<?php
/* For licensing terms, see /license.txt */

/**
 * This script opens the CustomCertificate plugin entry point.
 */

$course_plugin = 'CustomCertificate';
require_once __DIR__.'/config.php';

$plugin = CustomCertificatePlugin::create();

if (!$plugin->isEnabled(true)) {
    api_not_allowed(true, $plugin->get_lang('ToolDisabled'));
}

if (!api_is_platform_admin() && !api_is_teacher()) {
    Display::addFlash(Display::return_message($plugin->get_lang('OnlyAdminPlatformOrTeacher'), 'warning'));

    $cidReq = api_get_cidreq();
    if (!empty($cidReq)) {
        header('Location: '.api_get_path(WEB_CODE_PATH).'course_info/infocours.php?'.$cidReq);
        exit;
    }

    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$url = 'src/index.php?';
$courseId = api_get_course_int_id();
$cidReq = api_get_cidreq();

if ($courseId > 0 && !empty($cidReq)) {
    $courseInfo = api_get_course_info();
    $enableCourse = 1 == api_get_course_setting('customcertificate_course_enable', $courseInfo);
    $useDefault = 1 == api_get_course_setting('use_certificate_default', $courseInfo);

    if (!$enableCourse && !$useDefault) {
        Display::addFlash(Display::return_message($plugin->get_lang('ToolDisabledCourse'), 'warning'));
        header('Location: '.api_get_path(WEB_CODE_PATH).'course_info/infocours.php?'.$cidReq);
        exit;
    }

    if ($enableCourse && $useDefault) {
        Display::addFlash(Display::return_message($plugin->get_lang('ToolUseDefaultSettingCourse'), 'warning'));
        header('Location: '.api_get_path(WEB_CODE_PATH).'course_info/infocours.php?'.$cidReq);
        exit;
    }

    $url .= $cidReq;
} else {
    $url .= 'default=1';
}

header('Location: '.$url);
exit;
