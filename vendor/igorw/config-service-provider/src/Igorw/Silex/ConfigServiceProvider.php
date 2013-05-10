<?php

/*
 * This file is part of ConfigServiceProvider.
 *
 * (c) Igor Wiedler <igor@wiedler.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Igorw\Silex;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigServiceProvider implements ServiceProviderInterface
{
    private $filename;
    private $replacements = array();

    public function __construct($filename, array $replacements = array())
    {
        $this->filename = $filename;

        if ($replacements) {
            foreach ($replacements as $key => $value) {
                $this->replacements['%'.$key.'%'] = $value;
            }
        }
    }

    public function register(Application $app)
    {
        $config = $this->readConfig();

        foreach ($config as $name => $value) {
            $app[$name] = $this->doReplacements($value);
        }
    }

    public function boot(Application $app)
    {
    }

    private function doReplacements($value)
    {
        if (!$this->replacements) {
            return $value;
        }

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->doReplacements($v);
            }

            return $value;
        }

        return strtr($value, $this->replacements);
    }

    private function readConfig()
    {
        $format = pathinfo($this->filename, PATHINFO_EXTENSION);

        if (!$this->filename || !$format) {
            throw new \RuntimeException('A valid configuration file must be passed before reading the config.');
        }

        if (!file_exists($this->filename)) {
            throw new \InvalidArgumentException(
                sprintf("The config file '%s' does not exist.", $this->filename));
        }

        if ('yml' === $format) {
            if (!class_exists('Symfony\\Component\\Yaml\\Yaml')) {
                throw new \RuntimeException('Unable to read yaml as the Symfony Yaml Component is not installed.');
            }
            return Yaml::parse($this->filename);
        }

        if ('json' === $format) {
            return json_decode(file_get_contents($this->filename), true);
        }

        throw new \InvalidArgumentException(
                sprintf("The config file '%s' appears has invalid format '%s'.", $this->filename, $format));
    }

}
