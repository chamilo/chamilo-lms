Migrating from vObject 2.x to 3.x
=================================

vObject 3.0 got a major overhaul, and much better built-in support for all
kinds of properties and escaping.

This version fixes the most important bugs, specifically Issue #19.

To do this, a few backwards compatibility breaks had to be made. This document
describes each of them, as well as all the new features.

New features overview
---------------------

* Serializer now properly deals with escaped commas and semi-colons.
* Properties and Parameters now have a getParts() method to grab multiple
  values.
* You can now simply set PHP DateTime objects on DATE-TIME properties. 
* Properties such as `CALSCALE`, `VERSION` and `PRODID` will automatically be
  added.
* [RFC6868][1] is used to serialize parameters.
* Methods to generate [jCard][2] and [jCal][3] objects.
* Parsing of vCard 2.1 is much, much better, including support for the broken
  vCards Microsoft generates if the `FORGIVING` option is on.
* The `add()` methods now return the objects that have been created.
* A brand new parser that reads from streams, lowering memory usage.
* Components now have an easy to use `remove()` method.
* Every property, parameter and component has a reference to the document.
* Binary properties are automatically decoded.

And since sabre/vobject 3.1:

* Added a jCard and jCal parser.
* Using the `convert()` method you can convert between vCard 2.1, 3.0 and 4.0.
* A new cli tool with `validate`, `repair`, `color` and `convert` commands.

To find how out it all works, check out the [Documentation][4].

Backwards compatibility breaks
------------------------------

### Creating components

Before, it was possible to create components such as `VEVENT`, `VTODO`, etc
with this syntax:

```php
<?php

$event = \Sabre\VObject\Component::create('VEVENT');
$event->summary = 'Birthday party!';

// Or:

$event = new \Sabre\VObject\Component('VEVENT');
$event->summary = 'Birthday party!';

?>
```

Neither of those are legal any longer. Components now _must_ be created through
the Document object.

Example:

```php
<?php

    $vcalendar = new \Sabre\VObject\Component\VCalendar();
    $event = $vcalendar->createComponent('VEVENT');
    $event->summary = 'Birthday party!';

?>
```

Or:

```php
$vcalendar = new \Sabre\VObject\Component\VCalendar();
$vcalendar->add('VEVENT', [
    'summary' => 'Birthday party!',
]);
```

### Creating properties

Creating properties works the _exact_ same way.

Old:

```php
<?php

$location = new \Sabre\VObject\Property('LOCATION', 'Home');

// Or:

$location = \Sabre\VObject\Property::create('LOCATION', 'Home');

?>
```

Now you must also use the document:

Example:

```php
<?php

$card = new \Sabre\VObject\Component\VCard();
$location = $card->createProperty('LOCATION','Home');

?>
```

Note that in most cases, this syntax is highly recommended instead:

```php
<?php

$card = new \Sabre\VObject\Component\VCard();
$location = $card->add('LOCATION','Home');

?>
```

In this case it doesn't make much of a difference, but when constructing
highly complex objects with sub-components, this _will_ make a big difference.

### Component::children() and Property::parameters() return arrays.

Before vObject 3 they returned an `ElementList`.

### The signature for DateTime::setDateTime has changed.

Before, you would use the following 4 syntaxes to set the date and time:

```php
<?php

use Sabre\VObject\Property;

$now = new DateTime('now');

$dt = new Property\DateTime('DTSTART');
$dt->setDateTime($now, Property\DateTime::DATE); // Date only
$dt->setDateTime($now, Property\DateTime::LOCAL); // Floating time
$dt->setDateTime($now, Property\DateTime::UTC); // Convert to UTC
$dt->setDateTime($now, Property\DateTime::LOCALTZ); // Local to timezone information.

```

This has completely changed:

```php
<?php

// Date only
$now = new DateTime('now');
$dt = $calendar->create('DTSTART');
$dt->setValue($now);
$dt['VALUE'] = 'date';

// Floating time
$dt = $calendar->create('DTSTART');
$dt->setValue($now, $floating = true);

// Convert to UTC
$now->setTimeZone(\DateTimeZone('UTC'));
$dt = $calendar->create('DTSTART');
$dt->setValue($now);

// Local time + timezone information
$dt = $calendar->create('DTSTART');
$dt->setValue($now);

?>
```

Note that the preceeding examples are all a bit convoluted.
In most cases you just want to do something like:

```php
<?php

// Assuming this a vevent
$event->DTSTART = $now;

?>
```

In addition, the `MultiDateTime` property is no more, and it's methods are
simply merged into `DateTime`.

### The $value property is now protected everywhere.

Both the `Property` and the `Parameter` classes had a public `$value` property,
which allowed you to retrieve the string value for either of those.

This is now protected, so you must access it in this manner:

```php
<?php
// Assuming $prop is a property object.

$prop->setValue('Birthday');
echo $prop->getValue();

?>
```

// For properties that have more than 1 value, you can use `setParts` and
`getParts`:


```php
<?php

$org->setParts(['Company', 'Department']);
print_r($org->getParts());

?>
```

### Binary properties are automatically de- and encoded.

The `ATTACH`, `LOGO` and `PHOTO` properties now automatically de- and encode
their binary values. In vObject 2 they were accessed by their raw base64
values.

### Components and documents get injected with default properties.

When creating a new `VCalendar`, it will automatically get the `VERSION`,
`CALSCALE` and `PRODID` properties.

If you were adding your own with this syntax:

```php
<?php

$calendar = new Sabre\VObject\Component\VCalendar();
$calendar->VERSION = '2.0';

?>
```

Then nothing will go wrong, and the properties will simply be overwritten.
However, if you used `add()` before in this manner:

```php
<?php

$calendar = new Sabre\VObject\Component\VCalendar();
$calendar->add('version', '2.0');

?>
```

You will end up with 2 VERSION properties, making the document invalid.

### componentMap and propertyMap properties have moved.

When you wanted to automatically map certain components or properties to
certain PHP classes, you could do so with `Component::$componentMap` and
`Property::$propertyMap`.

These properties have now moved to the document classes:

* `Component\VCalendar::$propertyMap`
* `Component\VCalendar::$componentMap`
* `Component\VCard::$propertyMap`
* `Component\VCard::$componentMap`

[1]: http://tools.ietf.org/html/rfc6868
[2]: http://tools.ietf.org/html/rfc7095
[3]: http://tools.ietf.org/html/draft-ietf-jcardcal-jcal-08
[4]: usage_3.md
