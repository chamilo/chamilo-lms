<?php

namespace Igorw\Silex;

use Toml\Parser;

class TomlConfigDriver implements ConfigDriver
{
    public function load($filename)
    {
        if (!class_exists('Toml\\Parser')) {
            throw new \RuntimeException('Unable to read toml as the Toml Parser is not installed.');
        }

        $config = Parser::fromFile($filename);
        return $config ?: array();
    }

    public function supports($filename)
    {
        return (bool) preg_match('#\.toml(\.dist)?$#', $filename);
    }
}
