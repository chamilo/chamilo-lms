<?php

/*
 * This file is part of the Behat
 *
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

$filename = 'mink_extension.phar';

if (file_exists($filename)) {
    unlink($filename);
}

$phar = new \Phar($filename, 0, 'extension.phar');
$phar->setSignatureAlgorithm(\Phar::SHA1);
$phar->startBuffering();

foreach (findFiles('src') as $path) {
    $phar->addFromString($path, file_get_contents(__DIR__.'/'.$path));
}

$phar->addFromString('init.php', file_get_contents(__DIR__.'/init.php'));

$phar->setStub(<<<STUB
<?php

/*
 * This file is part of the Behat
 *
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

Phar::mapPhar('extension.phar');

return require 'phar://extension.phar/init.php';

__HALT_COMPILER();
STUB
);
$phar->stopBuffering();

function findFiles($dir) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir),
      RecursiveIteratorIterator::CHILD_FIRST);

    $files = array();
    foreach ($iterator as $path) {
      if ($path->isFile()) {
          $files[] = $path->getPath().DIRECTORY_SEPARATOR.$path->getFilename();
      }
    }

    return $files;
}
