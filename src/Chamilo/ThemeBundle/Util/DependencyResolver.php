<?php
/**
 * DependencyResolver.php
 * publisher
 * Date: 18.04.14.
 */

namespace Chamilo\ThemeBundle\Util;

/**
 * Class DependencyResolver.
 *
 * @package Chamilo\ThemeBundle\Util
 */
class DependencyResolver implements DependencyResolverInterface
{
    /**
     * @var array
     */
    protected $queued = [];
    /**
     * @var array
     */
    protected $registered = [];
    /**
     * @var array
     */
    protected $resolved = [];
    /**
     * @var array
     */
    protected $unresolved = [];

    /**
     * @param $items
     *
     * @return $this
     */
    public function register($items)
    {
        $this->registered = $items;

        return $this;
    }

    /**
     * @return array
     */
    public function resolveAll()
    {
        $this->failOnCircularDependencies();
        $this->resolve(array_keys($this->registered));

        return $this->queued;
    }

    /**
     * @param $ids
     */
    protected function resolve($ids)
    {
        foreach ($ids as $id) {
            if (isset($this->resolved[$id])) {
                continue;
            } // already done
            if (!isset($this->registered[$id])) {
                continue;
            } // unregistered
            if (!$this->hasDependencies($id)) { // standalone
                $this->queued[] = $this->registered[$id];
                $this->resolved[$id] = true;

                continue;
            }

            $deps = $this->unresolved($this->getDependencies($id));

            $this->resolve($deps);

            $deps = $this->unresolved($this->getDependencies($id));

            if (empty($deps)) {
                $this->queued[] = $this->registered[$id];
                $this->resolved[$id] = true;

                continue;
            }
        }
    }

    /**
     * @param $deps
     *
     * @return array
     */
    protected function unresolved($deps)
    {
        return array_diff($deps, array_keys($this->resolved));
    }

    /**
     * @param $id
     *
     * @return bool
     */
    protected function hasDependencies($id)
    {
        if (!isset($this->registered[$id])) {
            return false;
        }

        return !empty($this->registered[$id]['deps']);
    }

    /**
     * @param $id
     */
    protected function getDependencies($id)
    {
        if (!$this->hasDependencies($id)) {
            return null;
        }

        return $this->registered[$id]['deps'];
    }

    /**
     * @param $needle
     * @param $haystackId
     *
     * @return bool
     */
    protected function contains($needle, $haystackId)
    {
        $deps = $this->getDependencies($haystackId);
        if (!is_array($deps)) {
            return false;
        }

        return in_array($needle, $deps);
    }

    /**
     * @throws \RuntimeException
     */
    protected function failOnCircularDependencies()
    {
        $ids = array_keys($this->registered);

        foreach ($ids as $id) {
            if (!$this->hasDependencies($id)) {
                continue;
            }

            $dependencies = $this->getDependencies($id);

            foreach ($dependencies as $dep) {
                if ($this->contains($id, $dep)) {
                    throw new \RuntimeException(sprintf('Circular dependency [%s] depends on [%s] which itself depends on [%s]', $id, $dep, $id));
                }
            }
        }
    }
}
