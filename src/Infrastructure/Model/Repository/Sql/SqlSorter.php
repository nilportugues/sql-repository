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
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Order;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Sort as SortInterface;

/**
 * Class SqlSorter.
 */
class SqlSorter
{
    /**
     * @param QueryBuilder  $queryBuilder
     * @param SortInterface $sort
     * @param SqlMapping    $mapping
     */
    public static function sort(QueryBuilder $queryBuilder, SortInterface $sort, SqlMapping $mapping)
    {
        /* @var Order $order */
        $columns = $mapping->map();

        foreach ($sort->orders() as $propertyName => $order) {
            self::guardColumnExists($columns, $propertyName);
            $queryBuilder->orderBy($columns[$propertyName], $order->isAscending() ? Order::ASCENDING : Order::DESCENDING);
        }
    }

    /**
     * @param $columns
     * @param $propertyName
     *
     * @return mixed
     */
    protected static function guardColumnExists($columns, $propertyName)
    {
        if (empty($columns[$propertyName])) {
            throw new \RuntimeException(sprintf('Property %s has associated column.', $propertyName));
        }
    }
}
