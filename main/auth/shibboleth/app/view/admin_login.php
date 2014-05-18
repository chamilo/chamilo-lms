<?php

namespace Shibboleth;

/**
 * Administratrive login. Useful when the standard login is not available anymore
 * which is usually the case. 
 * 
 * This page allow administrators to log into the application using the standard
 * Chamilo method when Shibboleth is not available.
 * 
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod for the University of Geneva
 */
$dir = dirname(__FILE__);
include_once("$dir/../../init.php");
require_once api_get_path(LIBRARY_PATH).'userportal.lib.php';


ShibbolethController::instance()->admin_login();