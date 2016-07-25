<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Exception\Renderer;

use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;

/**
 * This renderer uses a template to display an error message at the block position.
 *
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
class InlineRenderer implements RendererInterface
{
    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var string
     */
    protected $template;

    /**
     * Constructor.
     *
     * @param EngineInterface $templating Templating engine
     * @param string          $template   Template to render
     */
    public function __construct(EngineInterface $templating, $template)
    {
        $this->templating = $templating;
        $this->template   = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function render(\Exception $exception, BlockInterface $block, Response $response = null)
    {
        $parameters = array(
            'exception'      => $exception,
            'block'          => $block,
        );

        $content = $this->templating->render($this->template, $parameters);

        $response = $response ?: new Response();
        $response->setContent($content);

        return $response;
    }
}
