<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes.
 */

namespace Chamilo\CoreBundle\HookEvent\Interfaces;

/**
 * This interface should be implemented by plugins to implements event subscribers.
 */
interface PluginEventSubscriberInterface
{
    /**
     * This method will call the Hook management insertHook to add Hook observer from this plugin.
     */
    public function installEventSubscribers(): void;

    /**
     * This method will call the Hook management deleteHook to disable Hook observer from this plugin.
     */
    public function uninstallEventSubscribers(): void;
}
