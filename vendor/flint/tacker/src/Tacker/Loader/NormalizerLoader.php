<?php

namespace Tacker\Loader;

use Symfony\Component\Config\Loader\LoaderInterface;
use Tacker\Normalizer;

class NormalizerLoader extends \Symfony\Component\Config\Loader\Loader
{
    protected $normalizer;
    protected $loader;

    public function __construct(LoaderInterface $loader, Normalizer $normalizer)
    {
        $this->loader = $loader;
        $this->normalizer = $normalizer;
    }

    public function load($resource, $type = null)
    {
        $parameters = $this->loader->load($resource, $type);

        return tacker_array_map_recursive($parameters, array($this, 'normalize'));
    }

    public function supports($resource, $type = null)
    {
        return $this->loader->supports($resource, $type);
    }

    public function normalize($value)
    {
        return is_string($value) ? $this->normalizer->normalize($value) : $value;
    }
}
