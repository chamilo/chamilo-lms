The State API
=============

States
------

A LMS can use the xAPI to store documents associated to a certain state. A state
is dedicated to an activity, an actor, a state id and an optional registration
id (for example a user id):

```php
use Xabbuh\XApi\Model\State;

// ...
$state = new State();
$state->setActivity($activity);
$state->setActor($actor);
$state->setStateId($stateId);
```

Documents
---------

Documents are simple collections of key-value pairs and can be accessed like arrays:

```php
use Xabbuh\XApi\Model\StateDocument;

// ...
$document = new StateDocument();
$document->setState($state);
$document['x'] = 'foo';
$document['y'] = 'bar';
```

Obtaining the State API Client
------------------------------

After you have [built the global xAPI client](client.md), you can obtain a state
API client by calling its ``getStateApiClient()`` method:

```php
$stateApiClient = $xApiClient->getStateApiClient();
```

Storing State Documents
-----------------------

You can simply store a ``StateDocument`` passing it to the ``createOrUpdateStateDocument()``
method of the xAPI client:

```php
$document = ...; // the state document
$stateApiClient->createOrUpdateStateDocument($document);
```

If a document already exists for this state, the existing document will be updated.
This means that new fields will be updated, existing fields that are included in
the new document will be overwritten and existing fields that are not included in
the new document will be kept as they are.

If you want to replace a document, use the ``createOrReplaceStateDocument()`` method
instead:

```php
$document = ...; // the state document
$stateApiClient->createOrReplaceStateDocument($document);
```

Deleting State Documents
------------------------

A ``StateDocument`` is deleted by passing the particular ``State`` to the ``deleteStateDocument()``
method:

```php
$state = ...; // the state the document should be deleted from
$stateApiClient->deleteStateDocument($state);
```

Retrieving State Documents
--------------------------

Similarly, you receive a document for a particular state by passing the state to
the ``getStateDocument()`` method:

```php
$state = ...; // the state the document should be retrieved from
$document = $stateApiClient->getStateDocument($state);
```
