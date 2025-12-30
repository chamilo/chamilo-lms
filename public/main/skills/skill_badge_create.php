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
$assetRepo = Container::getAssetRepository();

$skill = $skillRepo->find($skillId);

if (empty($skill)) {
    // Safety: avoid rendering a broken page when an invalid skill id is provided
    Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
    api_location(api_get_path(WEB_CODE_PATH).'skills/skill_list.php');
}

// Badge Studio CSS
$htmlHeadXtra[] = '<link href="'.api_get_path(WEB_LIBRARY_JS_PATH).'badge-studio/media/css/core.css" rel="stylesheet">';

// Add badge studio paths
$badgeStudio = [
    'core' => api_get_path(WEB_LIBRARY_JS_PATH).'badge-studio/',
    'media' => api_get_path(WEB_LIBRARY_JS_PATH).'badge-studio/media/',
    'templates' => api_get_path(WEB_LIBRARY_JS_PATH).'badge-studio/media/images/templates/',
    'masks' => api_get_path(WEB_LIBRARY_JS_PATH).'badge-studio/media/images/masks/',
    'script_js' => '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'badge-studio/media/js/studio.js?"></script>',
];

// Compute current badge URL for preview (same approach as SkillModel::getAllSkills)
$badgeUrl = '';
if ($skill && method_exists($skill, 'getAsset') && $skill->getAsset()) {
    $badgeUrl = (string) $assetRepo->getAssetUrl($skill->getAsset());

    // Optional cache-buster to avoid stale cached images after updates
    if (!empty($badgeUrl)) {
        $badgeUrl .= (strpos($badgeUrl, '?') !== false ? '&' : '?').'v='.time();
    }
}

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    $hasStudio = !empty($_POST['badge_studio_image'] ?? '');

    $hasUpload = isset($_FILES['image'])
        && is_array($_FILES['image'])
        && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;

    // If nothing was selected, redirect back with no changes
    if (!$hasStudio && !$hasUpload) {
        Display::addFlash(Display::return_message(get_lang('Error'), 'warning'));
        api_location(api_get_path(WEB_CODE_PATH).'skills/skill_list.php');
    }

    // Remove previous asset (if any) to avoid orphaned files and stale references
    $skillRepo->deleteAsset($skill);

    // Use a unique title to help avoid browser cache showing an old image
    $safeTitle = preg_replace('/[^a-z0-9\-_]+/i', '-', (string) $skill->getTitle());
    $safeTitle = trim((string) $safeTitle, '-_');
    if (empty($safeTitle)) {
        $safeTitle = 'skill-badge';
    }

    $title = sprintf('%s-%s.png', $safeTitle, date('YmdHis'));

    $asset = (new Asset())
        ->setCategory(Asset::SKILL)
        ->setTitle($title);

    if ($hasStudio) {
        // Expected format: "data:image/png;base64,AAAA..."
        $raw = (string) ($_POST['badge_studio_image'] ?? '');
        $raw = preg_replace('#^data:image/\w+;base64,#i', '', $raw);

        $badgeImage = base64_decode($raw, true);
        if (false === $badgeImage || empty($badgeImage)) {
            // Safety: invalid base64 payload
            Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
            api_location(api_get_path(WEB_CODE_PATH).'skills/skill_list.php');
        }

        // Force PNG mime type when coming from studio
        $asset = $assetRepo->createFromString($asset, 'image/png', $badgeImage);
    } else {
        // Only executed when a real file was uploaded successfully
        $asset = $assetRepo->createFromRequest($asset, $_FILES['image']);
    }

    $skill->setAsset($asset);
    $skillRepo->update($skill);

    Display::addFlash(Display::return_message(get_lang('Update successful')));

    api_location(api_get_path(WEB_CODE_PATH).'skills/skill_list.php');
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
$tpl->assign('badge_url', $badgeUrl);
$tpl->assign('badge_studio', $badgeStudio);
$tpl->assign('current_url', api_get_self().'?id='.$skillId);

$templateName = $tpl->get_template('skill/badge_create.html.twig');

$contentTemplate = $tpl->fetch($templateName);
$tpl->assign('content', $toolbar.$contentTemplate);
$tpl->display_one_col_template();
