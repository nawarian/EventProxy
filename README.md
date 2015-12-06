Respect\EventProxy
--------------------

A proxy for dealing with respect/data pre/post insert, update and delete events.

```php
// Check out this sample!
$mapper = new Respect\Relational\Mapper($db);

// We'll use this object as mapper to the application
$proxy = new Respect\EventProxy\Mapper($mapper);

$proxy->on('pre.update', function($event) {
    var_dump(
        get_class($event), // Respect\EventProxy\Event\Update
        $event->getEntity(), // Object to be updated
        $event->getCollection()->getName() // Collection name
    );
});

$person = $proxy->person[1]->fetch();
$person->name = 'Nawarian';

$proxy->person->persist($person);
$proxy->flush(); // Calls pre.update automatically
```

You can also get and/or set Symfony's Event Dispatcher on the fly:

```php
$d = $proxy->getEventDispatcher(); // instance of use Symfony\Component\EventDispatcher\Event;

// Using full qualified event name
$d->addListener('respect.post.update', function($event) {
    var_dump(
        get_class($event), // Respect\EventProxy\Event\Update
        $event->getEntity(), // Updated object
        $event->getCollection()->getName() // Collection name
    );
});
```