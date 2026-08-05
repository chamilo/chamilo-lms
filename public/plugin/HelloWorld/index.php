<?php

/* For licensing terms, see /license.txt */

/**
 * Render a minimal translated greeting in the assigned plugin region.
 */

if (!class_exists('Plugin', false)) {
    $globalInc = __DIR__.'/../../main/inc/global.inc.php';

    if (is_file($globalInc)) {
        require_once $globalInc;
    }
}

if (!class_exists('HelloWorldPlugin', false)) {
    require_once __DIR__.'/src/HelloWorldPlugin.php';
}

$plugin = HelloWorldPlugin::create();

$region = '';
if (isset($plugin_info) && is_array($plugin_info)) {
    $region = (string) ($plugin_info['current_region'] ?? '');
}

if ('' === $region && isset($_GET['region'])) {
    $region = (string) $_GET['region'];
}

$region = preg_replace('/[^a-zA-Z0-9_\-]/', '', $region) ?: 'unknown';

$greeting = htmlspecialchars($plugin->getConfiguredGreeting(), ENT_QUOTES, 'UTF-8');
$title = htmlspecialchars($plugin->get_lang('region_title'), ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars($plugin->get_lang('region_description'), ENT_QUOTES, 'UTF-8');
$regionLabel = htmlspecialchars($region, ENT_QUOTES, 'UTF-8');

echo <<<HTML
<div class="hello-world-plugin my-4 rounded-2xl border border-support-3 bg-white p-5 shadow-sm" data-hello-world-region="{$regionLabel}">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-support-2 text-primary">
            <span class="mdi mdi-hand-wave-outline text-2xl" aria-hidden="true"></span>
        </div>
        <div class="min-w-0 flex-1">
            <div class="mb-1 flex flex-wrap items-center gap-2">
                <h3 class="mb-0 text-lg font-semibold text-gray-90">{$title}</h3>
                <span class="rounded-full bg-support-1 px-2 py-0.5 text-xs font-medium text-primary">{$regionLabel}</span>
            </div>
            <p class="mb-3 text-body-2 text-gray-50">{$description}</p>
            <div class="rounded-xl bg-gray-15 px-4 py-3 text-xl font-semibold text-primary">
                {$greeting}
            </div>
        </div>
    </div>
</div>
HTML;
