Messaging permissions
======================

The default permissions authorizer service will authenticate a user if they're a
participant of the thread and is very permissive by default.

You can implement your own permissions service to replace the built in service and tell
FOSMessageBundle about it:

```yaml
# app/config/config.yml

fos_message:
    authorizer: acme_message.authorizer
```

Any such service must implement `FOS\MessageBundle\Security\AuthorizerInterface`.

[Return to the documentation index](00-index.md)
