base_initializr template
============

Example config

```yaml
# app/config/config.yml
mopa_bootstrap:
    initializr:
        meta:
            title:        "YYY"
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

Variables
------------


* **meta\_description**

    default value:  _empty_ _string_  
    parent block:  _[head](#head)_  

* **meta\_keywords**

    default value: _empty_ _string_  
    parent block: _[head](#head)_  

* **meta\_author_name**  

    default value: _empty_ _string_  
    parent block: _[head](#head)_  

* **meta\_author_url**

    default value: _empty_ _string_  
    parent block: _[head](#head)_  
    examples:  

    * _/humans.txt_  
    * _mailto:example@example.com_  
    * _http://example.com_  

* **meta\_nofollow**

    default value: _false_  
    parent block: _[head](#head)_  
    comment: set true to disable robots from following links  

* **meta\_noindex**

    default value: _false_  
    parent block: _[head](#head)_  
    comment: set true to disable robots from indexing page  

* **google\_wt**

    default value: _empty_ _string_  
    parent block: _[head](#head)_  
    comment: set Google Webmaster Tools veryfication code  

* **google\_analytics**

    default value: _empty_ _string_  
    parent block: _[foot\_scripts](#foot_scripts)_  
    comment: set Google Analytics UA page code  
* **diagnostic\_mode**

    default value: _false_
    parent block: _[head](#head)_  
    comment: set this to true to check your CSS for implementation errors  
        read more about used diagnostic file on: http://meyerweb.com/eric/tools/css/diagnostics/

Blocks
------------

* <span id="html_tag">html_tag</span>
* <span id="head">head</span>
    * <span id="dns_prefetch">dns\_prefetch</span>
    * <span id="head_style">head\_style</span>
    * <span id="head_scripts">head_scripts</span>
* <span id="body_tag">body_tag</span>
* <span id="body_start">body_start</span>
* <span id="body">body</span>
    * <span id="navbar">navbar</span>
    * <span id="content">content</span>
    * <span id="footer">footer</span>
    * <span id="foot_scripts">foot_scripts</span>
