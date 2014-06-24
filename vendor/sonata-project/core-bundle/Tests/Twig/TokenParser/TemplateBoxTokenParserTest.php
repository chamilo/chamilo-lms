<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\Twig\TokenParser;

use Sonata\CoreBundle\Twig\TokenParser\TemplateBoxTokenParser;
use Sonata\CoreBundle\Twig\Node\TemplateBoxNode;

class TemplateBoxTokenParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getTestsForRender
     */
    public function testCompile($enabled, $source, $expected)
    {
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $env = new \Twig_Environment(new \Twig_Loader_String(), array('cache' => false, 'autoescape' => false, 'optimizations' => 0));
        $env->addTokenParser(new TemplateBoxTokenParser($enabled, $translator));
        $stream = $env->tokenize($source);
        $parser = new \Twig_Parser($env);

        $this->assertEquals($expected, $parser->parse($stream)->getNode('body')->getNode(0));
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
                )
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
                )
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
                )
            ),
        );
    }
}
