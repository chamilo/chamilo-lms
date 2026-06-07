<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/Mobidico.php';

$this_section = SECTION_COURSES;
$current_course_tool = 'Mobidico';
$course_plugin = 'Mobidico';

api_protect_course_script(true);

$plugin = Mobidico::create();

if (!$plugin->isEnabled()) {
    api_not_allowed(true);
}

$translate = static function (string $key) use ($plugin): string {
    $text = (string) $plugin->get_lang($key);

    return '' !== $text ? $text : $key;
};

$escape = static function (string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
};

$launchUrl = $plugin->getLaunchUrl((int) api_get_user_id());

if (null !== $launchUrl) {
    Session::write('mobidico_last_launch_url', $launchUrl);
}

$title = $plugin->get_title();
$backUrl = api_get_course_url();

if (null === $launchUrl) {
    $content = '
        <section class="mx-auto max-w-4xl px-4 py-6">
            <div class="rounded-2xl border border-danger bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-start">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-danger/10">
                        <span class="mdi mdi-alert-circle-outline text-4xl text-danger" aria-hidden="true"></span>
                    </div>
                    <div class="flex-1">
                        <h2 class="m-0 text-title font-semibold text-gray-90">'.$escape($translate('UnableToLaunchMobidico')).'</h2>
                        <p class="mt-2 text-body-2 text-gray-50">'.$escape($translate('CheckMobidicoConfiguration')).'</p>
                        <div class="mt-6">
                            <a class="btn btn--plain-outline inline-flex items-center gap-2" href="'.$escape($backUrl).'">
                                <span class="mdi mdi-arrow-left" aria-hidden="true"></span>
                                '.$escape($translate('BackToCourse')).'
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    ';

    $tpl = new Template($title);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
    exit;
}

$content = '
    <section class="mx-auto max-w-4xl px-4 py-6">
        <div class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm">
            <div class="border-b border-gray-25 bg-support-1 p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white shadow-sm">
                            <span class="mdi mdi-book-open-page-variant text-5xl text-primary" aria-hidden="true"></span>
                        </div>
                        <div>
                            <h2 class="m-0 text-title font-semibold text-gray-90">'.$escape($title).'</h2>
                            <p class="mt-1 text-body-2 text-gray-50">'.$escape($translate('MobidicoReadySubtitle')).'</p>
                        </div>
                    </div>
                    <a
                        class="btn btn--primary inline-flex items-center justify-center gap-2"
                        data-mobidico-launch="1"
                        href="'.$escape($launchUrl).'"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <span class="mdi mdi-open-in-new" aria-hidden="true"></span>
                        '.$escape($translate('OpenMobidico')).'
                    </a>
                </div>
            </div>

            <div class="space-y-4 p-6">
                <div class="rounded-xl border border-gray-25 bg-white p-4">
                    <div class="flex items-start gap-3">
                        <span class="mdi mdi-check-circle-outline mt-0.5 text-2xl text-success" aria-hidden="true"></span>
                        <div>
                            <h3 class="m-0 text-base font-semibold text-gray-90">'.$escape($translate('MobidicoReadyTitle')).'</h3>
                            <p class="mt-1 text-body-2 text-gray-50">'.$escape($translate('MobidicoLaunchDescription')).'</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl bg-support-1 p-4 text-body-2 text-gray-50">
                    <span class="mdi mdi-information-outline mr-1 text-primary" aria-hidden="true"></span>
                    '.$escape($translate('MobidicoPopupHelp')).'
                </div>

                <div>
                    <a class="inline-flex items-center gap-2 text-body-2 font-semibold text-primary hover:underline" href="'.$escape($backUrl).'">
                        <span class="mdi mdi-arrow-left" aria-hidden="true"></span>
                        '.$escape($translate('BackToCourse')).'
                    </a>
                </div>
            </div>
        </div>
    </section>
';

$htmlHeadXtra[] = '
<script>
document.addEventListener("DOMContentLoaded", function () {
    var launchLink = document.querySelector("[data-mobidico-launch]");
    if (launchLink) {
        launchLink.click();
    }
});
</script>';

$tpl = new Template($title);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
