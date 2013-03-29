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

        foreach ($config as $name => $value)
            if ('%' === substr($name, 0, 1))
                $this->replacements[$name] = (string) $value;

        $this->merge($app, $config);
    }

    public function boot(Application $app)
    {
    }

    public function getFileFormat()
    {
        $filename = $this->filename;

        if (preg_match('#.ya?ml(.dist)?$#i', $filename)) {
            return 'yaml';
        }

        if (preg_match('#.json(.dist)?$#i', $filename)) {
            return 'json';
        }

        if (preg_match('#.php(.dist)?$#i', $filename)) {
            return 'php';
        }

        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    protected function processRawJson($json)
    {
        return $json;
    }

    private function merge(Application $app, array $config)
    {
        foreach ($config as $name => $value) {
            if (isset($app[$name]) && is_array($value)) {
                $app[$name] = $this->mergeRecursively($app[$name], $value);
            } else {
                $app[$name] = $this->doReplacements($value);
            }
        }
    }

    private function mergeRecursively(array $currentValue, array $newValue)
    {
        foreach ($newValue as $name => $value) {
            if (is_array($value) && isset($currentValue[$name])) {
                $currentValue[$name] = $this->mergeRecursively($currentValue[$name], $value);
            } else {
                $currentValue[$name] = $this->doReplacements($value);
            }
        }

        return $currentValue;
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

        if (is_string($value)) {
            return strtr($value, $this->replacements);
        }

        return $value;
    }

    private function readConfig()
    {
        $format = $this->getFileFormat();

        if (!$this->filename || !$format) {
            throw new \RuntimeException('A valid configuration file must be passed before reading the config.');
        }

        if (!file_exists($this->filename)) {
            throw new \InvalidArgumentException(
                sprintf("The config file '%s' does not exist.", $this->filename));
        }

        if ('php' === $format) {
            $config = require $this->filename;
            $config = (1 === $config) ? array() : $config;
            return $config ?: array();
        }

        if ('yaml' === $format) {
            if (!class_exists('Symfony\\Component\\Yaml\\Yaml')) {
                throw new \RuntimeException('Unable to read yaml as the Symfony Yaml Component is not installed.');
            }
            $config = Yaml::parse($this->filename);
            return $config ?: array();
        }

        if ('json' === $format) {
            $config = $this->parseJson($this->filename);

            if (JSON_ERROR_NONE !== json_last_error()) {
                $jsonError = $this->getJsonError(json_last_error());
                throw new \RuntimeException(
                    sprintf('Invalid JSON provided "%s" in "%s"', $jsonError, $this->filename));
            }

            return $config ?: array();
        }

        throw new \InvalidArgumentException(
                sprintf("The config file '%s' appears has invalid format '%s'.", $this->filename, $format));
    }

    private function parseJson($filename)
    {
        $json = file_get_contents($filename);
        $json = $this->processRawJson($json);
        return json_decode($json, true);
    }

    private function getJsonError($code)
    {
        $errorMessages = array(
            JSON_ERROR_DEPTH            => 'The maximum stack depth has been exceeded',
            JSON_ERROR_STATE_MISMATCH   => 'Invalid or malformed JSON',
            JSON_ERROR_CTRL_CHAR        => 'Control character error, possibly incorrectly encoded',
            JSON_ERROR_SYNTAX           => 'Syntax error',
            JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, possibly incorrectly encoded',
        );

        return isset($errorMessages[$code]) ? $errorMessages[$code] : 'Unknown';
    }
}
