<?php

namespace CG\Tests\Core;

use CG\Core\DefaultGeneratorStrategy;

use CG\Generator\PhpProperty;
use CG\Generator\PhpMethod;
use CG\Generator\PhpClass;

class DefaultGeneratorStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerate()
    {
        $strategy = new DefaultGeneratorStrategy();
        $strategy->setConstantSortFunc(function($a, $b) {
            return strcasecmp($a, $b);
        });
        $strategy->setMethodSortFunc($func = function($a, $b) {
            return strcasecmp($a->getName(), $b->getName());
        });
        $strategy->setPropertySortFunc($func);

        $this->assertEquals(
            $this->getContent('GenerationTestClass_A.php'),
            $strategy->generate($this->getClass())
        );
    }

    public function testGenerateChangedConstantOrder()
    {
        $strategy = new DefaultGeneratorStrategy();
        $strategy->setConstantSortFunc(function($a, $b) {
            return -1 * strcasecmp($a, $b);
        });
        $strategy->setMethodSortFunc($func = function($a, $b) {
            return strcasecmp($a->getName(), $b->getName());
        });
        $strategy->setPropertySortFunc($func);

        $this->assertEquals(
            $this->getContent('GenerationTestClass_B.php'),
            $strategy->generate($this->getClass())
        );
    }

    private function getContent($file)
    {
        return file_get_contents(__DIR__.'/generated/'.$file);
    }

    private function getClass()
    {
        $class = PhpClass::create()
            ->setName('GenerationTestClass')
            ->setMethod(PhpMethod::create('a'))
            ->setMethod(PhpMethod::create('b'))
            ->setProperty(PhpProperty::create('a'))
            ->setProperty(PhpProperty::create('b'))
            ->setConstant('a', 'foo')
            ->setConstant('b', 'bar')
        ;

        return $class;
    }
}