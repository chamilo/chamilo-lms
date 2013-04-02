<?php

$loader = require __DIR__.'/../vendor/autoload.php';
$loader->add('Pagerfanta\Tests', __DIR__);

// fix for bad solarium autoloader in Solarium2: "Solarium" instead of "Solarium_"
$prefixes = $loader->getPrefixes();
if (isset($prefixes['Solarium'])) {
    $loader->add('Solarium_', $prefixes['Solarium']);
    $loader->set('Solarium', array());
}