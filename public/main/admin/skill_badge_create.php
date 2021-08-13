<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Framework\Container;

/**
 * Show information about Mozilla OpenBadges.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @author Julio Montoya
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();
SkillModel::isAllowed();

$this_section = SECTION_PLATFORM_ADMIN;

$skillId = (int) ($_GET['id'] ?? 0);
$skillRepo = Container::getSkillRepository();
$skill = $skillRepo->find($skillId);

$htmlHeadXtra[] = '<link href="'.api_get_path(WEB_LIBRARY_JS_PATH).'badge-studio/media/css/core.css" rel="stylesheet">';

// Add badge studio paths
$badgeStudio = [
    'core' => api_get_path(WEB_LIBRARY_JS_PATH).'badge-studio/',
    'media' => api_get_path(WEB_LIBRARY_JS_PATH).'badge-studio/media/',
    'templates' => api_get_path(WEB_LIBRARY_JS_PATH).'badge-studio/media/images/templates/',
    'masks' => api_get_path(WEB_LIBRARY_JS_PATH).'badge-studio/media/images/masks/',
    'script_js' => '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'badge-studio/media/js/studio.js?"></script>',
];

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    if ((isset($_FILES['image']) && 0 == $_FILES['image']['error']) ||
        (isset($_POST['badge_studio_image']) && !empty($_POST['badge_studio_image']))
    ) {
        $assetRepo = Container::getAssetRepository();
        $skillRepo->deleteAsset($skill);
        $title = sprintf("%s.png", $skill->getName());

        $asset = (new Asset())
            ->setCategory(Asset::SKILL)
            ->setTitle($title)
        ;

        if (isset($_POST['badge_studio_image']) && !empty($_POST['badge_studio_image'])) {
            $badgeImage = base64_decode(
                preg_replace('#^data:image/\w+;base64,#i', '', $_POST['badge_studio_image'])
            );
            $asset = $assetRepo->createFromString($asset, 'image/png', $badgeImage);
        }

        if (isset($_FILES['image'])) {
            $asset = $assetRepo->createFromRequest($asset, $_FILES['image']);
        }

        $skill->setAsset($asset);
        $skillRepo->update($skill);

        Display::addFlash(Display::return_message(get_lang('Update successful')));
    }

    api_location(api_get_path(WEB_CODE_PATH).'admin/skill_list.php');
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
    'name' => get_lang('Administration'),
];
$interbreadcrumb[] = ['url' => 'skill_list.php', 'name' => get_lang('Manage skills')];

$objSkill = new SkillModel();
$toolbar = $objSkill->getToolBar();

$tpl = new Template(get_lang('Create badge'));
$tpl->assign('platformAdminEmail', api_get_setting('emailAdministrator'));
$tpl->assign('skill', $skill);
$tpl->assign('badge_studio', $badgeStudio);
$tpl->assign('current_url', api_get_self().'?id='.$skillId);
$templateName = $tpl->get_template('skill/badge_create.tpl');
$contentTemplate = $tpl->fetch($templateName);
$tpl->assign('content', $toolbar.$contentTemplate);
$tpl->display_one_col_template();
