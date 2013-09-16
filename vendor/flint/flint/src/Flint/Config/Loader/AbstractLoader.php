<?php

namespace Flint\Config\Loader;

use Flint\Config\Normalizer\NormalizerInterface;
use Flint\Config\ResourceCollection;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Resource\FileResource;

/**
 * @package Flint
 */
abstract class AbstractLoader extends \Symfony\Component\Config\Loader\FileLoader {

    protected $normalizer;
    protected $resources;

    /**
     * @param NormalizerInterface  $normalizer
     * @param FileLocatorInterface $locator
     * @param ResourceCollection   $resources
     */
    public function __construct(
        NormalizerInterface $normalizer,
        FileLocatorInterface $locator,
        ResourceCollection $resources
    ) {
        parent::__construct($locator);

        $this->normalizer = $normalizer;
        $this->resources = $resources;
    }

    /**
     * {@inheritDoc}
     */
    public function load($resource, $type = null)
    {
        $resource = $this->locator->locate($resource);

        $this->resources->add(new FileResource($resource));

        return $this->parse($this->read($resource), $resource);
    }

    /**
     * @param  array  $parameters
     * @param  string $file
     * @return array
     */
    protected function parse(array $parameters, $file)
    {
        if (!isset($parameters['@import'])) {
            return $parameters;
        }

        $import = $parameters['@import'];

        unset($parameters['@import']);

        $this->setCurrentDir(dirname($import));

        return array_replace($this->import($import, null, false, $file), $parameters);
    }

    /**
     * @param $resource
     * @return array
     */
    abstract protected function read($resource);

}