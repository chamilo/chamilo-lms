<?php

namespace Tacker\Normalizer;

use Tacker\Normalizer;

/**
 * @package Tacker
 */
class ChainNormalizer implements \Tacker\Normalizer
{
    protected $normalizers = array();

    /**
     * @param array $normalizers
     */
    public function __construct(array $normalizers = array())
    {
        array_map(array($this, 'add'), $normalizers);
    }

    /**
     * @param Normalizer $normalizer
     */
    public function add(Normalizer $normalizer)
    {
        $this->normalizers[] = $normalizer;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($value)
    {
        foreach ($this->normalizers as $normalizer) {
            $value = $normalizer->normalize($value);
        }

        return $value;
    }
}
