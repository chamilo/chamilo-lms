<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Source;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class SymfonySitemapSourceIterator implements SourceIteratorInterface
{
    protected $router;

    protected $source;

    protected $routeName;

    protected $parameters;

    /**
     * @param SourceIteratorInterface $source
     * @param RouterInterface         $router
     * @param string                  $routeName
     * @param array                   $parameters
     */
    public function __construct(SourceIteratorInterface $source, RouterInterface $router, $routeName, array $parameters = array())
    {
        $this->source = $source;
        $this->router = $router;
        $this->routeName = $routeName;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $data = $this->source->current();

        $parameters = array_merge($this->parameters, array_intersect_key($data, $this->parameters));

        if (!isset($data['url'])) {
            $data['url'] = $this->router->generate($this->routeName, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->source->next();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->source->key();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->source->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->source->rewind();
    }
}
