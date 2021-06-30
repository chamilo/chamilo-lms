<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Entity\SkillRelUserComment;
use SkillRelUserModel as SkillRelUserManager;

/**
 * Show information about the issued badge.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @author José Loguercio Silva <jose.loguercio@beeznest.com>
 */
require_once __DIR__.'/../inc/global.inc.php';

$issue = isset($_REQUEST['issue']) ? (int) $_REQUEST['issue'] : 0;

if (empty($issue)) {
    api_not_allowed(true);
}

$entityManager = Database::getManager();
/** @var SkillRelUser $skillIssue */
$skillIssue = $entityManager->find(SkillRelUser::class, $issue);

if (!$skillIssue) {
    Display::addFlash(
        Display::return_message(
            get_lang('Skill not found'),
            'warning'
        )
    );
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$skillRepo = $entityManager->getRepository(\Chamilo\CoreBundle\Entity\Skill::class);
$skillLevelRepo = $entityManager->getRepository(\Chamilo\CoreBundle\Entity\Level::class);

$user = $skillIssue->getUser();
$skill = $skillIssue->getSkill();

if (!$user || !$skill) {
    Display::addFlash(
        Display::return_message(get_lang('No results found'), 'warning')
    );

    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

if (!SkillModel::isToolAvailable()) {
    api_not_allowed(true);
}

$showLevels = false === api_get_configuration_value('hide_skill_levels');

$skillInfo = [
    'id' => $skill->getId(),
    'name' => $skill->getName(),
    'short_code' => $skill->getShortCode(),
    'description' => $skill->getDescription(),
    'criteria' => $skill->getCriteria(),
    'badge_image' => Display::return_icon(
        'badges-default.png',
        null,
        null,
        ICON_SIZE_BIG,
        null,
        true
    ), // SkillModel::getWebIconPath($skill),
    'courses' => [],
];

$titleContent = sprintf(get_lang('I have achieved skill %s on %s'), $skillInfo['name'], api_get_setting('siteName'));

// Open Graph Markup
$htmlHeadXtra[] = "
    <meta property='og:type' content='article' />
    <meta property='og:title' content='".$titleContent."' />
    <meta property='og:url' content='".api_get_path(WEB_PATH)."badge/".$issue."' />
    <meta property='og:description' content='".$skillInfo['description']."' />
    <meta property='og:image' content='".$skillInfo['badge_image']."' />
";

$currentUserId = api_get_user_id();
$currentUser = api_get_user_entity($currentUserId);
$allowExport = $currentUser ? $currentUser->getId() === $user->getId() : false;

$allowComment = $currentUser ? SkillModel::userCanAddFeedbackToUser($currentUser, $user) : false;
$skillIssueDate = api_get_local_time($skillIssue->getAcquiredSkillAt());
$currentSkillLevel = get_lang('No level acquired yet');
if ($skillIssue->getAcquiredLevel()) {
    $currentSkillLevel = $skillLevelRepo->find(['id' => $skillIssue->getAcquiredLevel()])->getName();
}

$author = api_get_user_info($skillIssue->getArgumentationAuthorId());
$tempDate = DateTime::createFromFormat('Y-m-d H:i:s', $skillIssueDate);
$linkedinOrganizationId = api_get_configuration_value('linkedin_organization_id');
if (($linkedinOrganizationId === false)) {
    $linkedinOrganizationId = null;
}

$skillIssueInfo = [
    'id' => $skillIssue->getId(),
    'datetime' => api_format_date($skillIssueDate, DATE_TIME_FORMAT_SHORT),
    'year' => $tempDate->format('Y'),
    'month' => $tempDate->format('m'),
    'linkedin_organization_id' => $linkedinOrganizationId,
    'acquired_level' => $currentSkillLevel,
    'argumentation_author_id' => $skillIssue->getArgumentationAuthorId(),
    'argumentation_author_name' => $author['complete_name'],
    'argumentation' => $skillIssue->getArgumentation(),
    'source_name' => $skillIssue->getSourceName(),
    'user_id' => $skillIssue->getUser()->getId(),
    'user_complete_name' => UserManager::formatUserFullName($skillIssue->getUser()),
    'skill_id' => $skillIssue->getSkill()->getId(),
    'skill_badge_image' => Display::return_icon(
        'badges-default.png',
        null,
        null,
        ICON_SIZE_BIG,
        null,
        true
    ), // SkillModel::getWebIconPath($skillIssue->getSkill()),
    'skill_name' => $skillIssue->getSkill()->getName(),
    'skill_short_code' => $skillIssue->getSkill()->getShortCode(),
    'skill_description' => $skillIssue->getSkill()->getDescription(),
    'skill_criteria' => $skillIssue->getSkill()->getCriteria(),
    'badge_assertion' => SkillRelUserManager::getAssertionUrl($skillIssue),
    'comments' => [],
    'feedback_average' => $skillIssue->getAverage(),
];

$skillIssueComments = $skillIssue->getComments(true);

$userId = $skillIssueInfo['user_id'];
$skillId = $skillIssueInfo['skill_id'];

/** @var SkillRelUserComment $comment */
foreach ($skillIssueComments as $comment) {
    $commentDate = api_get_local_time($comment->getFeedbackDateTime());
    $skillIssueInfo['comments'][] = [
        'text' => $comment->getFeedbackText(),
        'value' => $comment->getFeedbackValue(),
        'giver_complete_name' => UserManager::formatUserFullName($comment->getFeedbackGiver()),
        'datetime' => api_format_date($commentDate, DATE_TIME_FORMAT_SHORT),
    ];
}

$acquiredLevel = [];
$profile = $skillRepo->find($skillId)->getProfile();

if (!$profile) {
    $skillRelSkill = new SkillRelSkillModel();
    $parents = $skillRelSkill->getSkillParents($skillId);

    krsort($parents);

    foreach ($parents as $parent) {
        $skillParentId = $parent['skill_id'];
        $profile = $skillRepo->find($skillParentId)->getProfile();

        if ($profile) {
            break;
        }

        if (0 == $parent['parent_id']) {
            $profile = $skillLevelRepo->findAll();
            if ($profile) {
                $profile = $profile[0];
            }
        }
    }
}

if ($profile) {
    $profileId = $profile->getId();
    $levels = $skillLevelRepo->findBy([
        'profile' => $profileId,
    ]);

    $profileLevels = [];
    foreach ($levels as $level) {
        $profileLevels[$level->getPosition()][$level->getId()] = $level->getName();
    }

    ksort($profileLevels); // Sort the array by Position.
    foreach ($profileLevels as $profileLevel) {
        $profileId = key($profileLevel);
        $acquiredLevel[$profileId] = $profileLevel[$profileId];
    }
}

$allowToEdit = SkillModel::isAllowed($user->getId(), false);

if ($showLevels && $allowToEdit) {
    $formAcquiredLevel = new FormValidator('acquired_level');
    $formAcquiredLevel->addSelect('acquired_level', get_lang('Level acquired'), $acquiredLevel);
    $formAcquiredLevel->addHidden('user', (string) $skillIssue->getUser()->getId());
    $formAcquiredLevel->addHidden('issue', (string) $skillIssue->getId());
    $formAcquiredLevel->addButtonSave(get_lang('Save'));

    if ($formAcquiredLevel->validate() && $allowComment) {
        $values = $formAcquiredLevel->exportValues();
        $level = $skillLevelRepo->find($values['acquired_level']);
        $skillIssue->setAcquiredLevel($level);

        $entityManager->persist($skillIssue);
        $entityManager->flush();
        Display::addFlash(Display::return_message(get_lang('Saved')));

        header('Location: '.SkillRelUserManager::getIssueUrl($skillIssue));
        exit;
    }
}

$form = new FormValidator('comment');
$form->addTextarea('comment', get_lang('New comment'), ['rows' => 4]);
$form->applyFilter('comment', 'trim');
$form->addRule('comment', get_lang('Required field'), 'required');
$form->addSelect(
    'value',
    [
        get_lang('Value'),
        get_lang('On a grade of 1 to 10, how well did you observe that this person could put this skill in practice?'),
    ],
    ['-', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
);
$form->addHidden('user', (string) $skillIssue->getUser()->getId());
$form->addHidden('issue', (string) $skillIssue->getId());
$form->addButtonSend(get_lang('Send message'));

if ($form->validate() && $allowComment && $allowToEdit) {
    $values = $form->exportValues();
    $skillUserComment = new SkillRelUserComment();
    $skillUserComment
        ->setFeedbackDateTime(new DateTime())
        ->setFeedbackGiver($currentUser)
        ->setFeedbackText($values['comment'])
        ->setFeedbackValue($values['value'] ? $values['value'] : null)
        ->setSkillRelUser($skillIssue)
    ;

    $entityManager->persist($skillUserComment);
    $entityManager->flush();
    Display::addFlash(Display::return_message(get_lang('Added')));

    header('Location: '.SkillRelUserManager::getIssueUrl($skillIssue));
    exit;
}

$badgeInfoError = '';
$personalBadge = '';
if ($allowExport) {
    $backpack = 'https://backpack.openbadges.org/';
    $configBackpack = api_get_setting('openbadges_backpack');

    if (0 !== strcmp($backpack, $configBackpack)) {
        $backpack = $configBackpack;
        if ('/' !== substr($backpack, -1)) {
            $backpack .= '/';
        }
    }

    $htmlHeadXtra[] = '<script src="'.$backpack.'issuer.js"></script>';
    $objSkill = new SkillModel();
    $assertionUrl = $skillIssueInfo['badge_assertion'];
    $skills = $objSkill->get($skillId);
//    $unbakedBadge = api_get_path(SYS_UPLOAD_PATH).'badges/'.$skills['icon'];
//    if (!is_file($unbakedBadge)) {
        $unbakedBadge = api_get_path(SYS_PUBLIC_PATH).'img/icons/128/badges-default.png';
//    }

//    $unbakedBadge = file_get_contents($unbakedBadge);
    $badgeInfoError = false;
    $personalBadge = $unbakedBadge;
//    $png = new PNGImageBaker($unbakedBadge);
//
//    if ($png->checkChunks("tEXt", "openbadges")) {
//        $bakedInfo = $png->addChunk("tEXt", "openbadges", $assertionUrl);
//        $bakedBadge = UserManager::getUserPathById($userId, "system");
//        $bakedBadge = $bakedBadge.'badges';
//        if (!file_exists($bakedBadge)) {
//            mkdir($bakedBadge, api_get_permissions_for_new_directories(), true);
//        }
//        $skillRelUserId = $skillIssueInfo['id'];
//        if (!file_exists($bakedBadge."/badge_".$skillRelUserId)) {
//            file_put_contents($bakedBadge."/badge_".$skillRelUserId.".png", $bakedInfo);
//        }
//
//        // Process to validate a baked badge
//        $badgeContent = file_get_contents($bakedBadge."/badge_".$skillRelUserId.".png");
//        $verifyBakedBadge = $png->extractBadgeInfo($badgeContent);
//        if (!is_array($verifyBakedBadge)) {
//            $badgeInfoError = true;
//        }
//
//        if (!$badgeInfoError) {
//            $personalBadge = UserManager::getUserPathById($userId, 'web');
//            $personalBadge = $personalBadge."badges/badge_".$skillRelUserId.".png";
//        }
//    }
}

$template = new Template(get_lang('Issued badge information'));
$template->assign('issue_info', $skillIssueInfo);
$template->assign('allow_comment', $allowComment);
$template->assign('allow_export', $allowExport);

$commentForm = '';
if ($allowComment && $allowToEdit) {
    $commentForm = $form->returnForm();
}
$template->assign('comment_form', $commentForm);

$levelForm = '';
if ($showLevels && $allowToEdit && $formAcquiredLevel) {
    $levelForm = $formAcquiredLevel->returnForm();
}
$template->assign('acquired_level_form', $levelForm);
$template->assign('badge_error', $badgeInfoError);
$template->assign('personal_badge', $personalBadge);
$template->assign('show_level', $showLevels);
$content = $template->fetch($template->get_template('skill/issued.tpl'));
$template->assign('header', get_lang('Issued badge information'));
$template->assign('content', $content);
$template->display_one_col_template();
