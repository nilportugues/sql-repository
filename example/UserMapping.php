<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 7/02/16
 * Time: 17:33
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\Example\Repository;

use NilPortugues\Foundation\Infrastructure\Model\Repository\Sql\SqlMapping;

/**
 * Class UserMapping
 * @package NilPortugues\Example\Repository
 */
class UserMapping extends SqlMapping
{
    /**
     * Name of the identity field in storage.
     * @return string
     */
    public function identity()
    {
        return 'user_id';
    }

    /**
     * Returns the table name.
     *
     * @return string
     */
    public function name()
    {
        return 'users';
    }

    /**
     * Array with keys as object properties, and values its SQL column equivalents.
     *
     * @return array
     */
    public function map()
    {
        return [
            'userId' => 'user_id',
            'username' => 'username',
            'alias' => 'public_username',
            'email' => 'email',
            'registeredOn' => 'created_at'
        ];
    }
}