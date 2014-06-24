<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Sonata\CoreBundle\Model;


/**
 * Class Metadata
 *
 * @package Sonata\CoreBundle\Model
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class Metadata implements MetadataInterface
{
    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var mixed
     */
    protected $image;

    /**
     * @var string
     */
    protected $domain;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param string $title
     * @param string $description
     * @param mixed  $image
     * @param string $domain
     * @param array  $options
     */
    public function __construct($title, $description = null, $image = null, $domain = null, array $options = array())
    {
        $this->title       = $title;
        $this->description = $description;
        $this->image       = $image;
        $this->domain      = $domain;
        $this->options     = $options;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $name    The option key
     * @param mixed  $default The default value if option not found
     *
     * @return mixed
     */
    public function getOption($name, $default = null)
    {
        return array_key_exists($name, $this->options) ? $this->options[$name] : $default;
    }

    /**
     * Sets an option
     *
     * @param $name
     * @param $option
     */
    public function setOption($name, $option)
    {
        $this->options[$name] = $option;
    }

    /**
     * Sets all options
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }
}