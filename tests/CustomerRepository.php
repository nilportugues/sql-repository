<?php

namespace NilPortugues\Tests\Foundation;

use NilPortugues\Foundation\Infrastructure\Model\Repository\Sql\SqlRepository;
use NilPortugues\Foundation\Infrastructure\Model\Repository\Sql\SqlRepositoryHydrator;

class CustomerRepository extends SqlRepository
{
    use SqlRepositoryHydrator;
}
