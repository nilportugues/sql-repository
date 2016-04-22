<?php

namespace NilPortugues\Tests\Foundation\Infrastructure\Model\Repository\Sql;

use DateTime;
use NilPortugues\Foundation\Domain\Model\Repository\Fields;
use NilPortugues\Foundation\Domain\Model\Repository\Filter;
use NilPortugues\Foundation\Domain\Model\Repository\Order;
use NilPortugues\Foundation\Domain\Model\Repository\Pageable;
use NilPortugues\Foundation\Domain\Model\Repository\Sort;
use NilPortugues\Tests\Foundation\Customer;
use NilPortugues\Tests\Foundation\CustomerId;
use NilPortugues\Tests\Foundation\CustomerMapping;
use NilPortugues\Tests\Foundation\CustomerRepository;
use NilPortugues\Tests\Foundation\PDOProvider;

class SqlRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PDO */
    private $pdo;
    /** @var CustomerRepository */
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
            /** @var Customer $customer */
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

    public function testItPageableFiltersAndDistinct()
    {
        /** @var Customer $client1 */
        $client1 = $this->repository->find(new CustomerId(1));
        $client1->setName('Homer Simpson');

        $client2 = $this->repository->find(new CustomerId(2));
        $client2->setName($client1->name());
        $client2->setDate($client1->date());
        $client2->setTotalEarnings($client1->totalEarnings());
        $client2->setTotalOrders($client1->totalOrders());

        $client3 = $this->repository->find(new CustomerId(3));
        $client3->setName($client1->name());
        $client3->setDate($client1->date());
        $client3->setTotalEarnings($client1->totalEarnings());
        $client3->setTotalOrders($client1->totalOrders());

        $client4 = $this->repository->find(new CustomerId(4));
        $client4->setName($client1->name());
        $client4->setDate($client1->date());
        $client4->setTotalEarnings($client1->totalEarnings());
        $client4->setTotalOrders($client1->totalOrders());

        $this->repository->addAll([$client1, $client2, $client3, $client4]);

        $distinctFields = new Fields(['name', 'date', 'totalOrders', 'totalEarnings']);
        $pageable = new Pageable(1, 10, new Sort(['name'], new Order('DESC')), null, null, $distinctFields);

        $result = $this->repository->findAll($pageable);

        $this->assertEquals(1, count($result->content()));
    }

    public function testItFindByDistinct()
    {
        /** @var Customer $client1 */
        $client1 = $this->repository->find(new CustomerId(1));
        $client1->setName('Homer Simpson');

        $client2 = $this->repository->find(new CustomerId(2));
        $client2->setName($client1->name());
        $client2->setDate($client1->date());
        $client2->setTotalEarnings($client1->totalEarnings());
        $client2->setTotalOrders($client1->totalOrders());

        $client3 = $this->repository->find(new CustomerId(3));
        $client3->setName($client1->name());
        $client3->setDate($client1->date());
        $client3->setTotalEarnings($client1->totalEarnings());
        $client3->setTotalOrders($client1->totalOrders());

        $client4 = $this->repository->find(new CustomerId(4));
        $client4->setName($client1->name());
        $client4->setDate($client1->date());
        $client4->setTotalEarnings($client1->totalEarnings());
        $client4->setTotalOrders($client1->totalOrders());

        $this->repository->addAll([$client1, $client2, $client3, $client4]);

        $distinctFields = new Fields(['name', 'date', 'totalOrders', 'totalEarnings']);
        $filter = new Filter();
        $filter->must()->notEmpty('name');

        $results = $this->repository->findByDistinct(
            $distinctFields,
            $filter,
            new Sort(['name'], new Order('DESC'))
        );

        $this->assertEquals(1, count($results));
    }

    public function testItCanUpdateAnExistingcustomer()
    {
        $expected = $this->repository->find(new CustomerId(4));
        $expected->setName('Homer Simpson');
        $expected->setDate(new DateTime('2010-12-10'));
        $expected->setTotalOrders(4);
        $expected->setTotalEarnings(69158.687);

        $this->repository->add($expected);

        $customer = $this->repository->find(new CustomerId(4));
        $this->assertEquals('Homer Simpson', $customer->name());
    }


    public function tearDown()
    {
        PDOProvider::destroy($this->pdo);
    }
}
