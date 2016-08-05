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
     * Returns whether an entity with the given id exists.
     *
     * @param $id
     *
     * @return bool
     */
    public function exists(Identity $id): bool
    {
        return !empty($this->selectOneQuery($id));
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

        return $this->selectOneQuery($value->id());
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
        if ($this->mapping->autoGenerateId() && $isInsert) {
            $mappings = $this->mappingWithoutIdentityColumn();
        }

        $object = $this->flattenObject($value);
        $setOperation = ($isInsert) ? 'setValue' : 'set';

        foreach ($mappings as $objectProperty => $sqlColumn) {
            $this->mappingGuard($objectProperty, $object, $value);
            $placeholder = ':'.$sqlColumn;
            $query->$setOperation($sqlColumn, $placeholder);
            $query->setParameter($placeholder, $object[$objectProperty]);
        }
    }

    /**
     * @param $value
     *
     * @return array
     */
    protected function flattenObject($value) : array
    {
        return $this->serializer->serialize($value);
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

        $query
            ->insert($this->mapping->name())
            ->execute();
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

        $results = (array) $selectQuery
            ->select([$this->mapping->identity()])
            ->from($this->mapping->name())
            ->where($selectQuery->expr()->in($this->mapping->identity(), $ids))
            ->execute()
            ->fetchAll(PDO::FETCH_ASSOC);

        $ids = [];

        foreach($results as $row) {
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
            $ids[] = $value->id();
        }

        return $ids;
    }

    /**
     * @param $mapping
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
