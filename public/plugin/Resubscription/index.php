<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/config.php';

api_protect_admin_script(true);

$plugin = Resubscription::create();

Display::display_header($plugin->get_title());

echo '<div class="max-w-3xl rounded-lg border border-gray-25 bg-white p-6 shadow-sm">';
echo '<h2 class="mb-2 text-xl font-semibold text-gray-90">'.htmlspecialchars($plugin->get_title(), ENT_QUOTES, api_get_system_encoding()).'</h2>';
echo '<p class="text-sm text-gray-50">'.htmlspecialchars($plugin->get_comment(), ENT_QUOTES, api_get_system_encoding()).'</p>';
echo '<p class="mt-4 text-sm text-gray-50">'.htmlspecialchars($plugin->get_lang('PluginUsageHelp'), ENT_QUOTES, api_get_system_encoding()).'</p>';
echo '</div>';

Display::display_footer();
