<?php
/* For license terms, see /license.txt */
/**
 * Install the Current Sessions Block plugin
 * @package chamilo.plugin.sessions_slider_block
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
require_once __DIR__ . '/../../main/inc/global.inc.php';

$plugin = CurrentSessionsBlockPlugin::create();

$showBlock = $plugin->get(CurrentSessionsBlockPlugin::CONFIG_SHOW_BLOCK) === 'true';

if ($showBlock) {
    if (!api_is_anonymous()) {
        $sessions = $plugin->getSessionList();

        $_template['sessions'] = $sessions;
    }
}
