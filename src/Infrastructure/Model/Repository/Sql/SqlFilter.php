<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 7/02/16
 * Time: 16:06
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\Foundation\Infrastructure\Model\Repository\Sql;

use NilPortugues\Foundation\Domain\Model\Repository\Contracts\BaseFilter;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Filter as FilterInterface;
use NilPortugues\Sql\QueryBuilder\Manipulation\QueryInterface;
use NilPortugues\Sql\QueryBuilder\Manipulation\Select;
use NilPortugues\Sql\QueryBuilder\Syntax\Where;

/**
 * Class SqlFilter
 * @package NilPortugues\Foundation\Infrastructure\Model\Repository\Sql
 */
class SqlFilter
{
    const MUST_NOT = 'must_not';
    const MUST = 'must';
    const SHOULD = 'should';

    /**
     * @param Select  $query
     * @param FilterInterface $filter
     *
     * @return QueryInterface
     */
    public static function filter(Select $query, FilterInterface $filter)
    {
        foreach ($filter->filters() as $condition => $filters) {
            switch ($condition) {
                case self::MUST:
                    $where = $query->where();
                    self::must($where, $filters);
                    break;

                case self::MUST_NOT:
                    //$where = $query->where('AND NOT');
                    self::mustNot($where, $filters);
                    break;

                case self::SHOULD:
                    $where = $query->where('OR');
                    self::should($where, $filters);
                    break;
            }
        }

        return $query;
    }

    /**
     * @param $where
     * @param array          $filters
     */
    protected static function must(Where $where, array $filters)
    {
        foreach ($filters as $filterName => $valuePair) {
            foreach ($valuePair as $key => $value) {
                if (is_array($value)) {
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

    /**
     * @param Where $where
     * @param array $filters
     */
    protected static function mustNot(Where $where, array $filters)
    {

    }


    /**
     * @param Where $where
     * @param array $filters
     */
    protected static function should(Where $where, array $filters)
    {

    }
}