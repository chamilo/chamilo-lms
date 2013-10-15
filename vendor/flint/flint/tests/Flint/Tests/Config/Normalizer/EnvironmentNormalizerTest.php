<?php

namespace Flint\Tests\Config\Normalizer;

use Flint\Config\Normalizer\EnvironmentNormalizer;

class EnvironmentNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testItReplacePlaceholders()
    {
        putenv('TEST_ENV=replaced');

        $normalizer = new EnvironmentNormalizer;

        $this->assertEquals('replaced', $normalizer->normalize('#TEST_ENV#'));
    }
}
