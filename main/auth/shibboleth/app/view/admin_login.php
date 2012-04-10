<?php

/**
 * Administratrive login. Useful when the standard login is not available anymore
 * which is usually the case. 
 * 
 * This page allow administrators to log into the application using the standard
 * Chamilo method when Shibboleth is not available.
 * 
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod
 */
$dir = dirname(__FILE__);
include_once("$dir/../../init.php");
require_once api_get_path(LIBRARY_PATH).'userportal.lib.php';


ShibbolethController::instance()->admin_login();