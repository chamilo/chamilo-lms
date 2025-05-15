<?php

/* For licensing terms, see /license.txt */
/**
 * Initialization install.
 *
 * @author José Loguercio Silva <jose.loguercio@beeznest.com>
 */
require_once __DIR__.'/config.php';

GoogleMapsPlugin::create()->install();
