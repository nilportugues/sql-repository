<?php

/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 7/02/16
 * Time: 17:03.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace NilPortugues\Foundation\Infrastructure\Model\Repository\Sql;

use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Mapping;

/**
 * Class SqlMapping.
 */
abstract class SqlMapping implements Mapping
{
    /**
     * Returns the table name.
     *
     * @return string
     */
    abstract public function name();

    /**
     * Array with keys as object properties, and values its SQL column equivalents.
     *
     * @return array
     */
    abstract public function map();
}
