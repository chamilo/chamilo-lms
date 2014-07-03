<?php

namespace CG\Tests\Generator;

use CG\Generator\PhpParameter;

use CG\Generator\PhpFunction;

class PhpFunctionTest extends \PHPUnit_Framework_TestCase
{
    public function testSetGetName()
    {
        $func = new PhpFunction();

        $this->assertNull($func->getName());
        $this->assertSame($func, $func->setName('foo'));
        $this->assertEquals('foo', $func->getName());

        $func = new PhpFunction('foo');
        $this->assertEquals('foo', $func->getName());
    }

    public function testSetGetNamespace()
    {
        $func = new PhpFunction();

        $this->assertNull($func->getNamespace());
        $this->assertSame($func, $func->setNamespace('foo'));
        $this->assertEquals('foo', $func->getNamespace());
    }

    public function testSetGetBody()
    {
        $func = new PhpFunction();

        $this->assertSame('', $func->getBody());
        $this->assertSame($func, $func->setBody('foo'));
        $this->assertEquals('foo', $func->getBody());
    }

    public function testSetGetParameters()
    {
        $func = new PhpFunction();

        $this->assertEquals(array(), $func->getParameters());
        $this->assertSame($func, $func->setParameters(array($param = new PhpParameter())));
        $this->assertSame(array($param), $func->getParameters());
        $this->assertSame($func, $func->addParameter($param2 = new PhpParameter()));
        $this->assertSame(array($param, $param2), $func->getParameters());
        $this->assertSame($func, $func->replaceParameter(1, $param3 = new PhpParameter()));
        $this->assertSame(array($param, $param3), $func->getParameters());
        $this->assertSame($func, $func->removeParameter(0));
        $this->assertSame(array($param3), $func->getParameters());
    }

    public function testSetGetDocblock()
    {
        $func = new PhpFunction();

        $this->assertNull($func->getDocblock());
        $this->assertSame($func, $func->setDocblock('foo'));
        $this->assertEquals('foo', $func->getDocblock());
    }

    public function testSetIsReferenceReturned()
    {
        $func = new PhpFunction();

        $this->assertFalse($func->isReferenceReturned());
        $this->assertSame($func, $func->setReferenceReturned(true));
        $this->assertTrue($func->isReferenceReturned());
        $this->assertSame($func, $func->setReferenceReturned(false));
        $this->assertFalse($func->isReferenceReturned());
    }
}