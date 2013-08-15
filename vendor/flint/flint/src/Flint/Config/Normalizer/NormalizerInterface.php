<?php

namespace Flint\Config\Normalizer;

/**
 * @package Flint
 */
interface NormalizerInterface
{
    /**
     * @param  string $contents
     * @return string
     */
    public function normalize($contents);
}
