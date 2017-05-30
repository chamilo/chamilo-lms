<?php

/**
 * Simple chart stroking to check if the functionality itself works
 */

require __DIR__.'/../../../vendor/autoload.php';

use CpChart\Behat\Fixtures\FixtureGenerator;

$generator = new FixtureGenerator();

$image = $generator->createEmptyImage();
$generator->setSplineData($image);
$image->stroke();
