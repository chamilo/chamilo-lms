<?php

namespace CG\Tests\Core;

use CG\Generator\Writer;
use CG\Core\ReflectionUtils;

class ReflectionUtilsTest extends \PHPUnit_Framework_TestCase
{
    public function testGetOverridableMethods()
    {
        $ref = new \ReflectionClass('CG\Tests\Core\OverridableReflectionTest');
        $methods = ReflectionUtils::getOverrideableMethods($ref);

        $this->assertEquals(4, count($methods));

        $methods = array_map(function($v) { return $v->name; }, $methods);
        sort($methods);
        $this->assertEquals(array('a', 'd', 'e', 'h'), $methods);
    }

    public function testGetUnindentedDocComment()
    {
        $writer = new Writer();
        $comment = $writer
            ->writeln('/**')
            ->indent()
            ->writeln(' * Foo.')
            ->write(' */')
            ->getContent()
        ;

        $this->assertEquals("/**\n * Foo.\n */", ReflectionUtils::getUnindentedDocComment($comment));
    }
}

abstract class OverridableReflectionTest
{
    public function a() { }
    public final function b() { }
    public static function c() { }
    abstract public function d();
    protected function e() { }
    protected final function f() {}
    protected static function g() { }
    abstract protected function h();
    private function i() { }
}