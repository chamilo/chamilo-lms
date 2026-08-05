<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/config.php';

if (!api_is_platform_admin()) {
    api_not_allowed(true);
}

$plugin = CourseLegalPlugin::create();

Display::display_header($plugin->get_title());

echo '
<div class="max-w-4xl rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center gap-3">
        <span class="mdi mdi-file-document-check-outline ch-tool-icon text-primary"></span>
        <h2 class="m-0 text-h3 font-semibold text-gray-90">'.htmlspecialchars($plugin->get_title(), ENT_QUOTES | ENT_SUBSTITUTE).'</h2>
    </div>
    <p class="mb-4 text-body-2 text-gray-70">
        '.htmlspecialchars($plugin->get_comment(), ENT_QUOTES | ENT_SUBSTITUTE).'
    </p>
    <div class="rounded-xl border border-gray-25 bg-support-2 p-4 text-body-2 text-gray-70">
        <p class="mb-2">
            This plugin is configured per course.
        </p>
        <p class="mb-0">
            Open a course, go to course settings, enable legal terms in the course access section, and use the
            Course legal agreement panel to configure the agreement content and review user acceptance.
        </p>
    </div>
</div>';

Display::display_footer();
