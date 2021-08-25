<?php
/**
 * @see       https://github.com/zendframework/zend-config for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-config/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Config;

use Psr\Container\ContainerInterface;

class StandaloneWriterPluginManager implements ContainerInterface
{
    private $knownPlugins = [
        'ini'            => Writer\Ini::class,
        'javaproperties' => Writer\JavaProperties::class,
        'json'           => Writer\Json::class,
        'php'            => Writer\PhpArray::class,
        'phparray'       => Writer\PhpArray::class,
        'xml'            => Writer\Xml::class,
        'yaml'           => Writer\Yaml::class,
    ];

    /**
     * @param string $plugin
     * @return bool
     */
    public function has($plugin)
    {
        if (in_array($plugin, array_values($this->knownPlugins), true)) {
            return true;
        }

        return in_array(strtolower($plugin), array_keys($this->knownPlugins), true);
    }

    /**
     * @param string $plugin
     * @return Reader\ReaderInterface
     * @throws Exception\PluginNotFoundException
     */
    public function get($plugin)
    {
        if (! $this->has($plugin)) {
            throw new Exception\PluginNotFoundException(sprintf(
                'Config writer plugin by name %s not found',
                $plugin
            ));
        }

        if (! class_exists($plugin)) {
            $plugin = $this->knownPlugins[strtolower($plugin)];
        }

        return new $plugin();
    }
}
