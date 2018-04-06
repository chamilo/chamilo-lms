<?php
/* For licensing terms, see /license.txt */
/**
 * Uninstall the plugin.
 *
 * @author Enrique Alcaraz Lopez
 *
 * @package chamilo.plugin.redirection
 */
api_protect_admin_script();

RedirectionPlugin::create()->uninstall();
