<?php

/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 7/02/16
 * Time: 15:58.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace NilPortugues\Foundation\Infrastructure\Model\Repository\Sql;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Query\QueryBuilder;
use NilPortugues\Assert\Assert;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Fields;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Filter;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Identity;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Page;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Pageable;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\PageRepository;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\ReadRepository;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Sort;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\WriteRepository;
use NilPortugues\Foundation\Domain\Model\Repository\Filter as DomainFilter;
use NilPortugues\Foundation\Domain\Model\Repository\Page as ResultPage;
use PDO;
use PDOException;
use RuntimeException;

class SqlRepository implements ReadRepository, WriteRepository, PageRepository
{
    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    /** @var SqlMapping */
    protected $mapping;

    /**
     * SqlRepository constructor.
     *
     * @param PDO        $pdo
     * @param SqlMapping $mapping
     */
    public function __construct(PDO $pdo, SqlMapping $mapping)
    {
        $this->connection = DriverManager::getConnection(['pdo' => $pdo]);
        $this->mapping = $mapping;
    }

    /**
     * Returns whether an entity with the given id exists.
     *
     * @param $id
     *
     * @return bool
     */
    public function exists(Identity $id)
    {
        return !empty($this->find($id));
    }

    /**
     * Retrieves an entity by its id.
     *
     * @param Identity    $id
     * @param Fields|null $fields
     *
     * @return array
     */
    public function find(Identity $id, Fields $fields = null)
    {
        $result = $this->selectOneQuery($id->id(), ($fields) ? $this->getColumns($fields) : $fields);

        return ($result) ? $result : [];
    }

