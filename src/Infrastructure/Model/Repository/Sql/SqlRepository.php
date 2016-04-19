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
use PDO;
use PDOException;

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
     * Retrieves an entity by its id.
     *
     * @param Identity    $id
     * @param Fields|null $fields
     *
     * @return array
     */
    public function find(Identity $id, Fields $fields = null)
    {
        return (array) $this->selectOneQuery($id->id(), ($fields) ? $this->getColumns($fields) : $fields);
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

        return (array) $query
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
     * Returns whether an entity with the given id exists.
     *
     * @param $id
     *
     * @return bool
     */
    public function exists(Identity $id)
    {
        $filter = new DomainFilter();
        $filter->must()->equals($this->mapping->identity(), $id->id());

        return $this->count($filter) > 0;
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
     * Adds a new entity to the storage.
     *
     * @param Identity $value
     *
     * @return mixed
     */
    public function add(Identity $value)
    {
        $this->insertQuery($value);

        return $this->selectOneQuery($value->id());
    }

    /**
     * @param Identity $value
     */
    protected function insertQuery(Identity $value)
    {
        $fields = [];
        $query = $this->queryBuilder();
        $object = $this->mapping->toArray($value);

        foreach ($this->mapping->map() as $objectProperty => $sqlColumn) {
            if (false === array_key_exists($objectProperty, $object)) {
                throw new \RuntimeException(
                    sprintf('Object of class %s has no property %s', get_class($value), $objectProperty)
                );
            }

            $placeholder = ':'.$sqlColumn;
            $fields[$sqlColumn] = $placeholder;
            $query->setParameter($placeholder, $object[$objectProperty]);
        }

        $query
            ->insert($this->mapping->name())
            ->values($fields)
            ->execute();
    }

    /**
     * Adds a collections of entities to the storage.
     *
     * @param array $values
     *
     * @return mixed
     *
     * @throws PDOException
     */
    public function addAll(array $values)
    {
        $ids = [];
        foreach ($values as $value) {
            Assert::isInstanceOf($value, Identity::class);
            $ids[] = $value->id();
        }

        $alreadyExistingRows = $this->fetchExistingRows($ids);
        $transactionalQueries = $this->queryBuilder();

        try {
            $transactionalQueries->getConnection()->beginTransaction();

            /** @var Identity $value */
            foreach ($values as $value) {
                if (false !== in_array($value->id(), $alreadyExistingRows)) {
                    $this->updateQuery($value);
                    continue;
                }
                $this->insertQuery($value);
            }

            $transactionalQueries->getConnection()->commit();
        } catch (PDOException $e) {
            $transactionalQueries->getConnection()->rollBack();
            throw $e;
        }

        $filter = new DomainFilter();
        $filter->must()->includesGroup($this->mapping->identity(), $ids);

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
     * @param Identity $value
     */
    protected function updateQuery(Identity $value)
    {
        $fields = [];
        $query = $this->queryBuilder();
        $object = $this->mapping->toArray($value);

        foreach ($this->mapping->map() as $objectProperty => $sqlColumn) {
            if (false === array_key_exists($objectProperty, $object)) {
                throw new \RuntimeException(
                    sprintf('Object of class %s has no property %s', get_class($value), $objectProperty)
                );
            }

            $placeholder = ':'.$sqlColumn;
            $fields[$sqlColumn] = $placeholder;
            $query->setParameter($placeholder, $value->$objectProperty());
        }

        $query
            ->update($this->mapping->name())
            ->values($fields)
            ->where($query->expr()->eq($this->mapping->identity(), $value->id()))
            ->execute();
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

      //  echo $query->getSQL(); die();

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
            ->delete()
            ->from($this->mapping->name())
            ->andWhere($query->expr()->eq($this->mapping->identity(), ':id'))
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
        $query->delete()->from($this->mapping->name());

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

        $query
            ->select(($fields = $pageable->fields()) ? $this->getColumns($fields) : ['*'])
            ->from($this->mapping->name());

        if ($filter = $pageable->filters()) {
            SqlFilter::filter($query, $filter, $this->mapping);
        }

        if ($sort = $pageable->sortings()) {
            SqlSorter::sort($query, $sort, $this->mapping);
        }

        $sql = sprintf($query->getSQL().' LIMIT %s, %s',
            (int) ($pageable->offset() - $pageable->pageSize()),
            (int) $pageable->pageSize()
        );

        return $query->getConnection()->executeQuery($sql, $query->getParameters());
    }
}
