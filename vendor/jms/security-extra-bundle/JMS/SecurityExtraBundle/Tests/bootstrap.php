<?php

call_user_func(function() {
    if ( ! is_file($autoloadFile = __DIR__.'/../vendor/autoload.php')) {
        throw new \LogicException('Could not find vendor/autoload.php. Did you run "composer install --dev"?');
    }

    require_once $autoloadFile;

    \Doctrine\Common\Annotations\AnnotationRegistry::registerLoader('class_exists');
});