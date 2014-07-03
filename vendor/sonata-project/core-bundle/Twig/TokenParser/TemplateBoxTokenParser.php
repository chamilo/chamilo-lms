<?php

/*
 * This file is part of sonata-project.
 *
 * (c) 2010 Thomas Rabaix
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Twig\TokenParser;

use Sonata\CoreBundle\Twig\Node\TemplateBoxNode;
use Symfony\Component\Translation\TranslatorInterface;

class TemplateBoxTokenParser extends \Twig_TokenParser
{
    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * Constructor
     *
     * @param bool                $enabled    Is Symfony debug enabled?
     * @param TranslatorInterface $translator Symfony Translator service
     */
    public function __construct($enabled, TranslatorInterface $translator)
    {
        $this->enabled    = $enabled;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(\Twig_Token $token)
    {
        if ($this->parser->getStream()->test(\Twig_Token::STRING_TYPE)) {
            $message = $this->parser->getExpressionParser()->parseExpression();
        } else {
            $message = new \Twig_Node_Expression_Constant('Template information', $token->getLine());
        }

        if ($this->parser->getStream()->test(\Twig_Token::STRING_TYPE)) {
            $translationBundle = $this->parser->getExpressionParser()->parseExpression();
        } else {
            $translationBundle = null;
        }

        $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

        return new TemplateBoxNode($message, $translationBundle, $this->enabled, $this->translator, $token->getLine(), $this->getTag());
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'sonata_template_box';
    }
}
