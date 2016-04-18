<?php

namespace NilPortugues\Foundation\Infrastructure\Model\Repository\Sql;

use NilPortugues\Serializer\Serializer;

class ArrayTransformer
{
    /**
     * @var ArrayTransformer The reference to *Singleton* instance of this class
     */
    private static $instance;
    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {
    }
    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Serializer The *Singleton* instance.
     */
    public static function create()
    {
        if (null === static::$instance) {
            static::$instance = new Serializer(new \NilPortugues\Serializer\Transformer\ArrayTransformer());
        }
        return static::$instance;
    }
    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @codeCoverageIgnore
     */
    private function __clone()
    {
    }
}