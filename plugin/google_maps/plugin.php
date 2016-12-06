<?php
/* For licensing terms, see /license.txt */
/**
 * Show the JavaScript template in the web pages
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.tour
 */
require_once __DIR__.'/config.php';

$plugin_info = GoogleMaps::create()->get_info();
