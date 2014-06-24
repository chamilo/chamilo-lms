Sending a message programatically
=================================

This bundle comes with a set of forms that already should make it easy for your
users to start sending messages to one another. Sometimes however you want to be
able to send a message from your code without any form being involved.

- A welcome message
- A message thread for a team that was just created
- A service notification message

Composing a message
-------------------

The service container contains a service to compose messages and one to send them.
This is probably all you will need in many cases.

To compose a message we retrieve the composer service and compose our message:

```php
    $sender = $this->get('security.context')->getToken()->getUser();
    $threadBuilder = $this->get('fos_message.composer')->newThread();
    $threadBuilder
        ->addRecipient($recipient) // Retrieved from your backend, your user manager or ...
        ->setSender($sender)
        ->setSubject('Stof commented on your pull request #456789')
        ->setBody('You have a typo, : mondo instead of mongo. Also for coding standards ...');
```

Sending a message
-----------------

Now all you have to do to send your message is get the sender and tell it to send

```php
    $sender = $this->get('fos_message.sender');
    $sender->send($threadBuilder->getMessage());
```

That's it, your message should now have been sent
