<?php

/* For licensing terms, see /license.txt */
/**
 * Config the plugin.
 *
 * @author Jose Angel Ruiz <desarrollo@nosolored.com>
 */

use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/src/test2pdf.lib.php';
require_once api_get_path(SYS_PLUGIN_PATH).'Test2Pdf/src/test2pdf_plugin.class.php';

/**
 * Check whether the plugin is active for the current access URL.
 */
function test2pdf_is_plugin_active(): bool
{
    $pluginRepository = Container::getPluginRepository();
    $pluginEntity = $pluginRepository->findOneByTitle('Test2Pdf');

    if (!$pluginEntity || !$pluginEntity->isInstalled()) {
        return false;
    }

    $accessUrl = Container::getAccessUrlUtil()->getCurrent();
    $configuration = $pluginEntity->getConfigurationsByAccessUrl($accessUrl);

    return $configuration ? $configuration->isActive() : false;
}

/**
 * Build the current course home URL in Chamilo 2 style.
 */
function test2pdf_get_course_home_url(): string
{
    $courseId = (int) api_get_course_int_id();
    $sessionId = (int) api_get_session_id();

    return api_get_path(WEB_PATH).'course/'.$courseId.'/home?sid='.$sessionId;
}
