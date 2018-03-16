<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\Util;

/**
 * Interface DependencyResolverInterface.
 *
 * @package Chamilo\ThemeBundle\Util
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
