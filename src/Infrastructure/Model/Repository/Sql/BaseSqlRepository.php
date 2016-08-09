<?php

namespace NilPortugues\Foundation\Infrastructure\Model\Repository\Sql;

use Doctrine\DBAL\Connection;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Fields;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Filter;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Identity;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Mapping;
use PDO;

abstract class BaseSqlRepository
{
    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    /** @var Mapping */
    protected $mapping;

    /**
     * SqlPageRepository constructor.
     *
     * @param Connection $connection
     * @param Mapping    $mapping
     */
    protected function __construct(Connection $connection, Mapping $mapping)
    {
        $this->connection = $connection;
        $this->mapping = $mapping;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getDriver()
    {
        return $this->connection->createQueryBuilder();
    }
    
    /**
     * @param Connection $connection
     * @param Mapping    $mapping
     *
     * @return static
     */
    public static function create(Connection $connection, Mapping $mapping)
    {
        return new static($connection, $mapping);
    }

    /**
     * Returns the total amount of elements in the repository given the restrictions provided by the Filter object.
     *
     * @param Filter|null $filter
     *
     * @return int
     */
    public function count(Filter $filter = null): int
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
     * @param string     $id
     * @param array|null $fields
     *
     * @return mixed
     */
    protected function selectOneQuery($id, array $fields = null)
    {
        $query = $this->queryBuilder();

        $q = $query
            ->select(($fields) ? $fields : ['*'])
            ->from($this->mapping->name())
            ->andWhere($query->expr()->eq($this->mapping->identity(), ':id'))
            ->setParameter(':id', $id);

        return $q->execute()->fetch(PDO::FETCH_ASSOC);
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
}
