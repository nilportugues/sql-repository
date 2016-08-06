# SQL Repository
![PHP7 Tested](http://php-eye.com/badge/nilportugues/sql-repository/php70.svg)
[![Build Status](https://travis-ci.org/PHPRepository/sql-repository.svg)](https://travis-ci.org/PHPRepository/sql-repository) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/PHPRepository/php-sql-repository/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/PHPRepository/php-sql-repository/?branch=master) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/9fc69e98-13b4-4ea5-a5fb-c394b42586e3/mini.png?gold)](https://insight.sensiolabs.com/projects/9fc69e98-13b4-4ea5-a5fb-c394b42586e3) [![Latest Stable Version](https://poser.pugx.org/nilportugues/sql-repository/v/stable)](https://packagist.org/packages/nilportugues/sql-repository) [![Total Downloads](https://poser.pugx.org/nilportugues/sql-repository/downloads)](https://packagist.org/packages/nilportugues/sql-repository) [![License](https://poser.pugx.org/nilportugues/sql-repository/license)](https://packagist.org/packages/nilportugues/sql-repository)
[![Donate](https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif)](https://paypal.me/nilportugues)

SQL Repository library aims to reduce the time spent writing repositories. It allows you to fetch, paginate and operate with data easily without adding overhead and following good practices.

## Features

- **Repository pattern right from the start.**
- **Multiple SQL drivers available using Doctrine's DBAL.**
- **All operations available from the begining:**
  - Search the repository using PHP objects
  - No need to write SQL for basic operations.
  - Filtering is available using the Filter object.
  - Fetching certaing fields is available using the Fields Object.
  - Pagination is solved available using the Page and Pageable objects.
- **Custom operation can be written using DBAL.**
- **Want to change persistence layer? Provided repository alternatives:**
  - *InMemoryRepository*: for testing purposes
  - *MongoDBRepository*: because your schema keeps changing
  - **FileRepository*: sites without DB access or for testing purposes.
- **Mapping written in PHP.**
  - Supports custom Data Types (a.k.a Value Objects). 
  - Mapping deep data structures made easy using dot-notation.
- **Hydratation is optional.**
  





