<?php

namespace NilPortugues\Foundation\Infrastructure\Model\Repository\Sql;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use NilPortugues\Assert\Assert;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Filter;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Identity;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Mapping;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\WriteRepository;
use NilPortugues\Foundation\Domain\Model\Repository\Filter as DomainFilter;
use NilPortugues\Foundation\Infrastructure\ObjectFlattener;
use PDO;
use PDOException;
use RuntimeException;

class SqlWriteRepository extends BaseSqlRepository implements WriteRepository
{
    /** @var \NilPortugues\Serializer\Serializer */
    protected $serializer;

    /**
     * SqlWriteRepository constructor.
     *
     * @param Connection $connection
     * @param Mapping    $mapping
     */
    public function __construct(Connection $connection, Mapping $mapping)
    {
        $this->serializer = ObjectFlattener::instance();
        parent::__construct($connection, $mapping);
    }

    /**
     * Adds a new entity to the storage.
     *
     * @param Identity $value
     *
     * @return mixed
     */
    public function add(Identity $value)
    {
        try {
            $this->updateQuery($value);
        } catch (PDOException $e) {
            $this->insertQuery($value);
        }

        $id = $value->id();
        if ($this->mapping->autoGenerateId()) {
            $id = $this->queryBuilder()->getConnection()->lastInsertId();
        }

        return $this->selectOneQuery($id);
    }

    /**
     * @param Identity $value
     *
     * @throws PDOException
     */
    protected function updateQuery(Identity $value)
    {
        $query = $this->queryBuilder();

        $this->populateQuery($query, $value, false);

        $affectedRows = $query
            ->update($this->mapping->name())
            ->where($query->expr()->eq($this->mapping->identity(), ':id'))
            ->setParameter(':id', $value->id())
            ->execute();

        if (0 === $affectedRows) {
            throw new PDOException(
                sprintf(
                    'Could not update %s where %s = %s',
                    $this->mapping->name(),
                    $this->mapping->identity(),
                    $value->id()
                )
            );
        }
    }

    /**
     * @param QueryBuilder $query
     * @param Identity     $value
     * @param bool         $isInsert
     */
    protected function populateQuery(QueryBuilder $query, Identity $value, $isInsert)
    {
        $mappings = $this->mapping->map();
        $flattenObject = $this->flattenObject($value);

        if ($this->mapping->autoGenerateId() && $isInsert) {
            $mappings = $this->mappingWithoutIdentityColumn();
        }

        if ($this->mapping->autoGenerateId()) {
            $keys = array_flip($this->mapping->map());
            $primaryKey = $keys[$this->mapping->identity()];
            unset($flattenObject[$primaryKey]);
            unset($mappings[$primaryKey]);
        }

        $setOperation = ($isInsert) ? 'setValue' : 'set';

        foreach ($mappings as $objectProperty => $sqlColumn) {
            $this->mappingGuard($objectProperty, $flattenObject, $value);
            $placeholder = ':'.$sqlColumn;
            $query->$setOperation($sqlColumn, $placeholder);
            $query->setParameter($placeholder, $flattenObject[$objectProperty]);
        }
    }

    /**
     * @param $value
     *
     * @return array
     */
    protected function flattenObject($value) : array
    {
        $result = $this->serializer->serialize($value);

        return array_map(function ($v) {
            if ($v === true || $v === false) {
                return ($v) ? 1 : 0;
            };

            return $v;
        }, $result);
    }

    /**
     * @return array
     */
    protected function mappingWithoutIdentityColumn() : array
    {
        $mappings = $this->mapping->map();

        if (false !== ($pos = array_search($this->mapping->identity(), $mappings, true))) {
            unset($mappings[$pos]);
        }

        return $mappings;
    }

    /**
     * @param string   $sqlColumn
     * @param array    $object
     * @param Identity $value
     *
     * @throws RuntimeException
     * @codeCoverageIgnore
     */
    protected function mappingGuard($sqlColumn, array $object, Identity $value)
    {
        if (false === array_key_exists($sqlColumn, $object)) {
            throw new RuntimeException(
                sprintf('Column %s not mapped for class %s.', $sqlColumn, get_class($value))
            );
        }
    }

