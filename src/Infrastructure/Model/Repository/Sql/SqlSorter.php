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

use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Order;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Sort as SortInterface;
use NilPortugues\Sql\QueryBuilder\Manipulation\QueryInterface;
use NilPortugues\Sql\QueryBuilder\Manipulation\Select;
use NilPortugues\Sql\QueryBuilder\Syntax\OrderBy;

/**
 * Class SqlSorter
 * @package NilPortugues\Foundation\Infrastructure\Model\Repository\Sql
 */
class SqlSorter
{
    /**
     * @param Select        $query
     * @param SortInterface $sort
     *
     * @return QueryInterface
     */
    public static function sort(Select $query, SortInterface $sort)
    {
        /** @var Order $order */
        foreach ($sort->orders() as $propertyName => $order) {
            $query->orderBy($propertyName, $order->isAscending() ? OrderBy::ASC : OrderBy::DESC);
        }
    }
}