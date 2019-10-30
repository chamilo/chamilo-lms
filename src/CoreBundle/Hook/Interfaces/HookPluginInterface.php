<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes.
 */

namespace Chamilo\CoreBundle\Hook\Interfaces;

/**
 * Interface HookPluginInterface.
 *
 * This interface should be implemented by plugins to implements Hook Observer.
 *
 * @package Chamilo\CoreBundle\Hook
 */
interface HookPluginInterface
{
    /**
     * This method will call the Hook management insertHook to add Hook observer from this plugin.
     *
     * @return int
     */
    public function installHook();

    /**
     * This method will call the Hook management deleteHook to disable Hook observer from this plugin.
     *
     * @return int
     */
    public function uninstallHook();
}
