Opauth-OpenID
=============
[Opauth][1] strategy for OpenID.

Opauth is a multi-provider authentication framework for PHP.

Getting started
----------------
1. Install Opauth-OpenID:
   ```bash
   cd path_to_opauth/Strategy
   git clone git://github.com/uzyn/opauth-openid.git OpenID
   ```
   
2. Configure Opauth-Google strategy. _(see next section)_

3. Direct user to `http://path_to_opauth/openid` to authenticate


Strategy configuration
----------------------

Opauth-OpenID requires **zero configurations**. It just needs to be defined along with the list of strategies.

```php
<?php
'OpenID' => array(),
```

Optional parameters:

- `required` - Required [OpenID attributes](http://openid.net/specs/openid-attribute-properties-list-1_0-01.html).

- `optional` - Optional OpenID attributes.

- `identifier_form` - complete path to HTML or PHP view renders the form requesting for OpenID identifier.

Credits
-------
Opauth-OpenID includes Mewp's [LightOpenID library](https://gitorious.org/lightopenid/lightopenid).  
LightOpenID library is Copyright (c) 2010, Mewp and MIT licensed.

License
---------
Opauth-OpenID is MIT Licensed  
Copyright © 2012 U-Zyn Chua (http://uzyn.com)

[1]: https://github.com/uzyn/opauth