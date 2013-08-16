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

use Doctrine\Common\Collections\ArrayCollection;

class ResourceCollection extends ArrayCollection
{
    private $context;
    private $temporary = false;

    /**
     * Constructor
     *
     * @param String $context
     * @param array  $elements An array of Resource
     */
    public function __construct($context, array $elements = array())
    {
        $this->context = $context;
        parent::__construct($elements);
    }

    /**
     * Returns the context related to the collection
     *
     * @return String
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Sets the context of the current collection
     *
     * @param type $context
     *
     * @return ResourceCollection
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Tells wheter the collection is temporary or not.
     *
     * A ResourceCollection is temporary when it required a temporary folder to
     * fetch data
     *
     * @return type
     */
    public function isTemporary()
    {
        return $this->temporary;
    }

    /**
     * Sets the collection temporary
     *
     * @param Boolean $temporary
     *
     * @return ResourceCollection
     */
    public function setTemporary($temporary)
    {
        $this->temporary = (Boolean) $temporary;

        return $this;
    }

    /**
     * Returns true if all resources can be processed in place, false otherwise
     *
     * @return Boolean
     */
    public function canBeProcessedInPlace()
    {
        foreach ($this as $resource) {
            if (!$resource->canBeProcessedInPlace($this->context)) {
                return false;
            }
        }

        return true;
    }
}
