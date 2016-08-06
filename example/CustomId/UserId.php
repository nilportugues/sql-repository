<?php

/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 7/02/16
 * Time: 18:12.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace NilPortugues\Example\CustomId;

use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Identity;

/**
 * Class UserId.
 */
class UserId implements Identity
{
    private $id;

    /**
     * UserId constructor.
     *
     * @param $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->id();
    }
}
