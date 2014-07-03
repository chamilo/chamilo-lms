Templating Helpers for FOSMessageBundle
=======================================

MessageBundle provides a few twig functions::

```html+jinja
    {# template.html.twig #}

    {# Know if a message is read by the authenticated participant #}
    {% if not fos_message_is_read(message) %} This message is new! {% endif %}

    {# Know if a thread is read by the authenticated participant. Yes, it's the same function. #}
    {% if not fos_message_is_read(thread) %} This thread is new! {% endif %}

    {# Get the number of new threads for the authenticated participant #}
    You have {{ fos_message_nb_unread() }} new messages
```

[Return to the documentation index](00-index.md)
