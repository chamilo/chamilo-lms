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

use Sonata\CoreBundle\Twig\Node\TemplateBoxNode;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Translator;

class TemplateBoxNodeTest extends \Twig_Test_NodeTestCase
{
    public function testConstructor()
    {
        $translator = $this->getTranslator('en');

        $body = new TemplateBoxNode(
            new \Twig_Node_Expression_Constant('This is the default message', 1),
            new \Twig_Node_Expression_Constant('SonataCoreBundle', 1),
            true,
            $translator,
            1,
            'sonata_template_box'
        );

        $this->assertEquals(1, $body->getLine());
    }

    /**
     * @covers Twig_Node_Block::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
        $translator = $this->getTranslator('en');

        $nodeEn = new TemplateBoxNode(
            new \Twig_Node_Expression_Constant('This is the default message', 1),
            new \Twig_Node_Expression_Constant('SonataCoreBundle', 1),
            true,
            $translator,
            1,
            'sonata_template_box'
        );

        $translator = $this->getTranslator('fr');

        $nodeFr = new TemplateBoxNode(
            new \Twig_Node_Expression_Constant('Ceci est le message par défaut', 1),
            new \Twig_Node_Expression_Constant('SonataCoreBundle', 1),
            true,
            $translator,
            1,
            'sonata_template_box'
        );

        return array(
            array($nodeEn, <<<EOF
// line 1
echo "<div class='alert alert-default alert-info'>
    <strong>This is the default message</strong>
    <div>This file can be found in <code>{\$this->getTemplateName()}</code>.</div>
</div>";
EOF
            ),
            array($nodeFr, <<<EOF
// line 1
echo "<div class='alert alert-default alert-info'>
    <strong>Ceci est le message par défaut</strong>
    <div>Ce fichier peut être trouvé à l'emplacement <code>{\$this->getTemplateName()}</code>.</div>
</div>";
EOF
            ),
        );
    }

    /**
     * Returns a Translator instance
     *
     * @param string $locale
     *
     * @return Translator
     */
    public function getTranslator($locale)
    {
        $translator = new Translator($locale, new MessageSelector());
        $translator->addLoader('array', new ArrayLoader());

        $translator->addResource('array', array('sonata_template_box_media_gallery_block' => 'This is the default message'), 'en', 'SonataCoreBundle');
        $translator->addResource('array', array('sonata_template_box_media_gallery_block' => 'Ceci est le message par défaut'), 'fr', 'SonataCoreBundle');
        $translator->addResource('array', array('sonata_core_template_box_file_found_in' => 'This file can be found in'), 'en', 'SonataCoreBundle');
        $translator->addResource('array', array('sonata_core_template_box_file_found_in' => "Ce fichier peut être trouvé à l'emplacement"), 'fr', 'SonataCoreBundle');

        return $translator;
    }
}