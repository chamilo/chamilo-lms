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

/**
 * Interface for exception renderer.
 *
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
interface RendererInterface
{
    /**
     * Renders an exception into an HTTP response.
     *
     * @param \Exception     $exception Exception provoked
     * @param BlockInterface $block     Block that provoked the exception
     * @param Response       $response  Response to alter
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render(\Exception $exception, BlockInterface $block, Response $response = null);
}
