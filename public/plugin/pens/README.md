PENS plugin
===

This plugin implements PENS in Chamilo, using the library php-pens available here: http://github.com/guillaumev/php-pens

Requirements
---

You need the curl extension installed to use this library.

How to install
---

Install the plugin and create a file at the root of your chamilo server called pens.php. Put the following code in the pens.php file you created:
```
require_once __DIR__ . '/plugin/pens/pens.php';
```

Licence
---

The chamilo-pens plugin is published under the GNU/GPLv3+ licence (see COPYING)

Credits
---

The original author of the plugin is Guillaume Viguier-Just <guillaume@viguierjust.com>.

This plugin was realized as part of his work in BeezNest (https://beeznest.com) and is maintained by BeezNest.
