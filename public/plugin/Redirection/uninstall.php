<?php

/* For licensing terms, see /license.txt */
/**
 * Uninstall the plugin.
 *
 * @author Enrique Alcaraz Lopez
 */
api_protect_admin_script();

RedirectionPlugin::create()->uninstall();
