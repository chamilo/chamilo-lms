<?php

namespace Tacker\Normalizer;

use Symfony\Component\Config\FileLocatorInterface;

class EnvfileNormalizer extends EnvironmentNormalizer
{
    protected $env;
    protected $locator;

    public function __construct(FileLocatorInterface $locator)
    {
        $this->locator = $locator;
    }

    protected function callback($matches)
    {
        if (!isset($matches[1])) {
            return $matches[0];
        }

        $this->load();

        if (!isset($this->env[$matches[1]])) {
            return $matches[0];
        }

        return $this->env[$matches[1]];
    }

    protected function load()
    {
        if (!is_null($this->env)) {
            return;
        }

        if (!$file = $this->locate()) {
            return;
        }

        $this->env = parse_ini_file($file, false, INI_SCANNER_RAW) ?: array();
        $this->env = array_change_key_case($this->env, CASE_UPPER);
    }

    protected function locate()
    {
        foreach (array('Envfile', 'Envfile.dist') as $i => $file) {
            try {
                return $this->locator->locate($file);
            } catch (\InvalidArgumentException $e) {
                // there is a possibility there is no file to load
            }
        }
    }
}
