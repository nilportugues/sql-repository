<?php

namespace NilPortugues\Tests\Foundation;

use DateTime;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Mapping;

class SqliteCustomerMapping implements Mapping
{
    /**
     * Name of the identity field in storage.
     *
     * @return string
     */
    public function identity()
    {
        return 'customer_id';
    }

    /**
     * Returns the table name.
     *
     * @return string
     */
    public function name()
    {
        return 'customers';
    }

    /**
     * Keys are object properties without property defined in identity(). Values its SQL column equivalents.
     *
     * @return array
     */
    public function map()
    {
        return [
            'id' => 'customer_id',
            'name' => 'customer_name',
            'totalOrders' => 'total_orders',
            'totalEarnings' => 'total_earnings',
            'date' => 'created_at',
        ];
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function fromArray(array $data)
    {
        if (empty($data)) {
            return;
        }

        return new Customer(
            !empty($data['customer_id']) ? $data['customer_id'] : '',
            !empty($data['customer_name']) ? $data['customer_name'] : '',
            !empty($data['total_orders']) ? $data['total_orders'] : '',
            !empty($data['total_earnings']) ? $data['total_earnings'] : '',
            !empty($data['created_at']) ? (new DateTime())->setTimestamp(strtotime($data['created_at'])) : new DateTime()
        );
    }

    /**
     * The automatic generated strategy used will be the data-store's if set to true.
     *
     * @return bool
     */
    public function autoGenerateId()
    {
        return false;
    }

    /**
     * @deprecated
     */
    public function toArray($object)
    {
        return [
            'customer_id' => $object->id(),
            'customer_name' => $object->name(),
            'total_orders' => $object->totalOrders(),
            'total_earnings' => $object->totalEarnings(),
            'created_at' => $object->date()->getTimestamp(),
        ];
    }
}
