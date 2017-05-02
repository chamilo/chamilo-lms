<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Show information about Mozilla OpenBadges
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.admin.openbadges
 */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

if (!api_is_platform_admin() || api_get_setting('allow_skills_tool') !== 'true') {
    api_not_allowed(true);
}

$this_section = SECTION_PLATFORM_ADMIN;

$skillId = intval($_GET['id']);

$objSkill = new Skill();
$skill = $objSkill->get($skillId);

$htmlHeadXtra[] = '<link  href="'.api_get_path(WEB_LIBRARY_JS_PATH).'badge-studio/media/css/core.css" rel="stylesheet">';

// Add badge studio paths

$badgeStudio = [
    'core' => api_get_path(WEB_LIBRARY_JS_PATH).'badge-studio/',
    'media' => api_get_path(WEB_LIBRARY_JS_PATH).'badge-studio/media/',
    'templates' => api_get_path(WEB_LIBRARY_JS_PATH).'badge-studio/media/images/templates/',
    'masks' => api_get_path(WEB_LIBRARY_JS_PATH).'badge-studio/media/images/masks/',
    'script_js' => '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'badge-studio/media/js/studio.js?"></script>'
];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $params = array(
        'name' => $_POST['name'],
        'description' => $_POST['description'],
        'criteria' => $_POST['criteria'],
        'id' => $skillId
    );

    if ((isset($_FILES['image']) && $_FILES['image']['error'] == 0) || !empty($_POST['badge_studio_image'])) {
        $dirPermissions = api_get_permissions_for_new_directories();

        $fileName = sha1($_POST['name']);

        $badgePath = api_get_path(SYS_UPLOAD_PATH).'badges/';

        $existsBadgesDirectory = is_dir($badgePath);

        if (!$existsBadgesDirectory) {
            $existsBadgesDirectory = api_create_protected_dir('badges', api_get_path(SYS_UPLOAD_PATH));
        }

        if ($existsBadgesDirectory) {
            if (!empty($skill['icon'])) {
                $iconFileAbsolutePath = $badgePath.$skill['icon'];

                if (Security::check_abs_path($iconFileAbsolutePath, $badgePath)) {
                    unlink($badgePath.$skill['icon']);
                }
            }

            $skillImagePath = sprintf("%s%s.png", $badgePath, $fileName);

            if (!empty($_POST['badge_studio_image'])) {
                $badgeImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $_POST['badge_studio_image']));
                file_put_contents($skillImagePath, $badgeImage);
                $skillImage = new Image($skillImagePath);
            } else {
                $skillImage = new Image($_FILES['image']['tmp_name']);
            }

            $skillImage->send_image($skillImagePath, -1, 'png');

            $skillThumbPath = sprintf("%s%s-small.png", $badgePath, $fileName);

            $skillImageThumb = new Image($skillImagePath);
            $skillImageThumb->resize(ICON_SIZE_BIG);
            $skillImageThumb->send_image($skillThumbPath);

            $params['icon'] = sprintf("%s.png", $fileName);
        } else {
            Session::write('errorMessage', get_lang('UplUnableToSaveFile'));
        }
    }

    $objSkill->update($params);

    header('Location: '.api_get_path(WEB_CODE_PATH).'admin/skill_badge_list.php');
    exit;
}

$interbreadcrumb = array(
    array(
        'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
        'name' => get_lang('Administration')
    ),
    array(
        'url' => api_get_path(WEB_CODE_PATH).'admin/skill_badge.php',
        'name' => get_lang('Badges')
    )
);

$toolbar = Display::toolbarButton(
    get_lang('ManageSkills'),
    api_get_path(WEB_CODE_PATH).'admin/skill_list.php',
    'list',
    'primary',
    ['title' => get_lang('ManageSkills')]
);
$tpl = new Template(get_lang('CreateBadge'));
$tpl->assign('platformAdminEmail', api_get_setting('emailAdministrator'));
$tpl->assign('skill', $skill);
$tpl->assign('badge_studio', $badgeStudio);
$templateName = $tpl->get_template('skill/badge_create.tpl');
$contentTemplate = $tpl->fetch($templateName);
$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$toolbar])
);
$tpl->assign('content', $contentTemplate);
$tpl->display_one_col_template();
