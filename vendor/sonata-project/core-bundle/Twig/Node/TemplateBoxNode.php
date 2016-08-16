<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Twig\Node;

use Symfony\Component\Translation\TranslatorInterface;

class TemplateBoxNode extends \Twig_Node
{
    /**
     * @var int
     */
    protected $enabled;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * Constructor.
     *
     * @param \Twig_Node_Expression $message           Node message to display
     * @param \Twig_Node_Expression $translationBundle Node translation bundle to use for display
     * @param int                   $enabled           Is Symfony debug enabled?
     * @param TranslatorInterface   $translator        Symfony Translator service
     * @param null|string           $lineno            Symfony template line number
     * @param null                  $tag               Symfony tag name
     */
    public function __construct(\Twig_Node_Expression $message, \Twig_Node_Expression $translationBundle = null, $enabled, TranslatorInterface $translator, $lineno, $tag = null)
    {
        $this->enabled = $enabled;
        $this->translator = $translator;

        parent::__construct(array('message' => $message, 'translationBundle' => $translationBundle), array(), $lineno, $tag);
    }

    /**
     * {@inheritdoc}
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this);

        if (!$this->enabled) {
            $compiler->write("// token for sonata_template_box, however the box is disabled\n");

            return;
        }

        $value = $this->getNode('message')->getAttribute('value');

        $translationBundle = $this->getNode('translationBundle');

        if ($translationBundle) {
            $translationBundle = $translationBundle->getAttribute('value');
        }

        $message = <<<CODE
"<div class='alert alert-default alert-info'>
    <strong>{$this->translator->trans($value, array(), $translationBundle)}</strong>
    <div>{$this->translator->trans('sonata_core_template_box_file_found_in', array(), 'SonataCoreBundle')} <code>{\$this->getTemplateName()}</code>.</div>
</div>"
CODE;

        $compiler
            ->write("echo $message;");
    }
}
