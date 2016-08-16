<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\Twig\TokenParser;

use Sonata\CoreBundle\Twig\Node\TemplateBoxNode;
use Sonata\CoreBundle\Twig\TokenParser\TemplateBoxTokenParser;

class TemplateBoxTokenParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getTestsForRender
     *
     * @param bool            $enabled
     * @param string          $source
     * @param TemplateBoxNode $expected
     *
     * @throws \Twig_Error_Syntax
     */
    public function testCompile($enabled, $source, $expected)
    {
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $env = new \Twig_Environment(new \Twig_Loader_Array(array()), array('cache' => false, 'autoescape' => false, 'optimizations' => 0));
        $env->addTokenParser(new TemplateBoxTokenParser($enabled, $translator));
        $stream = $env->tokenize($source);
        $parser = new \Twig_Parser($env);

        $actual = $parser->parse($stream)->getNode('body')->getNode(0);
        $this->assertSame(
            $expected->getIterator()->getFlags(),
            $actual->getIterator()->getFlags()
        );
        $this->assertSame(
            $expected->getLine(),
            $actual->getLine()
        );
        $this->assertSame(
            $expected->count(),
            $actual->count()
        );
    }

    public function getTestsForRender()
    {
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        return array(
            array(
                true,
                '{% sonata_template_box %}',
                new TemplateBoxNode(
                    new \Twig_Node_Expression_Constant('Template information', 1),
                    null,
                    true,
                    $translator,
                    1,
                    'sonata_template_box'
                ),
            ),
            array(
                true,
                '{% sonata_template_box "This is the basket delivery address step page" %}',
                new TemplateBoxNode(
                    new \Twig_Node_Expression_Constant('This is the basket delivery address step page', 1),
                    null,
                    true,
                    $translator,
                    1,
                    'sonata_template_box'
                ),
            ),
            array(
                false,
                '{% sonata_template_box "This is the basket delivery address step page" %}',
                new TemplateBoxNode(
                    new \Twig_Node_Expression_Constant('This is the basket delivery address step page', 1),
                    null,
                    false,
                    $translator,
                    1,
                    'sonata_template_box'
                ),
            ),
        );
    }
}
