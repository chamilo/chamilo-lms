<?php

namespace Tacker\Normalizer;

/**
 * @package Tacker
 */
class EnvironmentNormalizer implements \Tacker\Normalizer
{
    /**
     * @param  string $value
     * @return string
     */
    public function normalize($value)
    {
        return preg_replace_callback('{##|#([A-Z0-9_]+)#}', array($this, 'callback'), $value);
    }

    /**
     * @param  array $matches
     * @return mixed
     */
    protected function callback($matches)
    {
        if (!isset($matches[1])) {
            return $matches[0];
        }

        if (false !== $env = getenv($matches[1])) {
            return $env;
        };

        return $matches[0];
    }
}
