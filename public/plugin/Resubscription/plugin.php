<?php

/* For licensing terms, see /license.txt */
/**
 * Get the plugin info.
 *
 * @author Imanol Losada Oriol <imanol.losada@beeznest.com>
 */
require_once __DIR__.'/config.php';

$plugin_info = Resubscription::create()->get_info();
