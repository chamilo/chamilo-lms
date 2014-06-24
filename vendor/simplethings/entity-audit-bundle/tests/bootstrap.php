<?php

if (!($loader = @include __DIR__ . '/../vendor/autoload.php')) {
    die(<<<'EOT'
You must set up the project dependencies, run the following commands:
wget http://getcomposer.org/composer.phar
php composer.phar install
EOT
    );
}

if (version_compare(\Doctrine\ORM\Version::VERSION, '2.2.0') < 0) {
    require_once __DIR__ . '/../vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php';
}

\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(function ($class) use ($loader) {
    return $loader->loadClass($class);
});
