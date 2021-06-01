<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Entity\SkillRelUserComment;
use SkillRelUser as SkillRelUserManager;

/**
 * Show information about all issued badges with same skill by user.
 *
 * @author José Loguercio Silva <jose.loguercio@beeznest.com>
 */
require_once __DIR__.'/../inc/global.inc.php';

$userId = isset($_GET['user']) ? (int) $_GET['user'] : 0;
$skillId = isset($_GET['skill']) ? (int) $_GET['skill'] : 0;

if (!$userId || !$skillId) {
    api_not_allowed(true);
}

SkillModel::isAllowed($userId);

$em = Database::getManager();
$user = api_get_user_entity($userId);
$skill = $em->find(\Chamilo\CoreBundle\Entity\Skill::class, $skillId);
$currentUserId = api_get_user_id();

if (!$user || !$skill) {
    Display::addFlash(
        Display::return_message(get_lang('No results found'), 'error')
    );

    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$skillRepo = $em->getRepository(\Chamilo\CoreBundle\Entity\Skill::class);
$skillUserRepo = $em->getRepository(SkillRelUser::class);
$skillLevelRepo = $em->getRepository(\Chamilo\CoreBundle\Entity\Level::class);

$userSkills = $skillUserRepo->findBy([
    'user' => $user,
    'skill' => $skill,
]);

$userInfo = [
    'id' => $user->getId(),
    'complete_name' => UserManager::formatUserFullName($user),
];

$skillInfo = [
    'id' => $skill->getId(),
    'name' => $skill->getName(),
    'short_code' => $skill->getShortCode(),
    'description' => $skill->getDescription(),
    'criteria' => $skill->getCriteria(),
    'badge_image' => SkillModel::getWebIconPath($skill),
    'courses' => [],
];

$allUserBadges = [];
/** @var SkillRelUser $skillIssue */
foreach ($userSkills as $index => $skillIssue) {
    $currentUser = api_get_user_entity($currentUserId);
    $allowDownloadExport = $currentUser ? $currentUser->getId() === $user->getId() : false;
    $allowComment = $currentUser ? SkillModel::userCanAddFeedbackToUser($currentUser, $user) : false;
    $skillIssueDate = api_get_local_time($skillIssue->getAcquiredSkillAt());
    $currentSkillLevel = get_lang('No level acquired yet');
    if ($skillIssue->getAcquiredLevel()) {
        $currentSkillLevel = $skillLevelRepo->find(['id' => $skillIssue->getAcquiredLevel()])->getName();
    }
    $argumentationAuthor = api_get_user_info($skillIssue->getArgumentationAuthorId());

    $skillIssueInfo = [
        'id' => $skillIssue->getId(),
        'datetime' => api_format_date($skillIssueDate, DATE_TIME_FORMAT_SHORT),
        'acquired_level' => $currentSkillLevel,
        'argumentation_author_id' => $skillIssue->getArgumentationAuthorId(),
        'argumentation_author_name' => api_get_person_name(
            $argumentationAuthor['firstname'],
            $argumentationAuthor['lastname']
        ),
        'argumentation' => $skillIssue->getArgumentation(),
        'source_name' => $skillIssue->getSourceName(),
        'user_id' => $skillIssue->getUser()->getId(),
        'user_complete_name' => UserManager::formatUserFullName($skillIssue->getUser()),
        'skill_id' => $skillIssue->getSkill()->getId(),
        'skill_badge_image' => SkillModel::getWebIconPath($skillIssue->getSkill()),
        'skill_name' => $skillIssue->getSkill()->getName(),
        'skill_short_code' => $skillIssue->getSkill()->getShortCode(),
        'skill_description' => $skillIssue->getSkill()->getDescription(),
        'skill_criteria' => $skillIssue->getSkill()->getCriteria(),
        'badge_assertion' => SkillRelUserModel::getAssertionUrl($skillIssue),
        'comments' => [],
        'feedback_average' => $skillIssue->getAverage(),
    ];

    $skillIssueComments = $skillIssue->getComments(true);

    $userId = $skillIssueInfo['user_id'];
    $skillId = $skillIssueInfo['skill_id'];

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
        $skillRelSkill = new SkillRelSkill();
        $parents = $skillRelSkill->getSkillParents($skillId);

        krsort($parents);

        foreach ($parents as $parent) {
            $skillParentId = $parent['skill_id'];
            $profile = $skillRepo->find($skillParentId)->getProfile();

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
            $profileLevels[$level->getPosition()][$level->getId()] = $level->getName();
        }

        ksort($profileLevels); // Sort the array by Position.

        foreach ($profileLevels as $profileLevel) {
            $profileId = key($profileLevel);
            $acquiredLevel[$profileId] = $profileLevel[$profileId];
        }
    }

    $formAcquiredLevel = new FormValidator(
        'acquired_level'.$skillIssue->getId(),
        'post',
        SkillRelUserManager::getIssueUrlAll($skillIssue)
    );
    $formAcquiredLevel->addSelect('acquired_level', get_lang('Level acquired'), $acquiredLevel);
    $formAcquiredLevel->addHidden('user', $skillIssue->getUser()->getId());
    $formAcquiredLevel->addHidden('issue', $skillIssue->getId());
    $formAcquiredLevel->addButtonSend(get_lang('Save'));

    if ($formAcquiredLevel->validate() && $allowComment) {
        $values = $formAcquiredLevel->exportValues();

        $level = $skillLevelRepo->find($values['acquired_level']);
        $skillIssue->setAcquiredLevel($level);

        $em->persist($skillIssue);
        $em->flush();

        header('Location: '.SkillRelUserManager::getIssueUrlAll($skillIssue));
        exit;
    }

    $form = new FormValidator(
        'comment'.$skillIssue->getId(),
        'post',
        SkillRelUserManager::getIssueUrlAll($skillIssue)
    );
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
    $form->addHidden('user', $skillIssue->getUser()->getId());
    $form->addHidden('issue', $skillIssue->getId());
    $form->addButtonSend(get_lang('Send message'));

    if ($form->validate() && $allowComment) {
        $values = $form->exportValues();

        $skillUserComment = new SkillRelUserComment();
        $skillUserComment
            ->setFeedbackDateTime(new DateTime())
            ->setFeedbackGiver($currentUser)
            ->setFeedbackText($values['comment'])
            ->setFeedbackValue($values['value'] ? $values['value'] : null)
            ->setSkillRelUser($skillIssue);

        $em->persist($skillUserComment);
        $em->flush();

        header('Location: '.SkillRelUserManager::getIssueUrlAll($skillIssue));
        exit;
    }

    $badgeInfoError = '';
    $personalBadge = '';

    if ($allowDownloadExport) {
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
        $unbakedBadge = api_get_path(SYS_UPLOAD_PATH)."badges/".$skills['icon'];
        if (!is_file($unbakedBadge)) {
            $unbakedBadge = api_get_path(WEB_CODE_PATH).'img/icons/128/badges-default.png';
        }

        $unbakedBadge = file_get_contents($unbakedBadge);
        $badgeInfoError = false;
        $personalBadge = "";
        $png = new PNGImageBaker($unbakedBadge);

        if ($png->checkChunks("tEXt", "openbadges")) {
            $bakedInfo = $png->addChunk("tEXt", "openbadges", $assertionUrl);
            $bakedBadge = UserManager::getUserPathById($userId, "system");
            $bakedBadge = $bakedBadge.'badges';
            if (!file_exists($bakedBadge)) {
                mkdir($bakedBadge, api_get_permissions_for_new_directories(), true);
            }
            $skillRelUserId = $skillIssueInfo['id'];
            if (!file_exists($bakedBadge."/badge_".$skillRelUserId)) {
                file_put_contents($bakedBadge."/badge_".$skillRelUserId.".png", $bakedInfo);
            }

            //Process to validate a baked badge
            $badgeContent = file_get_contents($bakedBadge."/badge_".$skillRelUserId.".png");
            $verifyBakedBadge = $png->extractBadgeInfo($badgeContent);
            if (!is_array($verifyBakedBadge)) {
                $badgeInfoError = true;
            }

            if (!$badgeInfoError) {
                $personalBadge = UserManager::getUserPathById($userId, "web");
                $personalBadge = $personalBadge."badges/badge_".$skillRelUserId.".png";
            }
        }
    }

    $allUserBadges[$index]['issue_info'] = $skillIssueInfo;
    $allUserBadges[$index]['allow_comment'] = $allowComment;
    $allUserBadges[$index]['allow_download_export'] = $allowDownloadExport;
    $allUserBadges[$index]['comment_form'] = $form->returnForm();
    $allUserBadges[$index]['acquired_level_form'] = $formAcquiredLevel->returnForm();
    $allUserBadges[$index]['badge_error'] = $badgeInfoError;
    $allUserBadges[$index]['personal_badge'] = $personalBadge;
}

$template = new Template(get_lang('Issued badge information'));
$template->assign('user_badges', $allUserBadges);
$template->assign('show_level', false == api_get_configuration_value('hide_skill_levels'));

$content = $template->fetch(
    $template->get_template('skill/issued_all.tpl')
);

$template->assign('header', get_lang('Issued badge information'));
$template->assign('content', $content);
$template->display_one_col_template();
