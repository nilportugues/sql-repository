# SQL Repository
![PHP7 Tested](http://php-eye.com/badge/nilportugues/sql-repository/php70.svg)
[![Build Status](https://travis-ci.org/PHPRepository/sql-repository.svg)](https://travis-ci.org/PHPRepository/sql-repository) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/PHPRepository/php-sql-repository/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/PHPRepository/php-sql-repository/?branch=master) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/9fc69e98-13b4-4ea5-a5fb-c394b42586e3/mini.png?gold)](https://insight.sensiolabs.com/projects/9fc69e98-13b4-4ea5-a5fb-c394b42586e3) [![Latest Stable Version](https://poser.pugx.org/nilportugues/sql-repository/v/stable)](https://packagist.org/packages/nilportugues/sql-repository) [![Total Downloads](https://poser.pugx.org/nilportugues/sql-repository/downloads)](https://packagist.org/packages/nilportugues/sql-repository) [![License](https://poser.pugx.org/nilportugues/sql-repository/license)](https://packagist.org/packages/nilportugues/sql-repository)
[![Donate](https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif)](https://paypal.me/nilportugues)

SQL Repository library aims to reduce the time spent writing repositories. 

Motivation for this library was the boredom of writing SQL or using query builders to do the same thing over and over again in multiple projects.

SQL Repository allows you to fetch, paginate and operate with data easily without adding overhead and following good practices.

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
  - *InMemoryRepository*: for testing purposes
  - *MongoDBRepository*: because your schema keeps changing
  - *FileRepository*: sites without DB access or for testing purposes.
- **Mapping written in PHP.**
  - Supports custom Data Types (a.k.a Value Objects). 
  - Mapping deep data structures made easy using dot-notation.
- **Hydratation is optional.**
  - Use hydratation when needed. Use `SqlRepositoryHydrator` trait to enable it.
- **Both custom ids and autoincremental ids are supported**
  - Want to use UUID or a custom ID strategy? No problem! 
  
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

## Mapping

## Repository methods

### Filtering methods

### Sorting

### Fields

--

## Quality

To run the PHPUnit tests at the command line, go to the tests directory and issue phpunit.

This library attempts to comply with [PSR-1](http://www.php-fig.org/psr/psr-1/), [PSR-2](http://www.php-fig.org/psr/psr-2/), [PSR-4](http://www.php-fig.org/psr/psr-4/).

If you notice compliance oversights, please send a patch via [Pull Request](https://github.com/PHPRepository/sql-repository/pulls).

## Contribute

Contributions to the package are always welcome!

* Report any bugs or issues you find on the [issue tracker](https://github.com/PHPRepository/sql-repository/issues/new).
* You can grab the source code at the package's [Git Repository](https://github.com/PHPRepository/sql-repository).

## Support

Get in touch with me using one of the following means:

 - Emailing me at <contact@nilportugues.com>
 - Opening an [Issue](https://github.com/PHPRepository/sql-repository/issues/new)


## Authors

* [Nil Portugués Calderó](http://nilportugues.com)
* [The Community Contributors](https://github.com/PHPRepository/sql-repository/graphs/contributors)


## License
The code base is licensed under the [MIT license](LICENSE).


