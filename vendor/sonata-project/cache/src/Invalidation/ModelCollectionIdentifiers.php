<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Cache\Invalidation;

class ModelCollectionIdentifiers
{
    protected $classes = [];

    /**
     * @param array $classes
     */
    public function __construct(array $classes = [])
    {
        foreach ($classes as $class => $identifier) {
            $this->addClass($class, $identifier);
        }
    }

    /**
     * @param string $class
     * @param mixed  $identifier
     */
    public function addClass(string $class, $identifier): void
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

        return call_user_func([$object, $identifier]);
    }

    /**
     * @param $object
     *
     * @return mixed
     */
    public function getMethod($object)
    {
        if (null === $object) {
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