    /**
     * @param string     $id
     * @param array|null $fields
     *
     * @return mixed
     */
    protected function selectOneQuery($id, array $fields = null)
    {
        $query = $this->queryBuilder();

        return $query
            ->select(($fields) ? $fields : ['*'])
            ->from($this->mapping->name())
            ->andWhere($query->expr()->eq($this->mapping->identity(), ':id'))
            ->setParameter(':id', $id)
            ->execute()
            ->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function queryBuilder()
    {
        return $this->connection->createQueryBuilder();
    }

    /**
     * @param Fields $fields
     *
     * @return array
     */
    protected function getColumns(Fields $fields)
    {
        $newFields = [];

        foreach ($this->mapping->map() as $objectProperty => $tableColumn) {
            if (in_array($objectProperty, $fields->get())) {
                $newFields[$objectProperty] = $tableColumn;
            }
        }

        return $newFields;
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
        $mappings = $this->mappingWithoutIdentityColumn();

        $object = $this->mapping->toArray($value);
        $setOperation = ($isInsert) ? 'setValue' : 'set';

        foreach ($mappings as $objectProperty => $sqlColumn) {
            $this->mappingGuard($sqlColumn, $object, $value);
            $placeholder = ':'.$sqlColumn;
            $query->$setOperation($sqlColumn, $placeholder);
            $query->setParameter($placeholder, $object[$sqlColumn]);
        }
    }

    /**
     * @return array
     */
    protected function mappingWithoutIdentityColumn()
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
        $ids = [];
        foreach ($values as $value) {
            Assert::isInstanceOf($value, Identity::class);
            $ids[] = $value->id();
        }

        $alreadyExistingRows = $this->fetchExistingRows($ids);

        /** @var Identity $value */
        foreach ($values as $value) {
            foreach ($alreadyExistingRows as $row) {
                if ($value->id() == $row[$this->mapping->identity()]) {
                    $this->updateQuery($value);
                    continue;
                }
                $this->insertQuery($value);
            }
        }

        $mapping = array_flip($this->mapping->map());

        if (empty($mapping[$this->mapping->identity()])) {
            throw new RuntimeException(
                sprintf(
                    'Could not find primary key %s for %s',
                    $this->mapping->identity(),
                    $this->mapping->name()
                )
            );
        }

        $filter = new DomainFilter();
        $filter->must()->includeGroup($mapping[$this->mapping->identity()], $ids);

        return $this->findBy($filter);
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    protected function fetchExistingRows(array $ids)
    {
        $selectQuery = $this->queryBuilder();

        return (array) $selectQuery
            ->select([$this->mapping->identity()])
            ->from($this->mapping->name())
            ->where($selectQuery->expr()->in($this->mapping->identity(), $ids))
            ->execute()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns all instances of the type.
     *
     * @param Filter|null $filter
     * @param Sort|null   $sort
     * @param Fields|null $fields
     *
     * @return array
     */
    public function findBy(Filter $filter = null, Sort $sort = null, Fields $fields = null)
    {
        $query = $this->queryBuilder();

        $query
            ->select(($fields) ? $this->getColumns($fields) : ['*'])
            ->from($this->mapping->name());

        if ($filter) {
            SqlFilter::filter($query, $filter, $this->mapping);
        }

        if ($sort) {
            SqlSorter::sort($query, $sort, $this->mapping);
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
     *
     * @return bool
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
     * Returns a Page of entities meeting the paging restriction provided in the Pageable object.
     *
     * @param Pageable $pageable
     *
     * @return Page
     */
    public function findAll(Pageable $pageable = null)
    {
        $query = $this->queryBuilder();

        if ($pageable) {
            $fields = $this->getColumns($pageable->fields());
            $columns = (!empty($fields)) ? $fields : ['*'];

            if (count($pageable->distinctFields()->get()) > 0) {
                $columns = $this->getColumns($pageable->distinctFields());
                if (empty($columns)) {
                    $columns = ['*'];
                }

                $columns = 'DISTINCT '.implode(', ', $columns);
            }

            $query
                ->select($columns)
                ->from($this->mapping->name());

            if ($filter = $pageable->filters()) {
                SqlFilter::filter($query, $filter, $this->mapping);
            }

            if ($sort = $pageable->sortings()) {
                SqlSorter::sort($query, $sort, $this->mapping);
            }

            $total = $this->count($pageable->filters());

            return new ResultPage(
                $query->getConnection()->executeQuery(
                    sprintf($query->getSQL().' LIMIT %s, %s',
                        (int) ($pageable->offset() - $pageable->pageSize()),
                        (int) $pageable->pageSize()
                    ),
                    $query->getParameters()
                )->fetchAll(PDO::FETCH_ASSOC),
                $total,
                $pageable->pageNumber(),
                ceil($total / $pageable->pageSize())
            );
        }

        $query
            ->select('*')
            ->from($this->mapping->name());

        return new ResultPage(
            $query->getConnection()->executeQuery($query->getSQL(), $query->getParameters())->fetchAll(PDO::FETCH_ASSOC),
            $this->count(),
            1,
            1
        );
    }

    /**
     * Returns the total amount of elements in the repository given the restrictions provided by the Filter object.
     *
     * @param Filter|null $filter
     *
     * @return int
     */
    public function count(Filter $filter = null)
    {
        $query = $this->queryBuilder();

        $query
            ->select(['COUNT('.$this->mapping->identity().') AS total'])
            ->from($this->mapping->name())
            ->execute()
            ->fetch(PDO::FETCH_ASSOC);

        if ($filter) {
            SqlFilter::filter($query, $filter, $this->mapping);
        }

        return (int) $query->execute()->fetch(PDO::FETCH_ASSOC)['total'];
    }

    /**
     * Returns all instances of the type meeting $distinctFields values.
     *
     * @param Fields      $distinctFields
     * @param Filter|null $filter
     * @param Sort|null   $sort
     *
     * @return array
     */
    public function findByDistinct(Fields $distinctFields, Filter $filter = null, Sort $sort = null)
    {
        $query = $this->queryBuilder();

        $query
            ->select(($distinctFields) ? 'DISTINCT '.implode(', ', $this->getColumns($distinctFields)) : ['DISTINCT *'])
            ->from($this->mapping->name());

        if ($filter) {
            SqlFilter::filter($query, $filter, $this->mapping);
        }

        if ($sort) {
            SqlSorter::sort($query, $sort, $this->mapping);
        }

        return $query->execute()->fetchAll(PDO::FETCH_ASSOC);
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
}
