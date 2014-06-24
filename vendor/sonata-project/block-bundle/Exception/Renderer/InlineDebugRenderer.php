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

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;
use Sonata\BlockBundle\Model\BlockInterface;

use Symfony\Component\HttpKernel\Exception\FlattenException;

/**
 * This renderer uses a template to display an error message at the block position with extensive debug information.
 *
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
class InlineDebugRenderer implements RendererInterface
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
     * @var boolean
     */
    protected $forceStyle;

    /**
     * @var boolean
     */
    protected $debug;

    /**
     * Constructor
     *
     * @param EngineInterface $templating Templating engine
     * @param string          $template   Template to render
     * @param boolean         $debug      Whether the debug is enabled or not
     * @param boolean         $forceStyle Whether to force style within the template or not
     */
    public function __construct(EngineInterface $templating, $template, $debug, $forceStyle = true)
    {
        $this->templating = $templating;
        $this->template   = $template;
        $this->debug      = $debug;
        $this->forceStyle = $forceStyle;
    }

    /**
     * {@inheritdoc}
     */
    public function render(\Exception $exception, BlockInterface $block, Response $response = null)
    {
        $response = $response ?: new Response();

        // enforce debug mode or ignore silently
        if (!$this->debug) {
            return $response;
        }

        $flattenException = FlattenException::create($exception);
        $code = $flattenException->getStatusCode();

        $parameters = array(
            'exception'      => $flattenException,
            'status_code'    => $code,
            'status_text'    => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
            'logger'         => false,
            'currentContent' => false,
            'block'          => $block,
            'forceStyle'     => $this->forceStyle
        );

        $content = $this->templating->render($this->template, $parameters);
        $response->setContent($content);

        return $response;
    }
}
