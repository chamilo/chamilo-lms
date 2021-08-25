The Activity Profile API
========================

Activity Profiles
-----------------

A LMS can use the xAPI to store documents associated to a certain activity using
activity profiles. An activity profile is dedicated to an activity and a profile
id:

```php
use Xabbuh\XApi\Model\ActivityProfile;

// ...
$profile = new ActivityProfile();
$profile->setActivity($activity);
$profile->setProfileId($profileId);
```

Documents
---------

Documents are simple collections of key-value pairs and can be accessed like arrays:

```php
use Xabbuh\XApi\Model\ActivityProfileDocument;

// ...
$document = new ActivityProfileDocument();
$document->setActivityProfile($profile);
$document['x'] = 'foo';
$document['y'] = 'bar';
```

Obtaining the Activity Profile API Client
-----------------------------------------

After you have [built the global xAPI client](client.md), you can obtain an activity
profile API client by calling its ``getActivityProfileApiClient()`` method:

```php
$activityProfileApiClient = $xApiClient->getActivityProfileApiClient();
```

Storing Activity Profile Documents
----------------------------------

You can simply store an ``ActivityProfileDocument`` passing it to the
``createOrUpdateActivityProfileDocument()`` method of the xAPI client:

```php
$document = ...; // the activity profile document
$activityProfileApiClient->createOrUpdateActivityProfileDocument($document);
```

If a document already exists for this activity profile, the existing document will
be updated. This means that new fields will be updated, existing fields that are
included in the new document will be overwritten and existing fields that are
not included in the new document will be kept as they are.

If you want to replace a document, use the ``createOrReplaceActivityProfileDocument()``
method instead:

```php
$document = ...; // the activity profile document
$activityProfileApiClient->createOrReplaceActivityProfileDocument($document);
```

Deleting Activity Profile Documents
-----------------------------------

An ``ActivityProfileDocument`` is deleted by passing the particular ``ActivityProfile``
to the ``deleteActivityProfileDocument()`` method:

```php
$profile = ...; // the activity profile the document should be deleted from
$activityProfileApiClient->deleteActivityProfileDocument($profile);
```

Retrieving Activity Profile Documents
-------------------------------------

Similarly, you receive a document for a particular activity profile by passing
the profile to the ``getActivityProfileDocument()`` method:

```php
$profile = ...; // the activity profile the document should be retrieved from
$document = $activityProfileApiClient->getActivityProfileDocument($profile);
```
