<?php

/*
 * This file is part of the Pagerfanta package.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pagerfanta\View\Template;

/**
 * @author Pablo Díez <pablodip@gmail.com>
 */
abstract class Template implements TemplateInterface
{
    static protected $defaultOptions = array();

    private $routeGenerator;
    private $options;

    public function __construct($routeGenerator, array $options = array())
    {
        $this->routeGenerator = $routeGenerator;
        $this->options = array_merge(static::$defaultOptions, $options);
    }

    protected function generateRoute($page)
    {
        return call_user_func($this->routeGenerator, $page);
    }

    protected function option($name)
    {
        if (!isset($this->options[$name])) {
            throw new \InvalidArgumentException(sprintf('The option "%s" does not exist.', $name));
        }

        return $this->options[$name];
    }
}