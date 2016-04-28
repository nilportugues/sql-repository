<?php

namespace NilPortugues\Foundation\Infrastructure\Model\Repository\Sql;

use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Page;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Pageable;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\PageRepository;
use NilPortugues\Foundation\Domain\Model\Repository\Page as ResultPage;
use PDO;

class SqlPageRepository extends BaseSqlRepository implements PageRepository
{
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
            $columns = $this->getPageColumns($pageable);

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
     * @param Pageable $pageable
     *
     * @return array|string
     */
    protected function getPageColumns(Pageable $pageable)
    {
        $fields = $this->getColumns($pageable->fields());
        $columns = (!empty($fields)) ? $fields : ['*'];

        if (count($pageable->distinctFields()->get()) > 0) {
            $columns = $this->getColumns($pageable->distinctFields());
            if (empty($columns)) {
                $columns = ['*'];
            }

            $columns = 'DISTINCT '.implode(', ', $columns);
        }

        return $columns;
    }
}
