<?php
$course_plugin = 'olpc_peru_filter'; //needed in order to load the plugin lang variables 
require_once dirname(__FILE__).'/config.php';
$plugin_info = OLPC_Peru_FilterPlugin::create()->get_info();
