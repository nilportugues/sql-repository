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
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Mapping;

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
     * @param Mapping         $mapping
     *
     * @return QueryBuilder
     */
    public static function filter(QueryBuilder $query, FilterInterface $filter, Mapping $mapping)
    {
        $placeholders = [];
        $columns = $mapping->map();

        foreach ($filter->filters() as $condition => $filters) {
            if (array_key_exists($condition, $columns)) {
                $filters = self::removeEmptyFilters($filters);
                if (count($filters) > 0) {
                    self::processConditions($columns, $placeholders, $query, $condition, $filters);
                }
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
     * @param array        $columns
     * @param array        $placeholders
     * @param QueryBuilder $query
     * @param $condition
     * @param array $filters
     */
    private static function processConditions(
        array &$columns,
        array &$placeholders,
        QueryBuilder $query,
        $condition,
        array &$filters
    ) {
        switch ($condition) {
            case self::MUST:
                self::apply($columns, $placeholders, $query, $filters, 'andWhere', false);
                break;

            case self::MUST_NOT:
                self::apply($columns, $placeholders, $query, $filters, 'andWhere', true);
                break;

            case self::SHOULD:
                self::apply($columns, $placeholders, $query, $filters, 'orWhere', false);
                break;
        }
    }

    /**
     * @param array        $columns
     * @param array        $placeholders
     * @param QueryBuilder $query
     * @param array        $filters
     * @param $operator
     * @param $isNot
     */
    protected static function apply(
        array &$columns,
        array &$placeholders,
        QueryBuilder $query,
        array $filters,
        $operator,
        $isNot
    ) {
        foreach ($filters as $filterName => $valuePair) {
            foreach ($valuePair as $key => $value) {
                $key = self::fetchColumnName($columns, $key);
                if (is_array($value) && count($value) > 0) {
                    $value = array_values($value);
                    if (count($value[0]) > 1) {
                        switch ($filterName) {
                            case BaseFilter::RANGES:
                            case BaseFilter::NOT_RANGES:
                                $op = (!$isNot) ? 'BETWEEN' : 'NOT BETWEEN';

                                if ($filterName === BaseFilter::NOT_RANGES) {
                                    $op = (!$isNot) ? 'NOT BETWEEN' : 'BETWEEN';
                                }

                                self::rangeQuery($placeholders, $query, $operator, $key, $op, $value, $isNot);
                                break;
                        }
                    } else {
                        switch ($filterName) {
                            case BaseFilter::GROUP:
                            case BaseFilter::NOT_GROUP:
                                $op = (!$isNot) ? 'in' : 'notIn';

                                if ($filterName === BaseFilter::NOT_GROUP) {
                                    $op = (!$isNot) ? 'notIn' : 'in';
                                }

                                self::inGroupQuery($placeholders, $query, $operator, $key, $op, $value, $isNot);
                                break;
                        }
                    }
                }

                $value = (array) $value;
                $value = array_shift($value);
                $nextPlaceholder = self::nextPlaceholder($placeholders, $operator, $isNot);

                switch ($filterName) {
                    case BaseFilter::GREATER_THAN_OR_EQUAL:
                        $op = (!$isNot) ? 'gte' : 'lt';
                        self::query($placeholders, $query, $operator, $nextPlaceholder, $key, $op, $value);
                        break;
                    case BaseFilter::GREATER_THAN:
                        $op = (!$isNot) ? 'gt' : 'lte';
                        self::query($placeholders, $query, $operator, $nextPlaceholder, $key, $op, $value);
                        break;
                    case BaseFilter::LESS_THAN_OR_EQUAL:
                        $op = (!$isNot) ? 'lte' : 'gt';
                        self::query($placeholders, $query, $operator, $nextPlaceholder, $key, $op, $value);
                        break;
                    case BaseFilter::LESS_THAN:
                        $op = (!$isNot) ? 'lt' : 'gte';
                        self::query($placeholders, $query, $operator, $nextPlaceholder, $key, $op, $value);
                        break;
                    case BaseFilter::CONTAINS:
                    case BaseFilter::NOT_CONTAINS:
                        $value = '%'.$value.'%';
                        $op = (!$isNot) ? 'LIKE' : 'NOT LIKE';
                        if ($filterName === BaseFilter::NOT_CONTAINS) {
                            $op = (!$isNot) ? 'NOT LIKE' : 'LIKE';
                        }
                        self::likeQuery($placeholders, $query, $operator, $nextPlaceholder, $key, $op, $value);
                        break;
                    case BaseFilter::EQUALS:
                    case BaseFilter::NOT_EQUAL:
                        $op = (!$isNot) ? 'eq' : 'neq';
                        if ($filterName === BaseFilter::NOT_EQUAL) {
                            $op = (!$isNot) ? 'neq' : 'eq';
                        }
                        self::query($placeholders, $query, $operator, $nextPlaceholder, $key, $op, $value);
                        break;

                    case BaseFilter::EMPTY_FILTER:
                        $op = (!$isNot) ? 'eq' : 'neq';
                        self::query($placeholders, $query, $operator, $nextPlaceholder, $key, $op, '');
                        break;

                    case BaseFilter::NOT_EMPTY:
                        $op = (!$isNot) ? 'neq' : 'eq';
                        self::query($placeholders, $query, $operator, $nextPlaceholder, $key, $op, '');
                        break;

                    case BaseFilter::ENDS_WITH:
                    case BaseFilter::NOT_ENDS:
                        $op = (!$isNot) ? 'LIKE' : 'NOT LIKE';
                        $newValue = '%'.$value;
                        self::likeQuery($placeholders, $query, $operator, $nextPlaceholder, $key, $op, $newValue);
                        break;

                    case BaseFilter::STARTS_WITH:
                    case BaseFilter::NOT_STARTS:
                        $op = (!$isNot) ? 'LIKE' : 'NOT LIKE';
                        $newValue = $value.'%';
                        self::likeQuery($placeholders, $query, $operator, $nextPlaceholder, $key, $op, $newValue);
                        break;
                }
            }
        }
    }

    /**
     * @param $columns
     * @param $propertyName
     *
     * @return int
     */
    protected static function fetchColumnName(array &$columns, $propertyName)
    {
        if (empty($columns[$propertyName])) {
            throw new \RuntimeException(sprintf('Property %s has no associated column.', $propertyName));
        }

        return $columns[$propertyName];
    }

    /**
     * @param array        $placeholders
     * @param QueryBuilder $query
     * @param string       $operator
     * @param string       $key
     * @param string       $op
     * @param $value
     * @param bool $isNot
     */
    protected static function rangeQuery(
        array &$placeholders,
        QueryBuilder $query,
        $operator,
        $key,
        $op,
        $value,
        $isNot
    ) {
        $first = self::nextPlaceholder($placeholders, $operator, $isNot);
        $placeholders[$first] = self::toIntegerIfBoolean($value[0][0]);

        $second = self::nextPlaceholder($placeholders, $operator, $isNot);
        $placeholders[$second] = self::toIntegerIfBoolean($value[0][1]);

        $query->$operator(sprintf('%s %s %s AND %s', $key, $op, $first, $second));
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

    /**
     * @param array        $placeholders
     * @param QueryBuilder $query
     * @param string       $operator
     * @param string       $key
     * @param string       $op
     * @param $value
     * @param bool $isNot
     */
    protected static function inGroupQuery(
        array &$placeholders,
        QueryBuilder $query,
        $operator,
        $key,
        $op,
        $value,
        $isNot
    ) {
        $names = [];
        foreach ($value as $k => $v) {
            $nextPlaceholder = self::nextPlaceholder($placeholders, $operator, $isNot);
            $names[] = $nextPlaceholder;
            $placeholders[$nextPlaceholder] = self::toIntegerIfBoolean($v);
        }

        $query->$operator($query->expr()->$op($key, $names));
    }

    /**
     * @param array        $placeholders
     * @param QueryBuilder $query
     * @param string       $operator
     * @param string       $nextPlaceholder
     * @param string       $key
     * @param string       $op
     * @param $value
     */
    protected static function query(
        array &$placeholders,
        QueryBuilder $query,
        $operator,
        $nextPlaceholder,
        $key,
        $op,
        $value
    ) {
        $query->$operator($query->expr()->$op($key, $nextPlaceholder));
        $placeholders[$nextPlaceholder] = self::toIntegerIfBoolean($value);
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    protected static function toIntegerIfBoolean($value)
    {
        if ($value === true || $value === false) {
            $value = ($value) ? 1 : 0;
        }

        return $value;
    }

    /**
     * @param array        $placeholders
     * @param QueryBuilder $query
     * @param $operator
     * @param $nextPlaceholder
     * @param $key
     * @param $op
     * @param $value
     */
    protected static function likeQuery(
        array &$placeholders,
        QueryBuilder $query,
        $operator,
        $nextPlaceholder,
        $key,
        $op,
        $value
    ) {
        $query->$operator(sprintf('%s %s %s', $key, $op, $nextPlaceholder));
        $placeholders[$nextPlaceholder] = self::toIntegerIfBoolean($value);
    }
}
