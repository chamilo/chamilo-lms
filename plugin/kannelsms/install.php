<?php
/* For licensing terms, see /vendor/license.txt */

/**
 * This script is included by main/admin/settings.lib.php and generally
 * includes things to execute in the main database (settings_current table).
 *
 * @package chamilo.plugin.kannelsms
 *
 * @author  Imanol Losada <imanol.losada@beeznest.com>
 */
/**
 * Initialization.
 */
require_once __DIR__.'/config.php';
KannelsmsPlugin::create()->install();