    /**
     * @param Identity $value
     */
    protected function insertQuery(Identity $value)
    {
        $query = $this->queryBuilder();
        $this->populateQuery($query, $value, true);
        $query->insert($this->mapping->name())->execute();
    }

    /**
     * Adds a collections of entities to the storage.
     *
     * @param array $values
     *
     * @return mixed
     */
    public function addAll(array $values)
    {
        if (empty($values)) {
            return;
        }

        $ids = $this->fetchIds($values);
        $alreadyExistingRows = $this->fetchExistingRows($ids);

        $updates = [];
        $inserts = [];

        /** @var Identity $value */
        foreach ($values as $value) {
            if (false !== array_key_exists((string) $value->id(), $alreadyExistingRows)) {
                $updates[] = $value;
            } else {
                $inserts[] = $value;
            }
        }

        foreach ($updates as $update) {
            $this->updateQuery($update);
        }

        foreach ($inserts as $insert) {
            $this->insertQuery($insert);
        }

        $mapping = array_flip($this->mapping->map());
        $this->guardMappedIdentity($mapping);

        $filter = new DomainFilter();
        $filter->must()->includeGroup($mapping[$this->mapping->identity()], $ids);

        return $this->findByHelper($filter);
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    protected function fetchExistingRows(array $ids) : array
    {
        $selectQuery = $this->queryBuilder();

        $idsPlaceholders = [];
        foreach ($ids as $k => $id) {
            $idsPlaceholders[':ids'.$k] = $id;
        }

        $results = (array) $selectQuery
            ->select([$this->mapping->identity()])
            ->from($this->mapping->name())
            ->where($selectQuery->expr()->in($this->mapping->identity(), array_keys($idsPlaceholders)))
            ->setParameters($idsPlaceholders)
            ->execute()
            ->fetchAll(PDO::FETCH_ASSOC);

        $ids = [];

        foreach ($results as $row) {
            $id = array_pop($row);
            $ids[$id] = $id;
        }

        return $ids;
    }

    /**
     * @param $filter
     *
     * @return array
     */
    protected function findByHelper(Filter $filter = null) : array
    {
        $query = $this->queryBuilder();
        $query->select(['*'])->from($this->mapping->name());

        if ($filter) {
            SqlFilter::filter($query, $filter, $this->mapping);
        }

        return $query->execute()->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Removes the entity with the given id.
     *
     * @param $id
     */
    public function remove(Identity $id)
    {
        $query = $this->queryBuilder();

        $query
            ->delete($this->mapping->name())
            ->where($query->expr()->eq($this->mapping->identity(), ':id'))
            ->setParameter(':id', $id->id())
            ->execute();
    }

    /**
     * Removes all elements in the repository given the restrictions provided by the Filter object.
     * If $filter is null, all the repository data will be deleted.
     *
     * @param Filter $filter
     */
    public function removeAll(Filter $filter = null)
    {
        $query = $this->queryBuilder();
        $query->delete($this->mapping->name());

        if ($filter) {
            SqlFilter::filter($query, $filter, $this->mapping);
        }

        $query->execute();
    }

    /**
     * Repository data is added or removed as a whole block.
     * Must work or fail and rollback any persisted/erased data.
     *
     * @param callable $transaction
     *
     * @throws \Exception
     */
    public function transactional(callable $transaction)
    {
        $queryBuilder = $this->queryBuilder();

        try {
            $queryBuilder->getConnection()->beginTransaction();
            $transaction();
            $queryBuilder->getConnection()->commit();
        } catch (\Exception $e) {
            $queryBuilder->getConnection()->rollBack();
            throw $e;
        }
    }

    /**
     * @param array $values
     *
     * @return array
     */
    protected function fetchIds(array $values) : array
    {
        $ids = [];

        foreach ($values as $value) {
            Assert::isInstanceOf($value, Identity::class);
            if (null !== $value->id()) {
                $ids[] = $value->id();
            }
        }

        return $ids;
    }

    /**
     * @param $mapping
     * @codeCoverageIgnore
     */
    protected function guardMappedIdentity(array &$mapping)
    {
        if (empty($mapping[$this->mapping->identity()])) {
            throw new RuntimeException(
                sprintf(
                    'Could not find primary key %s for %s',
                    $this->mapping->identity(),
                    $this->mapping->name()
                )
            );
        }
    }
}
