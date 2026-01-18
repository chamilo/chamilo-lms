<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Level;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Entity\SkillRelUserComment;
use Chamilo\CoreBundle\Framework\Container;

/**
 * Show information about all issued badges with same skill by user.
 *
 * @author José Loguercio Silva <jose.loguercio@beeznest.com>
 * @author Julio Montoya
 */
require_once __DIR__.'/../inc/global.inc.php';

$userId = isset($_GET['user']) ? (int) $_GET['user'] : 0;
$skillId = isset($_GET['skill']) ? (int) $_GET['skill'] : 0;
$export = isset($_REQUEST['export']);

if (!$userId || !$skillId) {
    api_not_allowed(true);
}

$origin = isset($_GET['origin']) ? (string) $_GET['origin'] : '';
$originUrl = SkillModel::sanitizeInternalUrl($origin);

$query = [
    'user' => $userId,
    'skill' => $skillId,
];
if ($originUrl) {
    $query['origin'] = $originUrl;
}

$currentUrl = api_get_self().'?'.http_build_query($query);

SkillModel::isAllowed($userId);

// Default badge image (fallback when skill has no badge).
$defaultBadge = api_get_path(WEB_PATH).'img/icons/32/badges-default.png';

$em = Database::getManager();
$user = api_get_user_entity($userId);
$skillRepo = Container::getSkillRepository();
$skill = $skillRepo->find($skillId);
$currentUserId = api_get_user_id();

