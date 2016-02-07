<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 7/02/16
 * Time: 17:59
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\Example\Repository;

use NilPortugues\Foundation\Infrastructure\Model\Repository\Sql\SqlMapping;
use NilPortugues\Foundation\Infrastructure\Model\Repository\Sql\SqlRepository;
use NilPortugues\Sql\QueryBuilder\Builder\GenericBuilder;

/**
 * Class UserRepository
 * @package NilPortugues\Example\Repository
 */
class UserRepository extends SqlRepository
{

    /**
     * SqlRepository constructor.
     *
     * @param GenericBuilder $builder
     * @param SqlMapping     $mapping
     */
    public function __construct(GenericBuilder $builder, SqlMapping $mapping)
    {
        $this->builder = $builder;
        $this->mapping = $mapping;
    }
}