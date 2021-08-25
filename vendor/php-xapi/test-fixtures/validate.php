<?php

include __DIR__.'/vendor/autoload.php';

$status = 0;
$typeMap = array(
    'Xabbuh\XApi\DataFixtures\StatementFixtures::getStatementCollection' => 'array',
    'Xabbuh\XApi\DataFixtures\UuidFixtures' => 'string',
);

foreach (glob(__DIR__.'/src/*.php') as $path) {
    $filename = substr(basename($path), 0, -4);
    $fixturesClassName = 'Xabbuh\XApi\DataFixtures\\'.$filename;

    foreach (get_class_methods($fixturesClassName) as $method) {
        $object = call_user_func(array($fixturesClassName, $method));

        if (isset($typeMap[$fixturesClassName.'::'.$method])) {
            $type = $typeMap[$fixturesClassName.'::'.$method];
        } elseif (isset($typeMap[$fixturesClassName])) {
            $type = $typeMap[$fixturesClassName];
        } else {
            $type = 'object';
        }

        if (gettype($object) !== $type) {
            file_put_contents('php://stderr', sprintf(
                'Expected %s::%s to return data of type "%s", but got "%s"'.PHP_EOL,
                $filename,
                $method,
                $type,
                gettype($object)
            ));
            $status = 1;
        }

    }
}

exit($status);
