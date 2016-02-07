<?php

use NilPortugues\Example\Repository\UserId;

include_once '../vendor/autoload.php';

$builder = new \NilPortugues\Sql\QueryBuilder\Builder\GenericBuilder();
$mapping = new \NilPortugues\Example\Repository\UserMapping();
$repository = new \NilPortugues\Example\Repository\UserRepository($builder, $mapping);

$userId = new UserId(1);

print_r($repository->find($userId));
echo PHP_EOL;