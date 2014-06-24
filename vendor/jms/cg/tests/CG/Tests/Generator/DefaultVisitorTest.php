<?php

namespace CG\Tests\Generator;

use CG\Generator\DefaultVisitor;
use CG\Generator\PhpParameter;
use CG\Generator\Writer;
use CG\Generator\PhpFunction;

class DefaultVisitorTest extends \PHPUnit_Framework_TestCase
{
    public function testVisitFunction()
    {
        $writer = new Writer();

        $function = new PhpFunction();
        $function
            ->setName('foo')
            ->addParameter(PhpParameter::create('a'))
            ->addParameter(PhpParameter::create('b'))
            ->setBody(
                $writer
                    ->writeln('if ($a === $b) {')
                    ->indent()
                    ->writeln('throw new \InvalidArgumentException(\'$a is not allowed to be the same as $b.\');')
                    ->outdent()
                    ->write("}\n\n")
                    ->write('return $b;')
                    ->getContent()
            )
        ;

        $visitor = new DefaultVisitor();
        $visitor->visitFunction($function);

        $this->assertEquals($this->getContent('a_b_function.php'), $visitor->getContent());
    }

    private function getContent($filename)
    {
        if (!is_file($path = __DIR__.'/Fixture/generated/'.$filename)) {
            throw new \InvalidArgumentException(sprintf('The file "%s" does not exist.', $path));
        }

        return file_get_contents($path);
    }
}