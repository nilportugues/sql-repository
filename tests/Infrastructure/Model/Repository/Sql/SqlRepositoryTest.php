<?php

namespace NilPortugues\Tests\Foundation\Infrastructure\Model\Repository\Sql;

use NilPortugues\Tests\CustomerMapping;
use NilPortugues\Tests\CustormerRepository;
use NilPortugues\Tests\PDOProvider;

class SqlRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PDO */
    private $pdo;
    /** @var CustormerRepository  */
    private $repository;
    
    public function setUp()
    {
        $this->pdo = PDOProvider::create();
        $this->repository = new CustormerRepository($this->pdo, new CustomerMapping());
    }

    public function tearDown()
    {
        PDOProvider::destroy($this->pdo);
    }
}
