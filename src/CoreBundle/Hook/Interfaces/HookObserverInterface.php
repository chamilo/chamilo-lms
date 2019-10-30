<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes.
 */

namespace Chamilo\CoreBundle\Hook\Interfaces;

/**
 * Interface HookObserverInterface.
 *
 * @package Chamilo\CoreBundle\Hook
 */
interface HookObserverInterface
{
    /**
     * Return the singleton instance of Hook observer.
     *
     * @return static
     */
    public static function create();

    /**
     * Return the path from the class, needed to store location or autoload later.
     *
     * @return string
     */
    public function getPath();

    /**
     * Return the plugin name where is the Hook Observer.
     *
     * @return string
     */
    public function getPluginName();
}
