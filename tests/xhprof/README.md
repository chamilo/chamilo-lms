# XHProf / Tideways

Previously the XHProf library was developed by Facebook. Since they moved to
HHVM, they have dropped support for the library and several projects have
forked it, between other reasons to provide support for PHP 7.

## Install procedure

To enable the profiler into Chamilo, you will need to do the following:
- install the php[version]-tideways library from https://tideways.io/profiler/downloads or your package manager
- add the following two lines to your Apache VirtualHost or (in a slightly different form) to your php.ini config
  (don't forget to update the path to your Chamilo root directory):
```
    php_value auto_prepend_file /var/www/chamilo/tests/xhprof/header.php
    php_value auto_append_file /var/www/chamilo/tests/xhprof/footer.php
```
- restart your PHP interpreter (Apache or PHP-FPM, in most cases)
- modify Chamilo's .htaccess file to comment (temporarily) the tests/ directory line, like so
```
# Deny access
#RewriteRule ^(tests|.git) - [F,L,NC]
RewriteRule ^.git - [F,L,NC]
```

## Using XHProf

Once you've done all the above, reload any Chamilo page.
You should now see a little link at the bottom left of the page (under the footer).
Click the link to see the details of the page load as seen by the profiler.