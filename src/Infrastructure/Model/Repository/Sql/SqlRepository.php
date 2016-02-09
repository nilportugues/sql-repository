<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 7/02/16
 * Time: 15:58
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\Foundation\Infrastructure\Model\Repository\Sql;

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
use NilPortugues\Sql\QueryBuilder\Builder\GenericBuilder;
use PDO;
use PDOException;

class SqlRepository implements ReadRepository, WriteRepository, PageRepository
{
    /**
     * @var PDO
     */
    protected $pdo;
    /**
     * @var GenericBuilder
     */
    protected $builder;

    /**
     * @var SqlMapping
     */
    protected $mapping;

    /**
     * SqlRepository constructor.
     *
     * @param PDO            $pdo
     * @param GenericBuilder $builder
     * @param SqlMapping     $mapping
     */
    public function __construct(PDO $pdo, GenericBuilder $builder, SqlMapping $mapping)
    {
        $this->pdo = $pdo;
        $this->builder = $builder;
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
        $filter = new DomainFilter();
        $filter->must()->equals($this->mapping->identity(), $id->id());
        $result = (array) $this->findBy($filter, null, $fields);

        return $result;
    }

    /**
     * @param Fields $fields
     *
     * @return array
     */
    protected function getColumns(Fields $fields)
    {
        $newFields = [];

        foreach($this->mapping->map() as $objectProperty => $tableColumn) {
            if (in_array($objectProperty, $fields->get())) {
                $newFields[$objectProperty] = $tableColumn;
            }
        }

        return $newFields;
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
        $query = $this->builder->select(
            $this->mapping->name(),
            $this->fields($fields)
        );

        if ($filter) {
            SqlFilter::filter($query, $filter);
        }

        if ($sort) {
            SqlSorter::sort($query, $sort);
        }

        return [$this->builder->write($query), $this->builder->getValues()];
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
        $query = $this->builder->select($this->mapping->name());
        $query->count($this->mapping->identity());

        if ($filter) {
            SqlFilter::filter($query, $filter);
        }

        return [$this->builder->write($query), $this->builder->getValues()];
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

        return $this->count($filter);
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
        //@todo: solve the object graph and persistence.
    }

    /**
     * Adds a collections of entities to the storage.
     *
     * @param array $values
     * @return mixed
     *
     * @throws PDOException
     */
    public function addAll(array $values)
    {
        $this->pdo->beginTransaction();
        try {
            foreach($values as $value) {
                Assert::isInstanceOf($value, Identity::class);
                $this->add($value);
            }
            $this->pdo->commit();
        } catch(PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }


    /**
     * Removes the entity with the given id.
     *
     * @param $id
     * @return string
     */
    public function remove(Identity $id)
    {
        $query = $this->builder->delete($this->mapping->name());
        $query->where()->equals($this->mapping->identity(), $id->id());
        $query->limit(1);

        return [$this->builder->write($query), $this->builder->getValues()];
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
        $query = $this->builder->delete($this->mapping->name());

        if ($filter) {
            SqlFilter::filter($query, $filter);
        }

        return [$this->builder->write($query), $this->builder->getValues()];
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

        $query = $this->builder->select(
            $this->mapping->name(),
            $this->fields($pageable->fields())
        );

        $filter = $pageable->filters();
        if ($filter) {
            SqlFilter::filter($query, $filter);
        }

        $sort = $pageable->sortings();
        if ($sort) {
            SqlSorter::sort($query, $sort);
        }

        $query->limit(
            $pageable->offset() - $pageable->pageSize(),
            $pageable->pageSize()
        );

        return [$this->builder->write($query), $this->builder->getValues()];
    }

    /**
     * @param Fields $fields
     *
     * @return array
     */
    private function fields(Fields $fields = null)
    {
        $columns = $this->mapping->map();
        if ($fields) {
            $columns = $this->getColumns($fields);
        }
        return $columns;
    }
}