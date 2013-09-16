<?php

namespace Flint\Tests\Config\Normalizer;

use Flint\Config\Normalizer\PimpleAwareNormalizer;

class PimpleAwareNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->pimple = new \Pimple;
        $this->normalizer = new PimpleAwareNormalizer($this->pimple);
    }

    public function testItReplacesPlaceHolders()
    {
        $this->pimple['service_parameter'] = 'hello';

        $this->assertEquals('hello', $this->normalizer->normalize('%service_parameter%'));
        $this->assertEquals('%%hello', $this->normalizer->normalize('%%%service_parameter%'));
        $this->assertEquals('%%service_parameter%', $this->normalizer->normalize('%%service_parameter%'));
    }

    public function testItThrowsExceptionWhenReplacementIsObject()
    {
        $this->setExpectedException('\RuntimeException', 'Unable to replace placeholder if its replacement is an object or resource.');

        $this->pimple['replacement'] = new \stdClass;

        $this->normalizer->normalize('%replacement%');
    }

    public function testItReplacesPhpValuesToReadables()
    {

        $this->pimple['null_value'] = null;
        $this->pimple['false_value'] = false;
        $this->pimple['true_value'] = true;
        $this->pimple['float_value'] = 2.23;

        $this->assertEquals('null', $this->normalizer->normalize('%null_value%'));
        $this->assertEquals('false', $this->normalizer->normalize('%false_value%'));
        $this->assertEquals('true', $this->normalizer->normalize('%true_value%'));
        $this->assertEquals('2.23', $this->normalizer->normalize('%float_value%'));
    }
}
