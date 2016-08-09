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
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Fields;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Filter;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Identity;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Mapping;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Page;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Pageable;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\PageRepository;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\ReadRepository;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Sort;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\WriteRepository;
use PDO;

class SqlRepository implements ReadRepository, WriteRepository, PageRepository
{
    /** @var SqlPageRepository */
    protected $pageRepository;

    /** @var SqlWriteRepository */
    protected $writeRepository;

    /** @var SqlReadRepository */
    protected $readRepository;

    /** @var Mapping */
    protected $mapping;

    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    /**
     * SqlRepository constructor.
     *
     * @param PDO     $pdo
     * @param Mapping $mapping
     */
    public function __construct(PDO $pdo, Mapping $mapping)
    {
        $this->connection = DriverManager::getConnection(['pdo' => $pdo]);
        $this->mapping = $mapping;

        $this->readRepository = SqlReadRepository::create($this->connection, $this->mapping);
        $this->writeRepository = SqlWriteRepository::create($this->connection, $this->mapping);
        $this->pageRepository = SqlPageRepository::create($this->connection, $this->mapping);
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getDriver()
    {
        return $this->readRepository->getDriver();
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
        return $this->readRepository->exists($id);
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
        return $this->readRepository->find($id, $fields);
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
        return $this->writeRepository->add($value);
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
        return $this->writeRepository->addAll($values);
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
    public function findBy(Filter $filter = null, Sort $sort = null, Fields $fields = null): array
    {
        return $this->readRepository->findBy($filter, $sort, $fields);
    }

    /**
     * Removes the entity with the given id.
     *
     * @param $id
     */
    public function remove(Identity $id)
    {
        $this->writeRepository->remove($id);
    }

    /**
     * Removes all elements in the repository given the restrictions provided by the Filter object.
     * If $filter is null, all the repository data will be deleted.
     *
     * @param Filter $filter
     */
    public function removeAll(Filter $filter = null)
    {
        $this->writeRepository->removeAll($filter);
    }

    /**
     * Returns a Page of entities meeting the paging restriction provided in the Pageable object.
     *
     * @param Pageable $pageable
     *
     * @return Page
     */
    public function findAll(Pageable $pageable = null) : Page
    {
        return $this->pageRepository->findAll($pageable);
    }

    /**
     * Returns the total amount of elements in the repository given the restrictions provided by the Filter object.
     *
     * @param Filter|null $filter
     *
     * @return int
     */
    public function count(Filter $filter = null) : int
    {
        return $this->readRepository->count($filter);
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
    public function findByDistinct(Fields $distinctFields, Filter $filter = null, Sort $sort = null): array
    {
        return $this->readRepository->findByDistinct($distinctFields, $filter, $sort);
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
        $this->writeRepository->transactional($transaction);
    }
}
