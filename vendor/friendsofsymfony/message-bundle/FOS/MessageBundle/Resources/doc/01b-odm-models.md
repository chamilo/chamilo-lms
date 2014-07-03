Concrete classes for Doctrine ODM
=================================

This page lists some example implementations of FOSMessageBundle models for the Doctrine
MongoDB ODM.

Given the examples below with their namespaces and class names, you need to configure
FOSMessageBundle to tell them about these classes.

Add the following to your `app/config/config.yml` file.

```yaml
# app/config/config.yml

fos_message:
    db_driver: mongodb
    thread_class: Acme\MessageBundle\Document\Thread
    message_class: Acme\MessageBundle\Document\Message
```

[Continue with the installation][]

Message class
-------------

```php
<?php
// src/Acme/MessageBundle/Document/Message.php

namespace Acme\MessageBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use FOS\MessageBundle\Document\Message as BaseMessage;

/**
 * @MongoDB\Document
 */
class Message extends BaseMessage
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\EmbedMany(targetDocument="Acme\MessageBundle\Document\MessageMetadata")
     */
    protected $metadata;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Acme\MessageBundle\Document\Thread")
     */
    protected $thread;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Acme\UserBundle\Document\User")
     */
    protected $sender;
}
```

MessageMetadata class
---------------------

```php
<?php

namespace Mashup\MessageBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use FOS\MessageBundle\Document\MessageMetadata as BaseMessageMetadata;

/**
 * @ODM\EmbeddedDocument
 */
class MessageMetadata extends BaseMessageMetadata
{
    /**
     * @ODM\ReferenceOne(targetDocument="Mashup\UserBundle\Document\User")
     */
    protected $participant;
}
```

Thread class
------------

```php
<?php
// src/Acme/MessageBundle/Document/Thread.php

namespace Acme\MessageBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use FOS\MessageBundle\Document\Thread as BaseThread;

/**
 * @MongoDB\Document
 */
class Thread extends BaseThread
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\ReferenceMany(targetDocument="Acme\MessageBundle\Document\Message")
     */
    protected $messages;

    /**
     * @MongoDB\EmbedMany(targetDocument="Acme\MessageBundle\Document\ThreadMetadata")
     */
    protected $metadata;

    /**
     * @MongoDB\ReferenceMany(targetDocument="Acme\UserBundle\Document\User")
     */
    protected $participants;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Acme\UserBundle\Document\User")
     */
    protected $createdBy;
}
```

ThreadMetadata class
--------------------

```php
<?php

namespace Mashup\MessageBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use FOS\MessageBundle\Document\ThreadMetadata as BaseThreadMetadata;

/**
 * @ODM\EmbeddedDocument
 */
class ThreadMetadata extends BaseThreadMetadata
{
    /**
     * @ODM\ReferenceOne(targetDocument="Mashup\UserBundle\Document\User")
     */
    protected $participant;
}
```

[Continue with the installation]: 01-installation.md
