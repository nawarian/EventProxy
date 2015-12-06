<?php

namespace Respect\EventProxy\Event;

use Respect\Data\Collections\Collection;
use Symfony\Component\EventDispatcher\Event;

abstract class AbstractEvent extends Event
{
    protected $object;
    protected $collection;
    protected $isPre = true;

    public function __construct($object, Collection $collection)
    {
        $this->object = $object;
        $this->collection = $collection;
    }

    public function setPre($isPre = true)
    {
        $this->isPre = $isPre;
    }

    public function isPre()
    {
        return $this->isPre;
    }

    public function getEntity()
    {
        return $this->object;
    }

    public function getCollection()
    {
        return $this->collection;
    }

    abstract public function getEventFullQualifiedName();
}