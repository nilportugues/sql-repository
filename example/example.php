<?php

use NilPortugues\Example\Repository\UserId;
use NilPortugues\Example\Repository\UserMapping;
use NilPortugues\Example\Repository\UserRepository;
use NilPortugues\Foundation\Domain\Model\Repository\Filter;
use NilPortugues\Foundation\Domain\Model\Repository\Order;
use NilPortugues\Foundation\Domain\Model\Repository\Sort;
use NilPortugues\Sql\QueryBuilder\Builder\GenericBuilder;

include_once '../vendor/autoload.php';

$builder = new GenericBuilder();
$mapping = new UserMapping();
$repository = new UserRepository($builder, $mapping);

$userId = new UserId(1);
print_r($repository->find($userId));
echo PHP_EOL;


$filter = new Filter();
$filter->must()->greaterThanOrEqual('created_at', '2016-01-01');
$filter->must()->lessThan('created_at', '2016-02-01');

$sort = new Sort();
$sort->setOrderFor('created_at', new Order('ASC'));

print_r($repository->findBy($filter, $sort));
echo PHP_EOL;