<?php

namespace Flint\Tests\Config\Normalizer;

use Flint\Config\Normalizer\ChainNormalizer;

class ChainNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testItNormalizesAChainOfNormalizers()
    {
        $normalizer = new ChainNormalizer;
        $normalizer->add($this->createNormalizerMock('original content', 'first modified'));
        $normalizer->add($this->createNormalizerMock('first modified', 'final content'));

        $this->assertEquals('final content', $normalizer->normalize('original content'));
    }

    protected function createNormalizerMock($contents, $return)
    {
        $mock = $this->getMock('Flint\Config\Normalizer\NormalizerInterface');
        $mock->expects($this->once())->method('normalize')->with($contents)
            ->will($this->returnValue($return));

        return $mock;
    }
}
