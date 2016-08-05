<?php

namespace NilPortugues\Tests\Foundation\Infrastructure\Model\Repository\Sql;

use DateTime;
use Exception;
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Page;
use NilPortugues\Foundation\Domain\Model\Repository\Fields;
use NilPortugues\Foundation\Domain\Model\Repository\Filter;
use NilPortugues\Foundation\Domain\Model\Repository\Order;
use NilPortugues\Foundation\Domain\Model\Repository\Pageable;
use NilPortugues\Foundation\Domain\Model\Repository\Sort;
use NilPortugues\Tests\Foundation\Customer;
use NilPortugues\Tests\Foundation\CustomerId;
use NilPortugues\Tests\Foundation\SqliteCustomerMapping;
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
        $this->repository = new CustomerRepository($this->pdo, new SqliteCustomerMapping());
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

        $distinctFields = new Fields(['name', 'date.date', 'totalOrders', 'totalEarnings']);
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

        $distinctFields = new Fields(['name', 'date.date', 'totalOrders', 'totalEarnings']);
        $filter = new Filter();

        $results = $this->repository->findByDistinct(
            $distinctFields,
            $filter,
            new Sort(['name'], new Order('DESC'))
        );

        $this->assertEquals(1, count($results));
    }

    public function testItCanUpdateAnExistingCustomer()
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

    public function testItCanUpdateAllCustomersName()
    {
        $customer1 = $this->repository->find(new CustomerId(1));
        $customer1->setName('Homer Simpson');

        $customer2 = $this->repository->find(new CustomerId(2));
        $customer2->setName('Homer Simpson');

        $customer3 = $this->repository->find(new CustomerId(3));
        $customer3->setName('Homer Simpson');

        $customer4 = $this->repository->find(new CustomerId(4));
        $customer4->setName('Homer Simpson');

        $this->repository->addAll([$customer1, $customer2, $customer3, $customer4]);

        for ($i = 1; $i <= 4; ++$i) {
            $customer = $this->repository->find(new CustomerId($i));
            $this->assertEquals('Homer Simpson', $customer->name());
        }
    }

    //--------------------------------------------------------------------------------
    // MUST FILTER TESTS
    //--------------------------------------------------------------------------------

    public function testFindByMustRange()
    {
        $filter = new Filter();
        $filter->must()->range('totalOrders', 3, 4);

        $results = $this->repository->findBy($filter);

        $this->assertEquals(3, count($results));
    }

    public function testFindByMustNotRangeTest()
    {
        $filter = new Filter();
        $filter->must()->notRange('totalOrders', 2, 4);

        $results = $this->repository->findBy($filter);
        $this->assertEquals(1, count($results));
    }

    public function testFindByMustNotIncludeGroupTest()
    {
        $filter = new Filter();
        $filter
            ->must()
            ->notIncludeGroup(
                'date.date',
                ['2010-12-01', '2010-12-10', '2013-02-22']
            );

        $results = $this->repository->findBy($filter);

        $this->assertEquals(1, count($results));
    }

    public function testFindByWithMustEqual()
    {
        $filter = new Filter();
        $filter->must()->equal('name', 'Ken Sugimori');

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(1, count($results));
        foreach ($results as $result) {
            $this->assertTrue(false !== strpos($result->name(), 'Ken'));
        }
    }

    public function testFindByWithMustContain()
    {
        $filter = new Filter();
        $filter->must()->contain('name', 'Ken');

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(1, count($results));
        foreach ($results as $result) {
            $this->assertTrue(false !== strpos($result->name(), 'Ken'));
        }
    }

    public function testFindByWithMustNotContainTest()
    {
        $filter = new Filter();
        $filter->must()->notContain('name', 'Ken');

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(3, count($results));
        foreach ($results as $result) {
            $this->assertFalse(strpos($result->name(), 'Ken'));
        }
    }

    public function testFindByWithMustEndsWith()
    {
        $filter = new Filter();
        $filter->must()->endsWith('name', 'mori');

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(1, count($results));
        foreach ($results as $result) {
            $this->assertTrue(false !== strpos($result->name(), 'Ken'));
        }
    }

    public function testFindByWithMustStartsWith()
    {
        $filter = new Filter();
        $filter->must()->startsWith('name', 'Ke');

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(1, count($results));
        foreach ($results as $result) {
            $this->assertTrue(false !== strpos($result->name(), 'Ken'));
        }
    }

    public function testFindByWithMustBeLessThan()
    {
        $filter = new Filter();
        $filter->must()->beLessThan('totalOrders', 6);

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(4, count($results));
    }

    public function testFindByWithMustBeLessThanOrEqual()
    {
        $filter = new Filter();
        $filter->must()->beLessThanOrEqual('totalOrders', 4);

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(3, count($results));
    }

    public function testFindByWithMustBeGreaterThan()
    {
        $filter = new Filter();
        $filter->must()->beGreaterThan('totalOrders', 2);

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(4, count($results));
    }

    public function testFindByWithMustBeGreaterThanOrEqual()
    {
        $filter = new Filter();
        $filter->must()->beGreaterThanOrEqual('totalOrders', 2);

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(4, count($results));
    }

    public function testFindByMustIncludeGroup()
    {
        $filter = new Filter();
        $filter->must()->includeGroup('date.date', ['2010-12-01', '2010-12-10', '2013-02-22']);
        $results = $this->repository->findBy($filter);
        $this->assertEquals(3, count($results));
    }

    public function testFindByWithMustBeEmpty()
    {
        $filter = new Filter();
        $filter->must()->empty('totalOrders');

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(0, count($results));
    }

    public function testFindByWithMustBeNotEmpty()
    {
        $filter = new Filter();
        $filter->must()->notEmpty('totalOrders');

        $fields = new Fields(['name']);

        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(4, count($results));
    }

    //--------------------------------------------------------------------------------
    // MUST NOT FILTER TESTS
    //--------------------------------------------------------------------------------


    public function testFindByWithMustNotEqual()
    {
        $filter = new Filter();
        $filter->mustNot()->equal('name', 'Ken Sugimori');

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(3, count($results));
        foreach ($results as $result) {
            $this->assertFalse(strpos($result->name(), 'Ken'));
        }
    }

    public function testFindByWithMustNotNotEqual()
    {
        $filter = new Filter();
        $filter->mustNot()->notEqual('name', 'Ken Sugimori');

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(1, count($results));
        foreach ($results as $result) {
            $this->assertTrue(false !== strpos($result->name(), 'Ken'));
        }
    }

    public function testFindByWithMustNotContain()
    {
        $filter = new Filter();
        $filter->mustNot()->contain('name', 'Ken');

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(3, count($results));
        foreach ($results as $result) {
            $this->assertFalse(strpos($result->name(), 'Ken'));
        }
    }

    public function testFindByWithMustNotNotContain()
    {
        $filter = new Filter();
        $filter->mustNot()->notContain('name', 'Ken');

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(1, count($results));
        foreach ($results as $result) {
            $this->assertTrue(false !== strpos($result->name(), 'Ken'));
        }
    }

    public function testFindByWithMustNotEndsWith()
    {
        $filter = new Filter();
        $filter->mustNot()->endsWith('name', 'mori');

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(3, count($results));
        foreach ($results as $result) {
            $this->assertFalse(strpos($result->name(), 'Ken'));
        }
    }

    public function testFindByWithMustNotStartsWith()
    {
        $filter = new Filter();
        $filter->mustNot()->startsWith('name', 'Ke');

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(3, count($results));
        foreach ($results as $result) {
            $this->assertFalse(strpos($result->name(), 'Ken'));
        }
    }

    public function testFindByWithMustNotBeLessThan()
    {
        $filter = new Filter();
        $filter->mustNot()->beLessThan('totalOrders', 2);

        $results = $this->repository->findBy($filter);

        $this->assertEquals(4, count($results));
    }

    public function testFindByWithMustNotBeLessThanOrEqual()
    {
        $filter = new Filter();
        $filter->mustNot()->beLessThanOrEqual('totalOrders', 4);

        $results = $this->repository->findBy($filter);

        $this->assertEquals(1, count($results));
    }

    public function testFindByWithMustNotBeGreaterThan()
    {
        $filter = new Filter();
        $filter->mustNot()->beGreaterThan('totalOrders', 6);

        $results = $this->repository->findBy($filter);

        $this->assertEquals(4, count($results));
    }

    public function testFindByWithMustNotBeGreaterThanOrEqual()
    {
        $filter = new Filter();
        $filter->mustNot()->beGreaterThanOrEqual('totalOrders', 6);

        $results = $this->repository->findBy($filter);

        $this->assertEquals(4, count($results));
    }

    public function testFindByMustNotIncludeGroup()
    {
        $filter = new Filter();
        $filter->mustNot()->includeGroup('date.date', ['2010-12-01', '2010-12-10', '2013-02-22']);

        $results = $this->repository->findBy($filter);

        $this->assertEquals(1, count($results));
    }

    public function testFindByMustNotNotIncludeGroup()
    {
        $filter = new Filter();
        $filter->mustNot()->notIncludeGroup('date.date',
            ['2010-12-01', '2010-12-10', '2013-02-22']);

        $results = $this->repository->findBy($filter);

        $this->assertEquals(3, count($results));
    }

    public function testFindByMustNotRange()
    {
        $filter = new Filter();
        $filter->mustNot()->range('totalOrders', 2, 4);
        $results = $this->repository->findBy($filter);
        $this->assertEquals(1, count($results));
    }

    public function testFindByMustNotNotRangeTest()
    {
        $filter = new Filter();
        $filter->mustNot()->notRange('totalOrders', 2, 4);

        $results = $this->repository->findBy($filter);
        $this->assertEquals(3, count($results));
    }

    public function testFindByWithMustNotBeEmpty()
    {
        $filter = new Filter();
        $filter->mustNot()->empty('totalOrders');

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(4, count($results));
    }

    public function testFindByWithMustNotBeNotEmpty()
    {
        $filter = new Filter();
        $filter->mustNot()->notEmpty('totalOrders');

        $fields = new Fields(['name']);

        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(0, count($results));
    }
    //--------------------------------------------------------------------------------
    // SHOULD FILTER TESTS
    //--------------------------------------------------------------------------------

    public function testFindByWithShouldEqual()
    {
        $filter = new Filter();
        $filter->must()->contain('name', 'Hideo Kojima');
        $filter->should()->equal('name', 'Ken Sugimori');

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(1, count($results));
    }

    public function testFindByShouldContain()
    {
        $filter = new Filter();
        $filter->must()->contain('name', 'Hideo Kojima');
        $filter->should()->contain('name', 'Ken');

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(1, count($results));
    }

    public function testFindByShouldNotContainTest()
    {
        $filter = new Filter();
        $filter->must()->contain('name', 'Hideo Kojima');
        $filter->should()->notContain('name', 'Ken');

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(3, count($results));
    }

    public function testFindByShouldEndsWith()
    {
        $filter = new Filter();
        $filter->must()->contain('name', 'Hideo Kojima');
        $filter->should()->endsWith('name', 'mori');

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(1, count($results));
    }

    public function testFindByShouldStartsWith()
    {
        $filter = new Filter();
        $filter->must()->contain('name', 'Hideo Kojima');
        $filter->should()->startsWith('name', 'Ke');

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(1, count($results));
    }

    public function testFindByShouldBeLessThan()
    {
        $filter = new Filter();
        $filter->must()->contain('name', 'Hideo Kojima');
        $filter->should()->beLessThan('totalOrders', 6);

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(4, count($results));
    }

    public function testFindByShouldBeLessThanOrEqual()
    {
        $filter = new Filter();
        $filter->must()->contain('name', 'Hideo Kojima');
        $filter->should()->beLessThanOrEqual('totalOrders', 4);

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(3, count($results));
    }

    public function testFindByShouldBeGreaterThan()
    {
        $filter = new Filter();
        $filter->must()->contain('name', 'Hideo Kojima');
        $filter->should()->beGreaterThan('totalOrders', 2);

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(4, count($results));
    }

    public function testFindByShouldBeGreaterThanOrEqual()
    {
        $filter = new Filter();
        $filter->must()->contain('name', 'Hideo Kojima');
        $filter->should()->beGreaterThanOrEqual('totalOrders', 2);

        $fields = new Fields(['name']);
        $results = $this->repository->findBy($filter, null, $fields);

        $this->assertEquals(4, count($results));
    }

    public function testFindByShouldIncludeGroup()
    {
        $filter = new Filter();
        $filter->must()->contain('name', 'Hideo Kojima');
        $filter->should()->includeGroup('date.date', ['2010-12-01', '2010-12-10', '2013-02-22']);

        $results = $this->repository->findBy($filter);

        $this->assertEquals(3, count($results));
    }

    public function testFindByShouldNotIncludeGroupTest()
    {
        $filter = new Filter();
        $filter->must()->contain('name', 'Hideo Kojima');
        $filter->should()->notIncludeGroup('date.date',
            ['2010-12-01', '2010-12-10', '2013-02-22']);

        $results = $this->repository->findBy($filter);

        $this->assertEquals(1, count($results));
    }

    public function testFindByShouldRange()
    {
        $filter = new Filter();
        $filter->must()->contain('name', 'Hideo Kojima');
        $filter->should()->range('totalOrders', 2, 4);

        $results = $this->repository->findBy($filter);

        $this->assertEquals(3, count($results));
    }

    public function testFindByShouldNotRangeTest()
    {
        $filter = new Filter();
        $filter->must()->contain('name', 'Hideo Kojima');
        $filter->should()->notRange('totalOrders', 2, 4);

        $results = $this->repository->findBy($filter);

        $this->assertEquals(1, count($results));
    }

    //----------------------------------------------------------------------------


    public function testItCanFind()
    {
        /* @var Customer $customer */
        $id = new CustomerId(1);
        $customer = $this->repository->find($id);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals(1, $customer->id());
    }

    public function testFindAll()
    {
        $result = $this->repository->findAll();

        $this->assertInstanceOf(Page::class, $result);
        $this->assertEquals(4, count($result->content()));
    }

    public function testFindAllWithPageable()
    {
        $filter = new Filter();
        $filter->must()->beGreaterThanOrEqual('id', 1);

        $pageable = new Pageable(2, 2, new Sort(['name'], new Order('DESC')), $filter);
        $result = $this->repository->findAll($pageable);

        $this->assertInstanceOf(Page::class, $result);
        $this->assertEquals(2, count($result->content()));
    }

    public function testCount()
    {
        $this->assertEquals(4, $this->repository->count());
    }

    public function testCountWithFilter()
    {
        $filter = new Filter();
        $filter->must()->contain('name', 'Ken');

        $this->assertEquals(1, $this->repository->count($filter));
    }

    public function testExists()
    {
        $this->assertTrue($this->repository->exists(new CustomerId(1)));
    }

    public function testRemove()
    {
        $id = new CustomerId(1);
        $this->repository->remove($id);
        $this->assertEmpty($this->repository->find($id));
    }

    public function testRemoveAll()
    {
        $this->repository->removeAll();
        $this->assertFalse($this->repository->exists(new CustomerId(1)));
    }

    public function testRemoveAllWithFilter()
    {
        $filter = new Filter();
        $filter->must()->contain('name', 'Doe');

        $this->repository->removeAll($filter);
        $this->assertFalse($this->repository->exists(new CustomerId(1)));
    }

    public function testFindByWithEmptyRepository()
    {
        $this->repository->removeAll();

        $sort = new Sort(['name'], new Order('ASC'));
        $filter = new Filter();
        $filter->must()->contain('name', 'Ken');

        $this->assertEquals([], $this->repository->findBy($filter, $sort));
    }

    public function testAdd()
    {
        $customer = new Customer(5, 'Ken Sugimori', 4,  69158.687, new DateTime('2010-12-10'));
        $this->repository->add($customer);

        $this->assertNotNull($this->repository->find(new CustomerId(5)));
    }

    public function testFindReturnsNullIfNotFound()
    {
        $this->assertEmpty($this->repository->find(new CustomerId(99999)));
    }

    public function testAddAll()
    {
        $customer5 = new Customer(5, 'New customer 1', 4, 69158.687, new DateTime('2010-12-10'));
        $customer6 = new Customer(6, 'New customer 2', 4, 69158.687, new DateTime('2010-12-10'));

        $customers = [$customer5, $customer6];
        $this->repository->addAll($customers);

        $this->assertNotNull($this->repository->find(new CustomerId(5)));
        $this->assertNotNull($this->repository->find(new CustomerId(6)));
    }

    public function testAddAllRollbacks()
    {
        $this->setExpectedException(Exception::class);
        $customers = ['a', 'b'];
        $this->repository->addAll($customers);
    }

    public function testFind()
    {
        $expected = new Customer(4, 'Ken Sugimori', 4, 69158.687, new DateTime('2010-12-10'));

        $this->assertEquals($expected->id(), $this->repository->find(new CustomerId(4))->id());
    }

    public function testFindBy()
    {
        $sort = new Sort(['name'], new Order('ASC'));

        $filter = new Filter();
        $filter->must()->contain('name', 'Ken');

        $result = $this->repository->findBy($filter, $sort);

        $this->assertNotEmpty($result);
        $this->assertEquals(1, count($result));
    }

    public function tearDown()
    {
        PDOProvider::destroy($this->pdo);
    }
}
