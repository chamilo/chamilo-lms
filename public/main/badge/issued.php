<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Level;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Entity\SkillRelUserComment;
use Chamilo\CoreBundle\Framework\Container;

/**
 * Show information about the issued badge.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @author José Loguercio Silva <jose.loguercio@beeznest.com>
 * @author Julio Montoya
 */
require_once __DIR__.'/../inc/global.inc.php';

$issue = isset($_REQUEST['issue']) ? (int) $_REQUEST['issue'] : 0;
$export = isset($_REQUEST['export']);

if (empty($issue)) {
    api_not_allowed(true);
}

$entityManager = Database::getManager();
/** @var SkillRelUser $skillRelUser */
$skillRelUser = $entityManager->find(SkillRelUser::class, $issue);
$currentUrl = api_get_self().'?issue='.$issue;

if (null === $skillRelUser) {
    Display::addFlash(
        Display::return_message(
            get_lang('Skill not found'),
            'warning'
        )
    );
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$skillRepo = Container::getSkillRepository();
$skillLevelRepo = $entityManager->getRepository(Level::class);

$user = $skillRelUser->getUser();
$skill = $skillRelUser->getSkill();

if (null === $user || null === $skill) {
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
    ),
    'courses' => [],
];

$titleContent = sprintf(get_lang('I have achieved skill %s on %s'), $skill->getName(), api_get_setting('siteName'));

// Open Graph Markup
$htmlHeadXtra[] = "
    <meta property='og:type' content='article' />
    <meta property='og:title' content='".$titleContent."' />
    <meta property='og:url' content='".api_get_path(WEB_PATH)."badge/".$issue."' />
    <meta property='og:description' content='".$skill->getDescription()."' />
    <meta property='og:image' content='".$skillInfo['badge_image']."' />
";

$currentUser = api_get_user_entity();
$allowExport = false;
$allowComment = false;
if (null !== $currentUser) {
    $allowExport = $currentUser->getId() === $user->getId() || api_is_platform_admin();
    $allowComment = SkillModel::userCanAddFeedbackToUser($currentUser, $user);
}

$skillRelUserDate = api_get_local_time($skillRelUser->getAcquiredSkillAt());
$currentSkillLevel = get_lang('No level acquired yet');
if ($skillRelUser->getAcquiredLevel()) {
    $currentSkillLevel = $skillLevelRepo->find(['id' => $skillRelUser->getAcquiredLevel()])->getName();
}

$author = api_get_user_info($skillRelUser->getArgumentationAuthorId());
$tempDate = DateTime::createFromFormat('Y-m-d H:i:s', $skillRelUserDate);
$linkedinOrganizationId = api_get_configuration_value('linkedin_organization_id');
if ((false === $linkedinOrganizationId)) {
    $linkedinOrganizationId = null;
}

$skillRelUserInfo = [
    'id' => $skillRelUser->getId(),
    'datetime' => api_format_date($skillRelUserDate, DATE_TIME_FORMAT_SHORT),
    'year' => $tempDate->format('Y'),
    'month' => $tempDate->format('m'),
    'linkedin_organization_id' => $linkedinOrganizationId,
    'acquired_level' => $currentSkillLevel,
    'argumentation_author_id' => $skillRelUser->getArgumentationAuthorId(),
    'argumentation_author_name' => $author['complete_name'],
    'argumentation' => $skillRelUser->getArgumentation(),
    'source_name' => $skillRelUser->getSourceName(),
    'user_id' => $skillRelUser->getUser()->getId(),
    'user_complete_name' => UserManager::formatUserFullName($skillRelUser->getUser()),
    'skill_id' => $skillRelUser->getSkill()->getId(),
    'skill_badge_image' => SkillModel::getWebIconPath($skillRelUser->getSkill()),
    'skill_name' => $skillRelUser->getSkill()->getName(),
    'skill_short_code' => $skillRelUser->getSkill()->getShortCode(),
    'skill_description' => $skillRelUser->getSkill()->getDescription(),
    'skill_criteria' => $skillRelUser->getSkill()->getCriteria(),
    'badge_assertion' => SkillRelUserModel::getAssertionUrl($skillRelUser),
    'comments' => [],
    'feedback_average' => $skillRelUser->getAverage(),
];

$skillRelUserComments = $skillRelUser->getComments(true);

$userId = $skillRelUserInfo['user_id'];
$skillId = $skillRelUserInfo['skill_id'];

/** @var SkillRelUserComment $comment */
foreach ($skillRelUserComments as $comment) {
    $commentDate = api_get_local_time($comment->getFeedbackDateTime());
    $skillRelUserInfo['comments'][] = [
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
    $formAcquiredLevel->addHidden('user', (string) $skillRelUser->getUser()->getId());
    $formAcquiredLevel->addHidden('issue', (string) $skillRelUser->getId());
    $formAcquiredLevel->addButtonSave(get_lang('Save'));

    if ($formAcquiredLevel->validate() && $allowComment) {
        $values = $formAcquiredLevel->exportValues();
        $level = $skillLevelRepo->find($values['acquired_level']);
        $skillRelUser->setAcquiredLevel($level);

        $entityManager->persist($skillRelUser);
        $entityManager->flush();
        Display::addFlash(Display::return_message(get_lang('Saved')));

        header('Location: '.SkillRelUserModel::getIssueUrl($skillRelUser));
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
$form->addHidden('user', (string) $skillRelUser->getUser()->getId());
$form->addHidden('issue', (string) $skillRelUser->getId());
$form->addButtonSend(get_lang('Send message'));

if ($form->validate() && $allowComment && $allowToEdit) {
    $values = $form->exportValues();
    $skillUserComment = new SkillRelUserComment();
    $skillUserComment
        ->setFeedbackDateTime(new DateTime())
        ->setFeedbackGiver($currentUser)
        ->setFeedbackText($values['comment'])
        ->setFeedbackValue($values['value'] ? $values['value'] : null)
        ->setSkillRelUser($skillRelUser)
    ;

    $entityManager->persist($skillUserComment);
    $entityManager->flush();
    Display::addFlash(Display::return_message(get_lang('Added')));

    header('Location: '.SkillRelUserModel::getIssueUrl($skillRelUser));
    exit;
}

$personalBadge = '';
if ($allowExport) {
    SkillModel::setBackPackJs($htmlHeadXtra);
    $personalBadge = api_get_self().'?issue='.$issue.'&export=1';
    if ($export) {
        SkillModel::exportBadge($skill, $skillRelUser, $currentUrl);
    }
}

$template = new Template(get_lang('Issued badge information'));
$template->assign('issue_info', $skillRelUserInfo);
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
$template->assign('personal_badge', $personalBadge);
$template->assign('show_level', $showLevels);
$content = $template->fetch($template->get_template('skill/issued.tpl'));
$template->assign('header', get_lang('Issued badge information'));
$template->assign('content', $content);
$template->display_one_col_template();
