<?php

namespace NilPortugues\Foundation\Infrastructure\Model\Repository\Sql;

use NilPortugues\Serializer\Serializer;
use NilPortugues\Serializer\Transformer\FlatArrayTransformer;

class ObjectFlattener
{
    /** @var Serializer */
    protected static $serializer;

    /**
     * @return Serializer
     */
    public static function instance()
    {
        if (null === self::$serializer) {
            self::$serializer = new Serializer(new FlatArrayTransformer());
        }

        return self::$serializer;
    }
}
