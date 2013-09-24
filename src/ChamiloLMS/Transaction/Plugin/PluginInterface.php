<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Transaction\Plugin;

/**
 * General plugin interface.
 */
interface PluginInterface
{
    /**
     * Returns a string identifier for the plugin.
     *
     * @return string
     *   This plugin's machine name.
     */
    public function getMachineName();
}
