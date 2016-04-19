<?php

namespace NilPortugues\Tests;

use NilPortugues\Foundation\Domain\Model\Repository\Contracts\Identity;

class Customer implements Identity
{
    /** @var int */
    protected $id;
    /** @var string */
    protected $name;
    /** @var float */
    protected $totalOrders;
    /** @var float */
    protected $totalEarnings;
    /** @var \DateTime */
    protected $date;

    /**
     * Customer constructor.
     *
     * @param int       $id
     * @param string    $name
     * @param float     $totalOrders
     * @param float     $totalEarnings
     * @param \DateTime $date
     */
    public function __construct($id, $name, $totalOrders, $totalEarnings, \DateTime $date)
    {
        $this->id = $id;
        $this->name = $name;
        $this->totalOrders = $totalOrders;
        $this->totalEarnings = $totalEarnings;
        $this->date = $date;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param float $totalOrders
     */
    public function setTotalOrders($totalOrders)
    {
        $this->totalOrders = $totalOrders;
    }

    /**
     * @param float $totalEarnings
     */
    public function setTotalEarnings($totalEarnings)
    {
        $this->totalEarnings = $totalEarnings;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return float
     */
    public function totalOrders()
    {
        return $this->totalOrders;
    }

    /**
     * @return float
     */
    public function totalEarnings()
    {
        return $this->totalEarnings;
    }

    /**
     * @return \DateTime
     */
    public function date()
    {
        return $this->date;
    }

    /**
     * @return int
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
        return (string) $this->id();
    }
}
