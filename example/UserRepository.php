<?php

/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 7/02/16
 * Time: 17:59.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace NilPortugues\Example\Repository;

use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Fields;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Filter;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Identity;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Pageable;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Sort;
use NilPortugues\Foundation\Domain\Model\Repository\Page;
use NilPortugues\Foundation\Infrastructure\Model\Repository\Sql\SqlRepository;

/**
 * Class UserRepository.
 */
class UserRepository extends SqlRepository
{
    /**
     * {@inheritdoc}
     */
    public function find(Identity $id, Fields $fields = null)
    {
        $result = parent::find($id, $fields);

        return $this->mapping->fromArray($result);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(Filter $filter = null, Sort $sort = null, Fields $fields = null)
    {
        $result = parent::findBy($filter, $sort, $fields);

        return $this->hydrateArray($result);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(Pageable $pageable = null)
    {
        $page = parent::findAll($pageable);

        return new Page(
            $this->hydrateArray($page->content()),
            $page->totalElements(),
            $page->pageNumber(),
            $page->totalPages(),
            $page->sortings(),
            $page->filters(),
            $page->fields()
        );
    }

    /**
     * @param array $result
     *
     * @return array
     */
    public function hydrateArray(array $result)
    {
        return array_map(function ($v) {
            return $this->mapping->fromArray($v);
        }, $result);
    }
}
