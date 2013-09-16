<?php

namespace Flint\Config\Normalizer;

/**
 * @package Flint
 */
class EnvironmentNormalizer implements NormalizerInterface
{
    const PLACEHOLDER = '{##|#([A-Z0-9_]+)#}';

    /**
     * @param  string $contents
     * @return string
     */
    public function normalize($contents)
    {
        return preg_replace_callback(static::PLACEHOLDER, array($this, 'callback'), $contents);
    }

    /**
     * @param  array $matches
     * @return mixed
     */
    protected function callback($matches)
    {
        if (!isset($matches[1])) {
            return '##';
        }

        return getenv($matches[1]);
    }
}
