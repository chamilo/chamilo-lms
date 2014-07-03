Bootstrap Extras (Initializr)
================

HOW TO USE IT?
--------------
To make fast start there is only 2 step process to use it.

1. add to config.yml:  

```yaml
# app/config/config.yml
mopa_bootstrap:
    initializr: ~
```

2. extend base_initializr template by your layout template (add this line as first one)  

```twig
{# src/Acme/DemoBundle/Resources/views/layout.html.twig #}
{% extends 'MopaBootstrapBundle::base_initializr.html.twig' %}
```

HOW TO SET IT UP?
--------

Example config file:

```yaml
# app/config/config.yml
mopa_bootstrap:
    initializr:
        meta:
            title:        "Some Title"
            description:  "This is test site"
            keywords:     "keyword1,keyword 2"
            author_name:  "this is me"
            author_url:   "/human.txt"
            nofollow:     false
            noindex:      false
        dns_prefetch:
            - '//ajax.googleapis.com'
        google:
            wt: 'xxx'
            analytics: 'UA-xxxxxxx-xx'
        diagnostic_mode: true
```

All variables description is available in file [51-Initializr-variables.md](51-initializr-variables.md)

TODO
------
Below is list of things that need to be done in near future:

* allow all variables to be overriden by controller
* add rss/atom sources
