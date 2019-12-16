<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\Util;

/**
 * Interface DependencyResolverInterface.
 */
interface DependencyResolverInterface
{
    /**
     * @param $items
     *
     * @return $this
     */
    public function register($items);

    /**
     * @return array
     */
    public function resolveAll();
}
