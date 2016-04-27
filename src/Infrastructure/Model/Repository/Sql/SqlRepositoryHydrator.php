<?php

namespace NilPortugues\Foundation\Infrastructure\Model\Repository\Sql;

use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Fields;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Filter;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Identity;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Pageable;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Sort;
use NilPortugues\Foundation\Domain\Model\Repository\Page;

trait SqlRepositoryHydrator
{
    /**
     * {@inheritdoc}
     */
    public function find(Identity $id, Fields $fields = null)
    {
        $result = parent::find($id, $fields);

        return ($result) ? $this->mapping->fromArray($result) : [];
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(Filter $filter = null, Sort $sort = null, Fields $fields = null)
    {
        $results = parent::findBy($filter, $sort, $fields);

        return ($results) ? $this->hydrateArray($results) : [];
    }

    /**
     * @param array $results
     *
     * @return array
     */
    protected function hydrateArray(array $results)
    {
        return array_map(function ($result) {
            return $this->mapping->fromArray($result);
        }, $results);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(Pageable $pageable = null)
    {
        $page = parent::findAll($pageable);

        return new Page(
            ($page->content()) ? $this->hydrateArray($page->content()) : [],
            $page->totalElements(),
            $page->pageNumber(),
            $page->totalPages(),
            $page->sortings(),
            $page->filters(),
            $page->fields()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findByDistinct(Fields $distinctFields, Filter $filter = null, Sort $sort = null)
    {
        $results = parent::findByDistinct($distinctFields, $filter, $sort);

        return ($results) ? $this->hydrateArray($results) : [];
    }
}
