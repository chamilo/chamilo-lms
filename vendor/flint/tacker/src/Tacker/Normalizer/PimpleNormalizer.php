<?php

namespace Tacker\Normalizer;

/**
 * @package Tacker
 */
class PimpleNormalizer implements \Tacker\Normalizer
{
    protected $pimple;

    /**
     * @param Pimple $pimple
     */
    public function __construct(\Pimple $pimple)
    {
        $this->pimple = $pimple;
    }

    /**
     * @param  string $value
     * @return string
     */
    public function normalize($value)
    {
        if (preg_match('{^%([a-z0-9_.]+)%$}', $value, $match)) {
            return isset($this->pimple[$match[1]]) ? $this->pimple[$match[1]] : $match[0];
        }

        return preg_replace_callback('{%%|%([a-z0-9_.]+)%}', array($this, 'callback'), $value);
    }

    /**
     * @param  array $matches
     * @return mixed
     */
    protected function callback($matches)
    {
        if (!isset($matches[1])) {
            return '%%';
        }

        return isset($this->pimple[$matches[1]]) ? $this->pimple[$matches[1]] : $matches[0];
    }
}
