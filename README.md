# SQL Repository
![PHP7 Tested](http://php-eye.com/badge/nilportugues/sql-repository/php70.svg)
[![Build Status](https://travis-ci.org/PHPRepository/sql-repository.svg)](https://travis-ci.org/PHPRepository/sql-repository)  [![SensioLabsInsight](https://insight.sensiolabs.com/projects/9fc69e98-13b4-4ea5-a5fb-c394b42586e3/mini.png?gold)](https://insight.sensiolabs.com/projects/9fc69e98-13b4-4ea5-a5fb-c394b42586e3) [![Latest Stable Version](https://poser.pugx.org/nilportugues/sql-repository/v/stable)](https://packagist.org/packages/nilportugues/sql-repository) [![Total Downloads](https://poser.pugx.org/nilportugues/sql-repository/downloads)](https://packagist.org/packages/nilportugues/sql-repository) [![License](https://poser.pugx.org/nilportugues/sql-repository/license)](https://packagist.org/packages/nilportugues/sql-repository)
[![Donate](https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif)](https://paypal.me/nilportugues)

SQL Repository library aims to reduce the time spent writing repositories. 

Motivation for this library was the boredom of writing SQL or using query builders to do the same thing over and over again in multiple projects.

SQL Repository allows you to fetch, paginate and operate with data easily without adding overhead and following good practices.

Table of Contents
=================

  * [Features](#features)
  * [Installation](#installation)
  * [Usage](#usage)
  * [Mapping](#mapping)
    * [Entity class](#entity-class)
    * [Mapping class](#mapping-class)
    * [Mapping the Repository](#mapping-the-repository)
  * [Repository](#repository)
    * [Methods](#methods)
      * [Available in SqlRepository](#available-in-sqlrepository)
      * [Available in SqlWriteRepository](#available-in-sqlwriterepository)
      * [Available in SqlReadRepository](#available-in-sqlreadrepository)
      * [Available in SqlPageRepository](#available-in-sqlpagerepository)
  * [Data Operations](#data-operations)
    * [Fields](#fields)
    * [Filtering](#filtering)
    * [Pagination](#pagination)
      * [Pageable](#pageable)
      * [Page object](#page-object)
    * [Sorting](#sorting)
      * [Ordering](#ordering)
  
## Features

- **Repository pattern right from the start.**
- **Multiple SQL drivers available using Doctrine's DBAL.**
- **All operations available from the beginning:**
  - Search the repository using PHP objects
  - No need to write SQL for basic operations.
  - Filtering is available using the Filter object.
  - Fetching certaing fields is available using the Fields Object.
  - Pagination is solved available using the Page and Pageable objects.
- **Custom operations can be written using DBAL.**
- **Want to change persistence layer? Provided repository alternatives are:**
  - *[InMemoryRepository](https://github.com/PHPRepository/repository)*: for testing purposes
  - *[FileRepository](https://github.com/PHPRepository/filesystem-repository)*: sites without DB access or for testing purposes.
  - *[MongoDBRepository](https://github.com/PHPRepository/mongodb-repository)*: because your schema keeps changing
- **Mapping written in PHP.**
  - Supports custom Data Types (a.k.a Value Objects). 
  - Mapping deep data structures made easy using dot-notation.
- **Hydratation is optional.**
  - Use hydratation when needed. Use `SqlRepositoryHydrator` trait to enable it.
- **Both custom ids and autoincremental ids are supported**
  - Want to use UUID or a custom ID strategy? No problem! 
- **Caching layer required? Easily to add!**
  - Require the *[Repository Cache](https://github.com/PHPRepository/repository-cache)* package from Composer to add consistent caching to all operations.

## Installation

Use [Composer](https://getcomposer.org) to install the package:

```json
$ composer require nilportugues/sql-repository
```

## Usage

**Show me the code**

See the [/example](https://github.com/PHPRepository/sql-repository/tree/master/example) directory. Examples for both `Custom ID` and `AutoIncremental ID` are provided.

**Explanation**

- You require a class implementing the `Mapping` interface provided
- You require a class implementing the `Identity` interface provided. Adds 2 methods, `id()` and `__toString`.
- You require a class to extend from `SqlRepository` class provided. Inject your PDO connection and the Mapping class to the `SqlRepository`

You're good to go.

--

# Mapping

Mapping must implement the `Mapping` interface. 

Mapping classes are used to read data from entities and save them in the storage of choice. This is done by mapping the Entities fields and specifying which fields and how are actually stored in the data storage.

For complex objects, let's say an Entity that has a Value Object, it is possible to still do one single mapping on the Entity and access the Value Object properties to get them stored.

Mappings are also used to hydrate data into it's entities again if the hydrator trait is used.

## Entity class

Remember, an Entity must implement the `Identity` interface to work with SqlRepository. This Entity can be any class of yours. 

```php
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Identity;

class User implements Identity
{
    protected $userId;
    protected $username;
    protected $alias;
    protected $email;
    protected $registeredOn;

    /**
     * User constructor.
     *
     * @param          $userId
     * @param          $username
     * @param          $alias
     * @param          $email
     * @param \DateTime $registeredOn
     */
    public function __construct($userId, $username, $alias, $email, \DateTime $registeredOn)
    {
        $this->userId = $userId;
        $this->username = $username;
        $this->alias = $alias;
        $this->email = $email;
        $this->registeredOn = $registeredOn;
    }

   // ... your getters/setters
  
    public function id()
    {
        return $this->userId;
    }
  
    public function __toString()
    {
        return (string) $this->id();
    }
}
```

## Mapping class

All methods from Mapping interface are mandatory. 

```php
use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Mapping;

class UserMapping implements Mapping
{
    /**
     * Name of the identity field in storage.
     */
    public function identity() : string
    {
        return 'user_id';
    }

    /**
     * Returns the table name.
     */
    public function name() : string
    {
        return 'users';
    }

    /**
     * Keys are object properties without property defined in identity(). 
     * Values its SQL column equivalents.
     */
    public function map() : array
    {
        return [
            'userId' => 'user_id',
            'username' => 'username',
            'alias' => 'public_username',
            'email' => 'email',
            
            // Notice how we are accessing date value inside
            // the \DateTime object! We use dont notation to 
            // access deep values.
            
            'registeredOn.date' => 'created_at', 
        ];
    }

    /**
     * @param array $data
     * @return User
     */
    public function fromArray(array $data)
    {
        return new User(
            $data['user_id'],
            $data['username'],
            $data['public_username'],
            $data['email'],
            new \DateTime($data['created_at'])
        );
    }

    /**
     * The automatic generated strategy used will be the data-store's if set to true.
     */
    public function autoGenerateId() : bool
    {
        return true;
    }
}
```

## Mapping the Repository

Finally, it's usage is straight-forward:

```php
use NilPortugues\Foundation\Infrastructure\Model\Repository\Sql\SqlRepository;
use NilPortugues\Foundation\Infrastructure\Model\Repository\Sql\SqlRepositoryHydrator;

class UserRepository extends SqlRepository
{
    use SqlRepositoryHydrator;
}

$pdo = new PDO('mysql:host=localhost;dbname=test', 'user', 'password');
$mapping = new UserMapping();
$repository = new UserRepository($pdo, $mapping);
```

---

# Repository 

The repository class implements all the methods required to interact and filter your data. 

SqlRepository can handle all CRUD operations by default by extending the `SqlRepository` class.

If you're not into CRUD, you can also have read-only, write-only and pagination-only repositories:

- For read-only repositories extend the `SqlReadRepository` class. 
- For write-only repositories extend the `SqlWriteRepository` class. 
- For pagination-only repositories extend the `SqlPageRepository` class. 


## Methods

### Available in SqlRepository

All the methods listed under SqlWriteRepository, SqlReadRepository and SqlPageRepository.

### Available in SqlWriteRepository

- `public function add($value)`
- `public function addAll(array $values)`
- `public function remove(Identity $id)`
- `public function removeAll(Filter $filter = null)`
- `public function transactional(callable $transaction)`
- `public function count(Filter $filter = null)`
- `public function exists(Identity $id)`

### Available in SqlReadRepository

- `public function find(Identity $id, Fields $fields = null)`
- `public function findBy(Filter $filter = null, Sort $sort = null, Fields $fields = null)`
- `public function findByDistinct(Fields $distinctFields, Filter $filter = null, Sort $sort = null, Fields $fields = null)`
- `public function count(Filter $filter = null)`
- `public function exists(Identity $id)`

### Available in SqlPageRepository

- `public function findAll(Pageable $pageable = null)`
- `public function count(Filter $filter = null)`
- `public function exists(Identity $id)`

---

# Data Operations

All data can be extracted by fields name, using filters, applying ordering and pages, capable of applying fields, filters and ordering criteria.

## Fields

Selecting by field will make hydratation fail. Currently partial object hydratation is not supported.

**Class:** `NilPortugues\Foundation\Domain\Model\Repository\Fields`

**Methods:**
- `public function __construct(array $fields = [])`
- `public function add($field)`
- `public function get()`

## Filtering

**Class:** `NilPortugues\Foundation\Domain\Model\Repository\Filter`

**Methods:**
- `public function filters()`
- `public function must()`
- `public function mustNot()`
- `public function should()`
- `public function clear()`
    
For **must()**, **mustNot()** and **should()**, the methods available are:

- `public function notStartsWith($filterName, $value)`
- `public function notEndsWith($filterName, $value)`
- `public function notEmpty($filterName)`
- `public function empty($filterName)`
- `public function startsWith($filterName, $value)`
- `public function endsWith($filterName, $value)`
- `public function equal($filterName, $value)`
- `public function notEqual($filterName, $value)`
- `public function includeGroup($filterName, array $value)`
- `public function notIncludeGroup($filterName, array $value)`
- `public function range($filterName, $firstValue, $secondValue)`
- `public function notRange($filterName, $firstValue, $secondValue)`
- `public function notContain($filterName, $value)`
- `public function contain($filterName, $value)`
- `public function beGreaterThanOrEqual($filterName, $value)`
- `public function beGreaterThan($filterName, $value)`
- `public function beLessThanOrEqual($filterName, $value)`
- `public function beLessThan($filterName, $value)`
- `public function clear()`
- `public function get()`
- `public function hasEmpty($filterName)` 

## Pagination 

Pagination is handled by two objects, `Pageable` that has the requirements to paginate, and `Page` that it's actually the page with the page data, such as page number, total number, and the data.

### Pageable

**Class:** `NilPortugues\Foundation\Domain\Model\Repository\Pageable`

**Methods:**
- `public function __construct($pageNumber, $pageSize, Sort $sort = null, Filter $filter = null, Fieldse $fields = null)`
- `public function offset()`
- `public function pageNumber()`
- `public function sortings()`
- `public function next()`
- `public function pageSize()`
- `public function previousOrFirst()`
- `public function hasPrevious()`
- `public function first()`
- `public function filters()`
- `public function fields()`

### Page object

**Class:** `NilPortugues\Foundation\Domain\Model\Repository\Page`

**Methods:**
- `public function __construct(array $elements, $totalElements, $pageNumber, $totalPages, Sort $sort = null, Filter $filter = null, Fields $fields = null)`
- `public function content()`
- `public function hasPrevious()`
- `public function isFirst()`
- `public function isLast()`
- `public function hasNext()`
- `public function pageSize()`
- `public function pageNumber()`
- `public function totalPages()`
- `public function nextPageable()`
- `public function sortings()`
- `public function filters()`
- `public function fields()`
- `public function previousPageable()`
- `public function totalElements()`
- `public function map(callable $converter)`

## Sorting

**Class:** `NilPortugues\Foundation\Domain\Model\Repository\Sort`

**Methods:**
- `public function __construct(array $properties = [], Order $order = null)`
- `public function andSort(SortInterface $sort)`
- `public function orders()`
- `public function equals(SortInterface $sort)`
- `public function orderFor($propertyName)`
- `public function setOrderFor($propertyName, Order $order)`
- `public function property($propertyName)`

### Ordering

Sometimes you want to sort by multiple fields, this is where Order comes in play.

**Class**: `NilPortugues\Foundation\Domain\Model\Repository\Order`

**Methods:**
- `public function __construct($direction)`
- `public function isDescending()`
- `public function isAscending()`
- `public function __toString()`
- `public function equals($object)`
- `public function direction()`


--

# Quality

To run the PHPUnit tests at the command line, go to the tests directory and issue phpunit.

This library attempts to comply with [PSR-1](http://www.php-fig.org/psr/psr-1/), [PSR-2](http://www.php-fig.org/psr/psr-2/), [PSR-4](http://www.php-fig.org/psr/psr-4/).

If you notice compliance oversights, please send a patch via [Pull Request](https://github.com/PHPRepository/sql-repository/pulls).

# Contribute

Contributions to the package are always welcome!

* Report any bugs or issues you find on the [issue tracker](https://github.com/PHPRepository/sql-repository/issues/new).
* You can grab the source code at the package's [Git Repository](https://github.com/PHPRepository/sql-repository).

# Support

Get in touch with me using one of the following means:

 - Emailing me at <contact@nilportugues.com>
 - Opening an [Issue](https://github.com/PHPRepository/sql-repository/issues/new)


# Authors

* [Nil Portugués Calderó](http://nilportugues.com)
* [The Community Contributors](https://github.com/PHPRepository/sql-repository/graphs/contributors)


# License
The code base is licensed under the [MIT license](LICENSE).


