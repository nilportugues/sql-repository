<?php

/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 7/02/16
 * Time: 17:33.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace NilPortugues\Example\Repository;

use DateTime;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Mapping;

/**
 * Class UserMapping.
 */
class UserMapping implements Mapping
{
    /**
     * Name of the identity field in storage.
     *
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
     * Keys are object properties without property defined in identity(). Values its SQL column equivalents.
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
            'registeredOn.date' => 'created_at',
        ];
    }

    /**
     * @param User $object
     *
     * @return array
     */
    public function toArray($object)
    {
        return [
            'user_id' => $object->id(),
            'username' => $object->username(),
            'public_username' => $object->alias(),
            'email' => $object->email(),
            'created_at' => $object->registeredOn()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param array $data
     *
     * @return User
     */
    public function fromArray(array $data)
    {
        if (empty($data)) {
            return;
        }


        return new User(
            $data['user_id'],
            $data['username'],
            $data['public_username'],
            $data['email'],
            new DateTime($data['created_at'])
        );
    }

    /**
     * The automatic generated strategy used will be the data-store's if set to true.
     *
     * @return bool
     */
    public function autoGenerateId()
    {
        return true;
    }

}
