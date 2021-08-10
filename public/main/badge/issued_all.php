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

$currentUrl = api_get_self().'?user='.$userId.'&skill='.$skillId;

SkillModel::isAllowed($userId);

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

$allUserBadges = [];
/** @var SkillRelUser $skillRelUser */
foreach ($userSkills as $index => $skillRelUser) {
    $skillRelUserDate = api_get_local_time($skillRelUser->getAcquiredSkillAt());
    $currentSkillLevel = get_lang('No level acquired yet');
    if ($skillRelUser->getAcquiredLevel()) {
        $currentSkillLevel = $skillLevelRepo->find($skillRelUser->getAcquiredLevel()->getId())->getName();
    }
    $argumentationAuthor = api_get_user_info($skillRelUser->getArgumentationAuthorId());

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
        'skill_badge_image' => SkillModel::getWebIconPath($skillRelUser->getSkill()),
        'skill_name' => $skillRelUser->getSkill()->getName(),
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
        SkillModel::setBackPackJs($htmlHeadXtra);
        $personalBadge = $currentUrl.'&export=1';
        if ($export) {
           SkillModel::exportBadge($skill, $skillRelUser, $currentUrl);
        }
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
$template->assign('show_level', false === api_get_configuration_value('hide_skill_levels'));

$content = $template->fetch($template->get_template('skill/issued_all.html.twig'));
$template->assign('header', get_lang('Issued badge information'));
$template->assign('content', $content);
$template->display_one_col_template();
