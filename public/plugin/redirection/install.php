<?php
/* For licensing terms, see /license.txt */
/**
 * Install.
 *
 * @author Enrique Alcaraz Lopez
 *
 * @package chamilo.plugin.redirection
 */
api_protect_admin_script();

RedirectionPlugin::create()->install();
