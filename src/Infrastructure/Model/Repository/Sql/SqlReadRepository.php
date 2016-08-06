<?php

namespace NilPortugues\Foundation\Infrastructure\Model\Repository\Sql;

use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Fields;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Filter;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Identity;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\ReadRepository;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Sort;
use PDO;

class SqlReadRepository extends BaseSqlRepository implements ReadRepository
{
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
     * Returns all instances of the type.
     *
     * @param Filter|null $filter
     * @param Sort|null   $sort
     * @param Fields|null $fields
     *
     * @return array
     */
    public function findBy(Filter $filter = null, Sort $sort = null, Fields $fields = null) : array
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
     * Returns all instances of the type meeting $distinctFields values.
     *
     * @param Fields      $distinctFields
     * @param Filter|null $filter
     * @param Sort|null   $sort
     *
     * @return array
     */
    public function findByDistinct(Fields $distinctFields, Filter $filter = null, Sort $sort = null) : array
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
}
