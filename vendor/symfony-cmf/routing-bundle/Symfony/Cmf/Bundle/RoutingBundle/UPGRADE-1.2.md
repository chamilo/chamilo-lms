UPGRADE FROM 1.1 TO 1.2
=======================

Renamed the configuration option `dynamic.persistence.phpcr.route_basepath` to
`dynamic.persistence.phpcr.route_basepaths` and made it a list instead of a
single value. `route_basepath` is supported for BC but deprecated.

Refactored explicit properties for `addTrailingSlash` and `addFormatPattern`
into Route options. Use setOption/getOption with `add_trailing_slash` resp
`add_format_pattern` to interact with the options. The getters and setters are
kept for BC. If you have stored PHPCR Routes with these options activated, you
need to move the data into the options:

```php
/** @var $dm \Doctrine\ODM\PHPCR\DocumentManager */
$query = $dm->createPhpcrQuery("SELECT * FROM nt:base WHERE addFormatPattern = 'true'", QueryInterface::JCR_SQL2)
$routes = $dm->getDocumentsByPhpcrQuery($query);
/** @var $route \Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\Route */
foreach($routes as $route) {
    $route->setOption('add_format_pattern', true);
}

$query = $dm->createPhpcrQuery("SELECT * FROM nt:base WHERE addTrailingSlash = 'true'", QueryInterface::JCR_SQL2)
$routes = $dm->getDocumentsByPhpcrQuery($query);
/** @var $route \Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\Route */
foreach($routes as $route) {
    $route->setOption('add_trailing_slash', true);
}

$dm->flush();
```
