Basic Usage of FOSMessageBundle
===============================

Basic operations involving FOSMessageBundle can be seen in the
`Controller/MessageController` file.

Get user threads
----------------

Get the threads in the inbox of the authenticated user::

```php
    $provider = $container->get('fos_message.provider');

    $threads = $provider->getInboxThreads();
```

And the threads in the sentbox::

```php
    $threads = $provider->getSentThreads();
```

To get a single thread, check it belongs to the authenticated user and mark it as read::

```php
    $thread = $provider->getThread($threadId);
```

Manipulate threads
------------------

See `FOS\\MessageBundle\\Model\\ThreadInterface` for the complete list of available methods::

```php
    // Print the thread subject
    echo $thread->getSubject();

    // Get the tread participants
    $participants = $thread->getParticipants();

    // Know if this participant has read this thread
    if ($thread->isReadByParticipant($participant))

    // Know if this participant has deleted this thread
    if ($thread->isDeletedByParticipant($participant))
```

Manipulate messages
-------------------

See ``FOS\\MessageBundle\\Model\\MessageInterface`` for the complete list of available methods::

```php
    // Print the message body
    echo $message->getBody();

    // Get the message sender participant
    $sender = $message->getSender();

    // Get the message thread
    $thread = $message->getThread();

    // Know if this participant has read this message
    if ($message->isReadByParticipant($participant))
```

Compose a message
--------------

Create a new message thread::

```php
    $composer = $container->get('fos_message.composer');

    $message = $composer->newThread()
        ->setSender($jack)
        ->addRecipient($clyde)
        ->setSubject('Hi there')
        ->setBody('This is a test message')
        ->getMessage();
```

And to reply to this thread::

```php
    $message = $composer->reply($thread)
        ->setSender($clyde)
        ->setBody('This is the answer to the test message')
        ->getMessage();
```

Note that when replying, we don't need to provide the subject nor the recipient.
Because they are the attributes of the thread, which already exists.

Send a message
--------------

Nothing's easier than sending the message you've just composed::

```php
    $sender = $container->get('fos_message.sender');

    $sender->send($message);
```php

Number of Unread Messages
-------------------------

You can return the number of unread messages for the authenticated user with::

```php
    $provider = $container->get('fos_message.provider');

    $provider->getNbUnreadMessages()
```

Will return an integer, the number of unread messages.

[Return to the documentation index](00-index.md)
