<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Cache\Invalidation;

class ModelCollectionIdentifiers
{
    protected $classes = array();

    /**
     * @param array $classes
     */
    public function __construct(array $classes = array())
    {
        foreach ($classes as $class => $identifier) {
            $this->addClass($class, $identifier);
        }
    }

    /**
     * @param string $class
     * @param mixed  $identifier
     */
    public function addClass($class, $identifier)
    {
        $this->classes[$class] = $identifier;
    }

    /**
     * @param $object
     *
     * @return bool|mixed
     */
    public function getIdentifier($object)
    {
        $identifier = $this->getMethod($object);

        if (!$identifier) {
            return false;
        }

        return call_user_func(array($object, $identifier));
    }

    /**
     * @param $object
     *
     * @return bool
     */
    public function getMethod($object)
    {
        if ($object === null) {
            return false;
        }

        foreach ($this->classes as $class => $identifier) {
            if ($object instanceof $class) {
                return $identifier;
            }
        }

        $class = get_class($object);

        if (method_exists($object, 'getCacheIdentifier')) {
            $this->addClass($class, 'getCacheIdentifier');
        } elseif (method_exists($object, 'getId')) {
            $this->addClass($class, 'getId');
        } else {
            return false;
        }

        return $this->classes[$class];
    }
}
