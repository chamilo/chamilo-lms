<?php
/* For licensing terms, see /license.txt */

/**
 * Show information about Mozilla OpenBadges.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();
Skill::isAllowed();

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
    'script_js' => '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'badge-studio/media/js/studio.js?"></script>',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $params = [
        'id' => $skillId,
    ];

    if ((isset($_FILES['image']) && $_FILES['image']['error'] == 0) ||
        !empty($_POST['badge_studio_image'])
    ) {
        $dirPermissions = api_get_permissions_for_new_directories();
        $fileName = sha1($skill['name']);
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
                $badgeImage = base64_decode(
                    preg_replace('#^data:image/\w+;base64,#i', '', $_POST['badge_studio_image'])
                );
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
            Display::addFlash(Display::return_message(get_lang('UplUnableToSaveFile')), 'warning');
        }
    }

    Display::addFlash(Display::return_message(get_lang('Updated')));
    $objSkill->update($params);
    header('Location: '.api_get_path(WEB_CODE_PATH).'admin/skill_list.php');
    exit;
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
    'name' => get_lang('Administration'),
];
$interbreadcrumb[] = ['url' => 'skill_list.php', 'name' => get_lang('ManageSkills')];

$toolbar = $objSkill->getToolBar();

$tpl = new Template(get_lang('CreateBadge'));
$tpl->assign('platformAdminEmail', api_get_setting('emailAdministrator'));
$tpl->assign('skill', $skill);
$tpl->assign('badge_studio', $badgeStudio);
$templateName = $tpl->get_template('skill/badge_create.tpl');
$contentTemplate = $tpl->fetch($templateName);
$tpl->assign('content', $toolbar.$contentTemplate);
$tpl->display_one_col_template();