if (null === $user || null === $skill) {
    Display::addFlash(
        Display::return_message(get_lang('No results found'), 'error')
    );

    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$skillUserRepo = $em->getRepository(SkillRelUser::class);
$skillLevelRepo = $em->getRepository(Level::class);

$userSkills = $skillUserRepo->findBy([
    'user' => $user,
    'skill' => $skill,
]);

$currentUser = api_get_user_entity();

$allowDownloadExport = false;
$allowComment = false;
if (null !== $currentUser) {
    $allowDownloadExport = $currentUser->getId() === $user->getId() || api_is_platform_admin();
    $allowComment = SkillModel::userCanAddFeedbackToUser($currentUser, $user);
}

/**
 * Direct download for a specific issue (recommended).
 * Also keep support for legacy export=1 (optional ?issue=ID).
 */
$downloadIssueId = isset($_GET['download_issue']) ? (int) $_GET['download_issue'] : 0;

// Legacy export support (kept to avoid breaking old URLs)
$legacyIssueId = isset($_GET['issue']) ? (int) $_GET['issue'] : 0;
if (!$downloadIssueId && $export) {
    if ($legacyIssueId > 0) {
        $downloadIssueId = $legacyIssueId;
    } elseif (count($userSkills) === 1 && !empty($userSkills[0])) {
        $downloadIssueId = (int) $userSkills[0]->getId();
    } else {
        Display::addFlash(
            Display::return_message(
                get_lang('Please select a specific badge issue to download'),
                'warning'
            )
        );
        api_location($currentUrl);
    }
}

// If a download was requested, serve PNG and exit (no HTML response)
if ($downloadIssueId && $allowDownloadExport) {
    /** @var SkillRelUser|null $issue */
    $issue = $skillUserRepo->find($downloadIssueId);

    // Ensure the issue belongs to the requested user+skill
    if ($issue && $issue->getUser()->getId() === $user->getId() && $issue->getSkill()->getId() === $skill->getId()) {
        SkillModel::exportBadge($skill, $issue, $currentUrl);
        exit;
    }

    api_not_allowed(true);
}

$allUserBadges = [];
$backpackJsAdded = false;

/** @var SkillRelUser $skillRelUser */
foreach ($userSkills as $index => $skillRelUser) {
    $skillRelUserDate = api_get_local_time($skillRelUser->getAcquiredSkillAt());
    $currentSkillLevel = get_lang('No level acquired yet');
    if ($skillRelUser->getAcquiredLevel()) {
        $levelEntity = $skillLevelRepo->find($skillRelUser->getAcquiredLevel()->getId());
        if ($levelEntity) {
            $currentSkillLevel = $levelEntity->getTitle();
        }
    }
    $argumentationAuthor = api_get_user_info($skillRelUser->getArgumentationAuthorId());

    // Resolve badge image and fallback to default when missing.
    $badgeImage = SkillModel::getWebIconPath($skillRelUser->getSkill());

    // Some skills may return an empty path or the legacy "unknown" image.
    if (empty($badgeImage) || false !== strpos($badgeImage, 'unknown.png')) {
        $badgeImage = $defaultBadge;
    }

    $skillRelUserInfo = [
        'id' => $skillRelUser->getId(),
        'datetime' => api_format_date($skillRelUserDate, DATE_TIME_FORMAT_SHORT),
        'acquired_level' => $currentSkillLevel,
        'argumentation_author_id' => $skillRelUser->getArgumentationAuthorId(),
        'argumentation_author_name' => api_get_person_name(
            $argumentationAuthor['firstname'],
            $argumentationAuthor['lastname']
        ),
        'argumentation' => $skillRelUser->getArgumentation(),
        'source_name' => $skillRelUser->getSourceName(),
        'user_id' => $skillRelUser->getUser()->getId(),
        'user_complete_name' => UserManager::formatUserFullName($skillRelUser->getUser()),
        'skill_id' => $skillRelUser->getSkill()->getId(),
        'skill_badge_image' => $badgeImage,
        'skill_name' => $skillRelUser->getSkill()->getTitle(),
        'skill_short_code' => $skillRelUser->getSkill()->getShortCode(),
        'skill_description' => $skillRelUser->getSkill()->getDescription(),
        'skill_criteria' => $skillRelUser->getSkill()->getCriteria(),
        'badge_assertion' => SkillRelUserModel::getAssertionUrl($skillRelUser),
        'comments' => [],
        'feedback_average' => $skillRelUser->getAverage(),
    ];

    $userId = $skillRelUserInfo['user_id'];
    $skillId = $skillRelUserInfo['skill_id'];

    $skillRelUserComments = $skillRelUser->getComments(true);
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
    $profileLevels = []; // Prevent leaking values between loop iterations

    $profile = $skillRepo->find($skillId)->getLevelProfile();

    if (!$profile) {
        $skillRelSkill = new SkillRelSkillModel();
        $parents = $skillRelSkill->getSkillParents($skillId);

        krsort($parents);

        foreach ($parents as $parent) {
            $skillParentId = $parent['skill_id'];
            $profile = $skillRepo->find($skillParentId)->getLevelProfile();

            if ($profile) {
                break;
            }

            if (!$profile && 0 == $parent['parent_id']) {
                $profile = $skillLevelRepo->findAll();
                $profile = !empty($profile) ? $profile[0] : [];
            }
        }
    }

    if ($profile) {
        $profileId = $profile->getId();
        $levels = $skillLevelRepo->findBy([
            'profile' => $profileId,
        ]);

        foreach ($levels as $level) {
            $profileLevels[$level->getPosition()][$level->getId()] = $level->getTitle();
        }

        ksort($profileLevels); // Sort the array by Position.

        foreach ($profileLevels as $profileLevel) {
            $profileId = key($profileLevel);
            $acquiredLevel[$profileId] = $profileLevel[$profileId];
        }
    }

    $formAcquiredLevel = new FormValidator('acquired_level'.$skillRelUser->getId(), 'post', $currentUrl);
    $formAcquiredLevel->addSelect('acquired_level', get_lang('Level acquired'), $acquiredLevel);
    $formAcquiredLevel->addHidden('user', $skillRelUser->getUser()->getId());
    $formAcquiredLevel->addHidden('issue', $skillRelUser->getId());
    $formAcquiredLevel->addButtonSend(get_lang('Save'));

    if ($allowComment && $formAcquiredLevel->validate()) {
        $values = $formAcquiredLevel->exportValues();

        $level = $skillLevelRepo->find($values['acquired_level']);
        $skillRelUser->setAcquiredLevel($level);

        $em->persist($skillRelUser);
        $em->flush();

        api_location($currentUrl);
    }

    $form = new FormValidator('comment'.$skillRelUser->getId(), 'post', $currentUrl);
    $form->addTextarea('comment', get_lang('New comment'), ['rows' => 4]);
    $form->applyFilter('comment', 'trim');
    $form->addRule('comment', get_lang('Required field'), 'required');
    $form->addSelect(
        'value',
        [
            get_lang('Value'),
            get_lang(
                'On a grade of 1 to 10, how well did you observe that this person could put this skill in practice?'
            ),
        ],
        ['-', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
    );
    $form->addHidden('user', $skillRelUser->getUser()->getId());
    $form->addHidden('issue', $skillRelUser->getId());
    $form->addButtonSend(get_lang('Send message'));

    if ($allowComment && $form->validate()) {
        $values = $form->exportValues();

        $skillUserComment = new SkillRelUserComment();
        $skillUserComment
            ->setFeedbackDateTime(new DateTime())
            ->setFeedbackGiver($currentUser)
            ->setFeedbackText($values['comment'])
            ->setFeedbackValue($values['value'] ?: null)
            ->setSkillRelUser($skillRelUser)
        ;

        $em->persist($skillUserComment);
        $em->flush();

        api_location($currentUrl);
    }

    $personalBadge = '';
    if ($allowDownloadExport) {
        if (!$backpackJsAdded) {
            SkillModel::setBackPackJs($htmlHeadXtra);
            $backpackJsAdded = true;
        }

        // Each issue has its own direct download URL
        $personalBadge = $currentUrl.'&download_issue='.$skillRelUser->getId();
    }

    $allUserBadges[$index]['issue_info'] = $skillRelUserInfo;
    $allUserBadges[$index]['allow_comment'] = $allowComment;
    $allUserBadges[$index]['allow_download_export'] = $allowDownloadExport;
    $allUserBadges[$index]['comment_form'] = $form->returnForm();
    $allUserBadges[$index]['acquired_level_form'] = $formAcquiredLevel->returnForm();
    $allUserBadges[$index]['personal_badge'] = $personalBadge;
}

$template = new Template(get_lang('Issued badge information'));
$template->assign('user_badges', $allUserBadges);
$template->assign('show_level', ('false' === api_get_setting('skill.hide_skill_levels')));
$template->assign('origin_url', $originUrl);

$content = $template->fetch($template->get_template('skill/issued_all.html.twig'));
$template->assign('header', get_lang('Issued badge information'));
$template->assign('content', $content);
$template->display_one_col_template();
