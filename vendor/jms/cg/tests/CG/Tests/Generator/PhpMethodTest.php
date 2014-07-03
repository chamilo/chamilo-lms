<?php

namespace CG\Tests\Generator;

use CG\Generator\PhpParameter;

use CG\Generator\PhpMethod;

class PhpMethodTest extends \PHPUnit_Framework_TestCase
{
    public function testSetIsFinal()
    {
        $method = new PhpMethod();

        $this->assertFalse($method->isFinal());
        $this->assertSame($method, $method->setFinal(true));
        $this->assertTrue($method->isFinal());
        $this->assertSame($method, $method->setFinal(false));
        $this->assertFalse($method->isFinal());
    }

    public function testSetIsAbstract()
    {
        $method = new PhpMethod();

        $this->assertFalse($method->isAbstract());
        $this->assertSame($method, $method->setAbstract(true));
        $this->assertTrue($method->isAbstract());
        $this->assertSame($method, $method->setAbstract(false));
        $this->assertFalse($method->isAbstract());
    }

    public function testSetGetParameters()
    {
        $method = new PhpMethod();

        $this->assertEquals(array(), $method->getParameters());
        $this->assertSame($method, $method->setParameters($params = array(new PhpParameter())));
        $this->assertSame($params, $method->getParameters());

        $this->assertSame($method, $method->addParameter($param = new PhpParameter()));
        $params[] = $param;
        $this->assertSame($params, $method->getParameters());

        $this->assertSame($method, $method->removeParameter(0));
        unset($params[0]);
        $this->assertSame(array($param), $method->getParameters());

        $this->assertSame($method, $method->addParameter($param = new PhpParameter()));
        $params[] = $param;
        $params = array_values($params);
        $this->assertSame($params, $method->getParameters());
    }

    public function testSetGetBody()
    {
        $method = new PhpMethod();

        $this->assertSame('', $method->getBody());
        $this->assertSame($method, $method->setBody('foo'));
        $this->assertEquals('foo', $method->getBody());
    }

    public function testSetIsReferenceReturned()
    {
        $method = new PhpMethod();

        $this->assertFalse($method->isReferenceReturned());
        $this->assertSame($method, $method->setReferenceReturned(true));
        $this->assertTrue($method->isReferenceReturned());
        $this->assertSame($method, $method->setReferenceReturned(false));
        $this->assertFalse($method->isReferenceReturned());
    }
}