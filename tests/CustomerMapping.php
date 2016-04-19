<?php

namespace NilPortugues\Tests;

use NilPortugues\Foundation\Infrastructure\Model\Repository\Sql\SqlMapping;

class CustomerMapping extends SqlMapping
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
            'customer_name',
            'total_orders',
            'total_earnings',
            'created_at',
        ];
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function fromArray(array $data)
    {
        return new Customer(
            $data['customer_id'],
            $data['customer_name'],
            $data['total_orders'],
            $data['total_earnings'],
            new \DateTime($data['created_at'])
        );
    }

    /**
     * @param Customer $object
     *
     * @return array
     */
    public function toArray($object)
    {
        return [
            'customer_name' => $object->name(),
            'total_orders' => $object->totalOrders(),
            'total_earnings' => $object->totalEarnings(),
            'created_at' => $object->date()->format('Y-m-d H:i:s'),
        ];
    }
}
