<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Exception\Strategy;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\BlockBundle\Exception\Renderer\RendererInterface;
use Sonata\BlockBundle\Exception\Filter\FilterInterface;

/**
 * The strategy manager handles exceptions thrown by a block. It uses an exception filter to identify which exceptions
 * it should handle or ignore. It then uses an exception renderer to "somehow" display the exception.
 *
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
class StrategyManager implements StrategyManagerInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $filters;

    /**
     * @var array
     */
    protected $renderers;

    /**
     * @var array
     */
    protected $blockFilters;

    /**
     * @var array
     */
    protected $blockRenderers;

    /**
     * @var string
     */
    protected $defaultFilter;

    /**
     * @var string
     */
    protected $defaultRenderer;

    /**
     * Constructor
     *
     * @param ContainerInterface $container      Dependency injection container
     * @param array              $filters        Filter definitions
     * @param array              $renderers      Renderer definitions
     * @param array              $blockFilters   Filter names for each block
     * @param array              $blockRenderers Renderer names for each block
     */
    public function __construct(ContainerInterface $container, array $filters, array $renderers, array $blockFilters, array $blockRenderers)
    {
        $this->container      = $container;
        $this->filters        = $filters;
        $this->renderers      = $renderers;
        $this->blockFilters   = $blockFilters;
        $this->blockRenderers = $blockRenderers;
    }

    /**
     * Sets the default filter name
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException
     */
    public function setDefaultFilter($name)
    {
        if (!array_key_exists($name, $this->filters)) {
            throw new \InvalidArgumentException(sprintf('Cannot set default exception filter "%s". It does not exist.', $name));
        }

        $this->defaultFilter = $name;
    }

    /**
     * Sets the default renderer name
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException
     */
    public function setDefaultRenderer($name)
    {
        if (!array_key_exists($name, $this->renderers)) {
            throw new \InvalidArgumentException(sprintf('Cannot set default exception renderer "%s". It does not exist.', $name));
        }

        $this->defaultRenderer = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function handleException(\Exception $exception, BlockInterface $block, Response $response = null)
    {
        $response = $response ?: new Response();
        $response->setPrivate();

        $filter = $this->getBlockFilter($block);
        if ($filter->handle($exception, $block)) {
            $renderer = $this->getBlockRenderer($block);
            $response = $renderer->render($exception, $block, $response);
        } else {
            // render empty block template?
        }

        return $response;
    }

    /**
     * Returns the exception renderer for given block
     *
     * @param BlockInterface $block
     *
     * @return RendererInterface
     *
     * @throws \RuntimeException
     */
    public function getBlockRenderer(BlockInterface $block)
    {
        $type = $block->getType();

        $name = isset($this->blockRenderers[$type]) ? $this->blockRenderers[$type] : $this->defaultRenderer;
        $service = $this->getRendererService($name);

        if (!$service instanceof RendererInterface) {
            throw new \RuntimeException(sprintf('The service "%s" is not an exception renderer', $name));
        }

        return $service;
    }

    /**
     * Returns the exception filter for given block
     *
     * @param BlockInterface $block
     *
     * @return FilterInterface
     *
     * @throws \RuntimeException
     */
    public function getBlockFilter(BlockInterface $block)
    {
        $type = $block->getType();

        $name = isset($this->blockFilters[$type]) ? $this->blockFilters[$type] : $this->defaultFilter;
        $service = $this->getFilterService($name);

        if (!$service instanceof FilterInterface) {
            throw new \RuntimeException(sprintf('The service "%s" is not an exception filter', $name));
        }

        return $service;
    }

    /**
     * Returns the filter service for given filter name
     *
     * @param string $name
     *
     * @return object
     *
     * @throws \RuntimeException
     */
    protected function getFilterService($name)
    {
        if (!isset($this->filters[$name])) {
            throw new \RuntimeException('The filter "%s" does not exist.');
        }

        return $this->container->get($this->filters[$name]);
    }

    /**
     * Returns the renderer service for given renderer name
     *
     * @param string $name
     *
     * @return object
     *
     * @throws \RuntimeException
     */
    protected function getRendererService($name)
    {
        if (!isset($this->renderers[$name])) {
            throw new \RuntimeException('The renderer "%s" does not exist.');
        }

        return $this->container->get($this->renderers[$name]);
    }
}
