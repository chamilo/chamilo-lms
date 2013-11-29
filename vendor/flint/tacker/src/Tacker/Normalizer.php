<?php

namespace Tacker;

/**
 * @package Tacker
 */
interface Normalizer
{
    /**
     * @param  mixed $value
     * @return mixed
     */
    public function normalize($value);
}
