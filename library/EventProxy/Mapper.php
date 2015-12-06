<?php

namespace Respect\EventProxy;

use Respect\Data\AbstractMapper;
use Respect\Data\Styles;
use Respect\Data\Collections\Collection;
use Respect\EventProxy\Event;
use SplObjectStorage;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Mapper extends AbstractMapper
{
    protected $concreteMapper;
    protected $dispatcher;

    protected $new;
    protected $tracked;
    protected $changed;
    protected $removed;

    public function __construct(AbstractMapper $mapper, EventDispatcher $dispatcher = null)
    {
        $this->concreteMapper = $mapper;

        if (is_null($dispatcher)) {
            $dispatcher = new EventDispatcher();
        }

        $this->setEventDispatcher($dispatcher);
    }

    public function getEventDispatcher()
    {
        return $this->dispatcher;
    }
    public function setEventDispatcher(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        return $this;
    }

    protected function createStatement(Collection $fromCollection, $withExtra = null)
    {
        $method = new ReflectionMethod(get_class($this->concreteMapper), 'createStatement');
        $method->setAccessible(true);

        return $method->invoke($this->concreteMapper, $fromCollection, $withExtra);
    }

    public function flush()
    {
        $this->dispatchPreFlush();
        $flushResult = $this->concreteMapper->flush();
        $this->dispatchPostFlush();

        return $flushResult;
    }

    private function getPropertyFromObject($object, $property)
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException('Object expected as first parameter.');
        }

        $ref = new \ReflectionProperty($object, $property);
        if (!$ref->isPublic()) {
            $ref->setAccessible(true);
        }

        return $ref->getValue($object);
    }

    protected function composeEntitiesFromMapper(AbstractMapper $mapper)
    {
        $this->new      = $this->getPropertyFromObject($mapper, 'new');
        $this->tracked  = $this->getPropertyFromObject($mapper, 'tracked');
        $this->changed  = $this->getPropertyFromObject($mapper, 'changed');
        $this->removed  = $this->getPropertyFromObject($mapper, 'removed');
    }

    protected function dispatchEntities($eventClassName, $entities, $isPre = true)
    {
        foreach ($entities as $entity) {
            $collection = $this->tracked[$entity];
            $this->dispatchEvent($eventClassName,
                array(
                    $entity,
                    $collection
                ),
                $isPre
            );
        }
    }

    protected function dispatchEvent($eventClassName, $arguments, $isPre = true)
    {
        if (class_exists($eventClassName)) {
            $ref = new \ReflectionClass($eventClassName);
            $event = $ref->newInstanceArgs($arguments);
            $event->setPre($isPre);

            $this->dispatcher->dispatch($event->getEventFullQualifiedName(), $event);
        }
    }

    protected function dispatchPreFlush()
    {
        $this->composeEntitiesFromMapper($this->concreteMapper);

        $this->dispatchEntities('Respect\\EventProxy\\Event\\Insert', $this->new, true);
        $this->dispatchEntities('Respect\\EventProxy\\Event\\Update', $this->changed, true);
        $this->dispatchEntities('Respect\\EventProxy\\Event\\Delete', $this->removed, true);
    }

    public function dispatchPostFlush()
    {
        $this->dispatchEntities('Respect\\EventProxy\\Event\\Insert', $this->new, false);
        $this->dispatchEntities('Respect\\EventProxy\\Event\\Update', $this->changed, false);
        $this->dispatchEntities('Respect\\EventProxy\\Event\\Delete', $this->removed, false);
    }

    public function on($eventName, callable $callback)
    {
        return $this->dispatcher->addListener("respect.{$eventName}", $callback);
    }
    
    public function reset()
    {
        $this->changed = new SplObjectStorage;
        $this->removed = new SplObjectStorage;
        $this->new = new SplObjectStorage;

        return $this->concreteMapper->reset();
    }

    public function __get($name)
    {
        return $this->concreteMapper->__get($name);
    }
    
    public function __isset($alias)
    {
        return $this->concreteMapper->__isset($alias);
    }

    public function __set($alias, $collection)
    {
        return $this->concreteMapper->__set($alias, $collection);
    }

    public function __call($name, $children)
    {
        return $this->concreteMapper->__call($name, $children);
    }
}