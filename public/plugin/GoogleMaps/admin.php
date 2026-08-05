<?php

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/config.php';

api_protect_admin_script();

$plugin = GoogleMapsPlugin::create();

if (!$plugin->isEnabled()) {
    api_not_allowed(true);
}

$templateName = $plugin->get_lang('plugin_title');
$tpl = new Template($templateName);

$content = '
    <section class="bg-white rounded-2xl shadow-sm border p-6 mb-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="text-sm font-semibold uppercase text-primary">'.$plugin->get_lang('plugin_title').'</div>
                <h1 class="text-2xl font-bold">'.$plugin->get_lang('UsersCoordinatesMap').'</h1>
                <p class="text-gray-500">'.$plugin->get_lang('GoogleMapsAdminIntro').'</p>
            </div>
        </div>
    </section>
';

$content .= $plugin->renderAdminSummary();

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
