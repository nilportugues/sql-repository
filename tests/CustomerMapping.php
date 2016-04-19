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
        // TODO: Implement identity() method.
    }

    /**
     * Returns the table name.
     *
     * @return string
     */
    public function name()
    {
        // TODO: Implement name() method.
    }

    /**
     * Keys are object properties without property defined in identity(). Values its SQL column equivalents.
     *
     * @return array
     */
    public function map()
    {
        // TODO: Implement map() method.
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function fromArray(array $data)
    {
        // TODO: Implement fromArray() method.
    }

    /**
     * @param $object
     *
     * @return array
     */
    public function toArray($object)
    {
        // TODO: Implement toArray() method.
    }
}