<?php
/* For licensing terms, see /license.txt */
/**
 * Show information about Mozilla OpenBadges
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.admin.openbadges
 */
use \ChamiloSession as Session;

$language_file = array('document');

$cidReset = true;

require_once '../inc/global.inc.php';

if (!api_is_platform_admin() || api_get_setting('allow_skills_tool') !== 'true') {
    api_not_allowed(true);
}

$this_section = SECTION_PLATFORM_ADMIN;

$skillId = intval($_GET['id']);

$objSkill = new Skill();
$skill = $objSkill->get($skillId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $params = array(
        'name' => $_POST['name'],
        'description' => $_POST['description'],
        'criteria' => $_POST['criteria'],
        'id' => $skillId
    );

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $dirPermissions = api_get_permissions_for_new_directories();
        $sysPath = api_get_path(SYS_PATH);
        $sysDataPath = api_get_path(SYS_DATA_PATH);

        $existsDataDirectory = is_dir($sysDataPath);

        if (!$existsDataDirectory) {
            $existsDataDirectory = api_create_protected_dir('data', $sysPath);
        }

        $fileDir = "badges/";
        $fileName = sha1($_POST['name']) . ".png";

        $existsBadgesDirectory = is_dir($sysDataPath . 'badges/');

        if (!$existsBadgesDirectory) {
            $existsBadgesDirectory = api_create_protected_dir('badges', $sysDataPath);
        }

        if ($existsBadgesDirectory) {
            if (!empty($skill['icon'])) {
                $iconFileAbsolutePath = $sysDataPath . $skill['icon'];
                $iconDirAbsolutePath = $sysDataPath . $fileDir;

                if (Security::check_abs_path($iconFileAbsolutePath, $iconDirAbsolutePath)) {
                    unlink($sysDataPath . $skill['icon']);
                }
            }

            $imageExtraField = new Image($_FILES['image']['tmp_name']);
            $imageExtraField->send_image($sysDataPath . $fileDir . $fileName, -1, 'png');

            $params['icon'] = $fileDir . $fileName;
        } else {
            Session::write('errorMessage', get_lang('UplUnableToSaveFile'));
        }
    }

    $objSkill->update($params);

    header('Location: ' . api_get_path(WEB_CODE_PATH) . 'admin/skill_badge_list.php');
    exit;
}

$interbreadcrumb = array(
    array(
        'url' => api_get_path(WEB_CODE_PATH) . 'admin/index.php',
        'name' => get_lang('Administration')
    ),
    array(
        'url' => api_get_path(WEB_CODE_PATH) . 'admin/skill_badge.php',
        'name' => get_lang('Badges')
    )
);

$tpl = new Template(get_lang('CreateBadge'));
$tpl->assign('platformAdminEmail', get_setting('emailAdministrator'));
$tpl->assign('skill', $skill);

$contentTemplate = $tpl->get_template('skill/badge_create.tpl');

$tpl->display($contentTemplate);
