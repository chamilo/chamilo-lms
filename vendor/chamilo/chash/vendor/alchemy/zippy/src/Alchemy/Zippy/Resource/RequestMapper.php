<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Zippy\Resource;

class RequestMapper
{
    private $locator;

    /**
     * Constructor
     *
     * @param TargetLocator $locator
     */
    public function __construct(TargetLocator $locator)
    {
        $this->locator = $locator;
    }

    /**
     * Maps resources request to a ResourceCollection
     *
     * @return ResourceCollection
     */
    public function map($context, array $resources)
    {
        $collection = new ResourceCollection($context);

        foreach ($resources as $location => $resource) {
            if (is_int($location)) {
                $collection->add(new Resource($resource, $this->locator->locate($context, $resource)));
            } else {
                $collection->add(new Resource($resource, ltrim($location, '/')));
            }
        }

        return $collection;
    }

    /**
     * Creates the default RequestMapper
     *
     * @return RequestMapper
     */
    public static function create()
    {
        return new static(new TargetLocator());
    }
}
