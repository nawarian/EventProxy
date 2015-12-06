<?php

namespace Respect\EventProxy\Event;

use Respect\Data\Collections\Collection;

class Update extends AbstractEvent
{
    public function getEventFullQualifiedName()
    {
        $moment = $this->isPre() ? 'pre' : 'post';
        return "respect.{$moment}.update";
    }
}