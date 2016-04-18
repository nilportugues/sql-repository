<?php

/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 7/02/16
 * Time: 16:06.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace NilPortugues\Foundation\Infrastructure\Model\Repository\Sql;

use Doctrine\DBAL\Query\QueryBuilder;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\BaseFilter;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Filter as FilterInterface;

/**
 * Class SqlFilter.
 */
class SqlFilter
{
    const MUST_NOT = 'must_not';
    const MUST = 'must';
    const SHOULD = 'should';

    /**
     * @param QueryBuilder    $query
     * @param FilterInterface $filter
     *
     * @return QueryBuilder
     */
    public static function filter(QueryBuilder $query, FilterInterface $filter)
    {
        $placeholders = [];

        foreach ($filter->filters() as $condition => $filters) {
            $filters = self::removeEmptyFilters($filters);
            if (count($filters) > 0) {
                self::processConditions($placeholders, $query, $condition, $filters);
            }
        }

        $query->setParameters($placeholders);

        return $query;
    }

    /**
     * @param array $filters
     *
     * @return array
     */
    private static function removeEmptyFilters(array $filters)
    {
        $filters = array_filter($filters, function ($v) {
            return count($v) > 0;
        });

        return $filters;
    }

    /**
     * @param array $placeholders
     * @param QueryBuilder $query
     * @param $condition
     * @param array $filters
     */
    private static function processConditions(array &$placeholders, QueryBuilder $query, $condition, array &$filters)
    {
        switch ($condition) {
            case self::MUST:
                self::apply($placeholders, $query, $filters, 'andWhere', false);
                break;

            case self::MUST_NOT:
                self::apply($placeholders, $query, $filters, 'andWhere', true);
                break;

            case self::SHOULD:
                self::apply($placeholders, $query, $filters, 'orWhere', false);
                break;
        }
    }

    /**
     * @param array        $placeholders
     * @param QueryBuilder $query
     * @param array        $filters
     * @param string       $operator
     * @param bool         $isNot
     */
    protected static function apply(array &$placeholders, QueryBuilder $query, array $filters, $operator, $isNot)
    {
        foreach ($filters as $filterName => $valuePair) {
            foreach ($valuePair as $key => $value) {
                if (is_array($value) && count($value) > 0) {
                    if (count($value) > 1) {
                        switch ($filterName) {
                            case BaseFilter::RANGES:
                                $first = self::nextPlaceholder($placeholders, $operator, $isNot);
                                $placeholders[$first] = $value[0];

                                $second = self::nextPlaceholder($placeholders, $operator, $isNot);
                                $placeholders[$second] = $value[1];

                                $op = (!$isNot) ? 'BETWEEN' : 'NOT BETWEEN';
                                $query->$operator(sprintf('%s %s % AND %s', $key, $op, $first, $second));
                                break;

                            case BaseFilter::NOT_RANGES:
                                $first = self::nextPlaceholder($placeholders, $operator, $isNot);
                                $placeholders[$first] = $value[0];

                                $second = self::nextPlaceholder($placeholders, $operator, $isNot);
                                $placeholders[$second] = $value[1];

                                $op = (!$isNot) ? 'NOT BETWEEN' : 'BETWEEN';
                                $query->$operator(sprintf('%s %s % AND %s', $key, $op, $first, $second));
                                break;

                            case BaseFilter::GROUP:
                                $names = [];
                                foreach ($value as $k => $v) {
                                    $nextPlaceholder = self::nextPlaceholder($placeholders, $operator, $isNot);
                                    $names[] = $nextPlaceholder;
                                    $placeholders[$nextPlaceholder] = $v;
                                }

                                $op = ($isNot) ? 'notIn' : 'in';
                                $query->$operator($query->expr()->$op($key, $names));
                                break;
                        }
                        break;
                    }
                    $value = array_shift($value);
                }

                $nextPlaceholder = self::nextPlaceholder($placeholders, $operator, $isNot);

                switch ($filterName) {
                    case BaseFilter::GREATER_THAN_OR_EQUAL:
                        $op = (!$isNot) ? 'gte' : 'lt';
                        $query->$operator($query->expr()->$op($key, $nextPlaceholder));
                        $placeholders[$nextPlaceholder] = $value;
                        break;
                    case BaseFilter::GREATER_THAN:
                        $op = (!$isNot) ? 'gt' : 'lte';
                        $query->$operator($query->expr()->$op($key, $nextPlaceholder));
                        $placeholders[$nextPlaceholder] = $value;
                        break;
                    case BaseFilter::LESS_THAN_OR_EQUAL:
                        $op = (!$isNot) ? 'lte' : 'gt';
                        $query->$operator($query->expr()->$op($key, $nextPlaceholder));
                        $placeholders[$nextPlaceholder] = $value;
                        break;
                    case BaseFilter::LESS_THAN:
                        $op = (!$isNot) ? 'lt' : 'gte';
                        $query->$operator($query->expr()->$op($key, $nextPlaceholder));
                        $placeholders[$nextPlaceholder] = $value;
                        break;
                    case BaseFilter::CONTAINS:
                        $op = ($isNot) ? 'NOT LIKE' : 'LIKE';
                        $query->$operator(sprintf('%s %s %', $key, $op, $nextPlaceholder));
                        $placeholders[$nextPlaceholder] = '%'.$value.'%';
                        break;
                    case BaseFilter::NOT_CONTAINS:
                        $op = ($isNot) ? 'LIKE' : 'NOT LIKE';
                        $query->$operator(sprintf('%s %s %s', $key, $op, $nextPlaceholder));
                        $placeholders[$nextPlaceholder] = '%'.$value.'%';
                        break;
                    case BaseFilter::STARTS_WITH:
                        $op = ($isNot) ? 'LIKE' : 'NOT LIKE';
                        $query->$operator(sprintf('%s %s %s', $key, $op, $nextPlaceholder));
                        $placeholders[$nextPlaceholder] = '%'.$value;
                        break;
                    case BaseFilter::ENDS_WITH:
                        $op = ($isNot) ? 'LIKE' : 'NOT LIKE';
                        $query->$operator(sprintf('%s %s %s', $key, $op, $nextPlaceholder));
                        $placeholders[$nextPlaceholder] = $value.'%';
                        break;
                    case BaseFilter::EQUALS:
                        $query->$operator($query->expr()->eq($key, $nextPlaceholder));
                        $placeholders[$nextPlaceholder] = $value;
                        break;
                    case BaseFilter::NOT_EQUAL:
                        $query->$operator($query->expr()->neq($key, $nextPlaceholder));
                        $placeholders[$nextPlaceholder] = $value;
                        break;
                }
            }
        }
    }

    /**
     * @param array $placeholders
     * @param $operator
     * @param $isNot
     *
     * @return string
     */
    protected static function nextPlaceholder(array $placeholders, $operator, $isNot)
    {
        $operator = $operator[0];
        $isNot = ($isNot) ? 'n' : 'p';

        return ':k'.$operator.$isNot.count($placeholders);
    }
}
