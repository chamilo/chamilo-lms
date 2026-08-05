<?php

/* For licensing terms, see /license.txt */

/**
 * Plugin details.
 *
 * This plugin intentionally stays as a simple region renderer. It must not be
 * marked as an admin plugin because administrators need to assign it to several
 * layout regions from the Regions settings page.
 */

$plugin_info = [
    'title' => 'Show regions',
    'comment' => 'Displays visible markers for enabled plugin regions. Markers are shown only to platform administrators.',
    'version' => '2.0.0',
    'author' => 'Chamilo',
    'source' => 'official',
    'commercial_model' => 'free',
    'supports_regions' => true,
];
