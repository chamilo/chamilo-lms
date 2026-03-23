<?php

/* For license terms, see /license.txt */

/**
 * Entry point for the Test2Pdf course tool.
 */

$course_plugin = 'test2pdf';
require_once __DIR__.'/config.php';

api_protect_course_script(true);

$tool_name = get_lang('Test2pdf');
$plugin = Test2pdfPlugin::create();

if (!test2pdf_is_plugin_active()) {
    Display::addFlash(
        Display::return_message(
            $plugin->get_lang('PluginDisabledFromAdminPanel'),
            'warning'
        )
    );

    header('Location: '.test2pdf_get_course_home_url());
    exit;
}

header('Location: src/view-pdf.php?'.api_get_cidreq());
exit;
