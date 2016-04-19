<?php

namespace NilPortugues\Tests;


use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Identity;

class CustomersId implements Identity
{
    /** @var string */
    protected $id;

    /**
     * CustomersId constructor.
     *
     * @param string $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->id;
    }
}