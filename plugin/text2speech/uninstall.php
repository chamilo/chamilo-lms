<?php
/* For license terms, see /license.txt */

/**
 * Uninstall the Text2Speech Plugin.
 *
 * @package chamilo.plugin.text2speech
 */
require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/Text2SpeechPlugin.php';

if (!api_is_platform_admin()) {
    exit('You must have admin permissions to install plugins');
}

Text2SpeechPlugin::create()->uninstall();
