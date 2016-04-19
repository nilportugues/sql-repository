<?php

namespace NilPortugues\Tests\Foundation\Infrastructure\Model\Repository\Sql;

use NilPortugues\Tests\Customer;
use NilPortugues\Tests\CustomerId;
use NilPortugues\Tests\CustomerMapping;
use NilPortugues\Tests\CustomerRepository;
use NilPortugues\Tests\PDOProvider;

class SqlRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PDO */
    private $pdo;
    /** @var CustomerRepository  */
    private $repository;

    public function setUp()
    {
        $this->pdo = PDOProvider::create();
        $this->repository = new CustomerRepository($this->pdo, new CustomerMapping());
    }

    public function testItCanRunSuccessfulTransaction()
    {
        $transaction = function () {
            /** @var Customer $customer1 */
            $customer1 = $this->repository->find(new CustomerId(1));
            $customer1->setName('Homer Simpson');

            /** @var Customer $customer2 */
            $customer2 = $this->repository->find(new CustomerId(2));
            $customer2->setName('Homer Simpson');

            $this->repository->addAll([$customer1, $customer2]);
        };

        $this->repository->transactional($transaction);

        for ($i = 1; $i <= 2; ++$i) {
            $customer = $this->repository->find(new CustomerId($i));
            $this->assertEquals('Homer Simpson', $customer->name());
        }
    }

    public function testItCanFailTransactionAndThrowException()
    {
        $transaction = function () {
            /** @var Customer $customer1 */
            $customer1 = $this->repository->find(new CustomerId(1));
            $customer1->setName('Homer Simpson');

            /** @var Customer $customer2 */
            $customer2 = $this->repository->find(new CustomerId(2));
            $customer2->setName('Homer Simpson');

            $this->repository->addAll([$customer1, $customer2]);
            throw new \Exception('Just because');
        };

        try {
            $this->repository->transactional($transaction);
        } catch (\Exception $e) {
            for ($i = 1; $i <= 2; ++$i) {
                /** @var Customer $customer */
                $customer = $this->repository->find(new CustomerId($i));
                $this->assertNotEquals('Homer Simpson', $customer->name());
            }
        }
    }

    public function tearDown()
    {
        PDOProvider::destroy($this->pdo);
    }
}
