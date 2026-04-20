<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../../main/inc/global.inc.php';

/**
 * Load the H5P core library classes required by the plugin.
 */
function h5pimport_require_h5p_core(): void
{
    if (interface_exists('\H5PFrameworkInterface', false) && class_exists('\H5PCore', false)) {
        return;
    }

    $projectRoot = dirname(__DIR__, 3);
    $publicRoot = dirname(__DIR__, 2);

    $candidates = [
        $projectRoot.'/vendor/h5p/h5p-core/h5p.classes.php',
        $publicRoot.'/vendor/h5p/h5p-core/h5p.classes.php',
    ];

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            require_once $candidate;
            return;
        }
    }

    throw new RuntimeException(
        'H5P core library not found. Checked: '.implode(', ', $candidates)
    );
}

h5pimport_require_h5p_core();

require_once __DIR__.'/H5pImportPlugin.php';

/**
 * Check whether the plugin is active for the current access URL.
 */
function h5pimport_is_plugin_active(): bool
{
    $pluginRepository = Container::getPluginRepository();
    $pluginEntity = $pluginRepository->findOneByTitle(H5pImportPlugin::create()->get_name());

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
function h5pimport_get_course_home_url(): string
{
    $courseId = (int) api_get_course_int_id();
    $sessionId = (int) api_get_session_id();

    return api_get_path(WEB_PATH).'course/'.$courseId.'/home?sid='.$sessionId;
}
