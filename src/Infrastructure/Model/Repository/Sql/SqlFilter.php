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
        foreach ($filter->filters() as $condition => $filters) {
            $filters = self::removeEmptyFilters($filters);
            if (count($filters) > 0) {
                self::processConditions($query, $condition, $filters);
            }
        }

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
     * @param QueryBuilder $query
     * @param $condition
     * @param array $filters
     */
    private static function processConditions(QueryBuilder $query, $condition, array &$filters)
    {
        switch ($condition) {
            case self::MUST:
                $where = $query->where('AND');
                self::apply($where, $filters);
                break;

            case self::MUST_NOT:
                $where = $query->where()->subWhere('AND NOT');
                self::apply($where, $filters);
                break;

            case self::SHOULD:
                $where = $query->where()->subWhere('OR');
                self::apply($where, $filters);
                break;
        }
    }

    /**
     * @param QueryBuilder $where
     * @param array        $filters
     */
    protected static function apply(QueryBuilder $where, array $filters)
    {
        foreach ($filters as $filterName => $valuePair) {
            foreach ($valuePair as $key => $value) {
                if (is_array($value) && count($value) > 0) {
                    if (count($value) > 1) {
                        switch ($filterName) {
                            case BaseFilter::RANGES:
                                $where->between($key, $value[0], $value[1]);
                                break;
                            case BaseFilter::NOT_RANGES:
                                $where->notBetween($key, $value[0], $value[1]);
                                break;
                            case BaseFilter::GROUP:
                                $where->in($key, $value);
                                break;
                        }
                        break;
                    }
                    $value = array_shift($value);
                }

                switch ($filterName) {
                    case BaseFilter::GREATER_THAN_OR_EQUAL:
                        $where->greaterThanOrEqual($key, $value);
                        break;
                    case BaseFilter::GREATER_THAN:
                        $where->greaterThan($key, $value);
                        break;
                    case BaseFilter::LESS_THAN_OR_EQUAL:
                        $where->lessThanOrEqual($key, $value);
                        break;
                    case BaseFilter::LESS_THAN:
                        $where->lessThan($key, $value);
                        break;
                    case BaseFilter::CONTAINS:
                        $where->in($key, $value);
                        break;
                    case BaseFilter::NOT_CONTAINS:
                        $where->notIn($key, $value);
                        break;
                    case BaseFilter::STARTS_WITH:
                        $where->like($key, sprintf('%%s', $value));
                        break;
                    case BaseFilter::ENDS_WITH:
                        $where->like($key, sprintf('%s%', $value));
                        break;
                    case BaseFilter::EQUALS:
                        $where->equals($key, $value);
                        break;
                    case BaseFilter::NOT_EQUAL:
                        $where->notEquals($key, $value);
                        break;
                }
            }
        }
    }
}
