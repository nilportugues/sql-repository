<?php

use NilPortugues\Example\Repository\User;
use NilPortugues\Example\Repository\UserId;
use NilPortugues\Example\Repository\UserMapping;
use NilPortugues\Example\Repository\UserRepository;
use NilPortugues\Foundation\Domain\Model\Repository\Filter;
use NilPortugues\Foundation\Domain\Model\Repository\Order;
use NilPortugues\Foundation\Domain\Model\Repository\Sort;

include_once __DIR__.'/../vendor/autoload.php';

$pdo = new PDO('sqlite::memory:');
$pdo->exec('
CREATE TABLE users (
  user_id INTEGER PRIMARY KEY AUTOINCREMENT,  
  username CHAR(255),
  public_username CHAR(255),
  email CHAR(255),
  created_at DATETIME
);');

$mapping = new UserMapping();
$repository = new UserRepository($pdo, $mapping);

$user = new User(1, 'nilportugues', 'Nil', 'hello@example.org', new DateTime('2016-01-11'));
$repository->add($user);

$userId = new UserId(1);
print_r($repository->find($userId));
echo PHP_EOL;

$filter = new Filter();
$filter->must()->beGreaterThanOrEqual('registeredOn', '2016-01-01 00:00:00');
$filter->must()->beLessThan('registeredOn', '2016-02-01 00:00:00');

$sort = new Sort();
$sort->setOrderFor('registeredOn', new Order('ASC'));

print_r($repository->findBy($filter, $sort));
echo PHP_EOL;
