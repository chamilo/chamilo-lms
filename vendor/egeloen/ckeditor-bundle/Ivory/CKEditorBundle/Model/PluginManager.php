<?php

/*
 * This file is part of the Ivory CKEditor package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\CKEditorBundle\Model;

use Ivory\CKEditorBundle\Exception\PluginManagerException;

/**
 * {@inheritdoc}
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class PluginManager implements PluginManagerInterface
{
    /** @var array */
    protected $plugins = array();

    /**
     * Creates a plugin manager.
     *
     * @param array $plugins The CKEditor plugins.
     */
    public function __construct(array $plugins = array())
    {
        $this->setPlugins($plugins);
    }
    /**
     * {@inheritdoc}
     */
    public function hasPlugins()
    {
        return !empty($this->plugins);
    }

    /**
     * {@inheritdoc}
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * {@inheritdoc}
     */
    public function setPlugins(array $plugins)
    {
        foreach ($plugins as $name => $plugin) {
            $this->setPlugin($name, $plugin);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasPlugin($name)
    {
        return isset($this->plugins[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getPlugin($name)
    {
        if (!$this->hasPlugin($name)) {
            throw PluginManagerException::pluginDoesNotExist($name);
        }

        return $this->plugins[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function setPlugin($name, array $plugin)
    {
        $this->plugins[$name] = $plugin;
    }
}
