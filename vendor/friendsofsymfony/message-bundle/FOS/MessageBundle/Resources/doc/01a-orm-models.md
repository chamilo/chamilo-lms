Concrete classes for Doctrine ORM
=================================

This page lists some example implementations of FOSMessageBundle models for the Doctrine
ORM.

Given the examples below with their namespaces and class names, you need to configure
FOSMessageBundle to tell them about these classes.

Add the following to your `app/config/config.yml` file.

```yaml
# app/config/config.yml

fos_message:
    db_driver: orm
    thread_class: Acme\MessageBundle\Entity\Thread
    message_class: Acme\MessageBundle\Entity\Message
```

[Continue with the installation][]

Message class
-------------

```php
<?php
// src/Acme/MessageBundle/Entity/Message.php

namespace Acme\MessageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\MessageBundle\Entity\Message as BaseMessage;

/**
 * @ORM\Entity
 */
class Message extends BaseMessage
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(
     *   targetEntity="Acme\MessageBundle\Entity\Thread",
     *   inversedBy="messages"
     * )
     * @var ThreadInterface
     */
    protected $thread;

    /**
     * @ORM\ManyToOne(targetEntity="Acme\UserBundle\Entity\User")
     * @var ParticipantInterface
     */
    protected $sender;

    /**
     * @ORM\OneToMany(
     *   targetEntity="Acme\MessageBundle\Entity\MessageMetadata",
     *   mappedBy="message",
     *   cascade={"all"}
     * )
     * @var MessageMetadata
     */
    protected $metadata;
}
```

MessageMetadata class
---------------------

```php
<?php
// src/Acme/MessageBundle/Entity/MessageMetadata.php

namespace Acme\MessageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\MessageBundle\Entity\MessageMetadata as BaseMessageMetadata;
use FOS\MessageBundle\Model\MessageInterface;
use FOS\MessageBundle\Model\ParticipantInterface;

/**
 * @ORM\Entity
 */
class MessageMetadata extends BaseMessageMetadata
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(
     *   targetEntity="Acme\MessageBundle\Entity\Message",
     *   inversedBy="metadata"
     * )
     * @var MessageInterface
     */
    protected $message;

    /**
     * @ORM\ManyToOne(targetEntity="Acme\UserBundle\Entity\User")
     * @var ParticipantInterface
     */
    protected $participant;
}
```

Thread class
------------

```php
<?php
// src/Acme/MessageBundle/Entity/Thread.php

namespace Acme\MessageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\MessageBundle\Entity\Thread as BaseThread;

/**
 * @ORM\Entity
 */
class Thread extends BaseThread
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Acme\UserBundle\Entity\User")
     */
    protected $createdBy;

    /**
     * @ORM\OneToMany(
     *   targetEntity="Acme\MessageBundle\Entity\Message",
     *   mappedBy="thread"
     * )
     * @var Message[]|\Doctrine\Common\Collections\Collection
     */
    protected $messages;

    /**
     * @ORM\OneToMany(
     *   targetEntity="Acme\MessageBundle\Entity\ThreadMetadata",
     *   mappedBy="thread",
     *   cascade={"all"}
     * )
     * @var ThreadMetadata[]|\Doctrine\Common\Collections\Collection
     */
    protected $metadata;
}
```

ThreadMetadata class
--------------------

```php
<?php
// src/Acme/MessageBundle/Entity/ThreadMetadata.php

namespace Acme\MessageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\MessageBundle\Entity\ThreadMetadata as BaseThreadMetadata;

/**
 * @ORM\Entity
 */
class ThreadMetadata extends BaseThreadMetadata
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(
     *   targetEntity="Acme\MessageBundle\Entity\Thread",
     *   inversedBy="metadata"
     * )
     * @var ThreadInterface
     */
    protected $thread;

    /**
     * @ORM\ManyToOne(targetEntity="Acme\UserBundle\Entity\User")
     * @var ParticipantInterface
     */
    protected $participant;
}
```

[Continue with the installation]: 01-installation.md
