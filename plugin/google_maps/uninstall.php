<?php
/* For licensing terms, see /license.txt */
/**
 * Initialization uninstall.
 *
 * @author José Loguercio Silva <jose.loguercio@beeznest.com>
 *
 * @package chamilo.plugin.google_maps
 */
require_once __DIR__.'/config.php';

GoogleMapsPlugin::create()->uninstall();
