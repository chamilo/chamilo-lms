<?php
/* For licensing terms, see /license.txt */
/**
 * Show information about all issued badges with same skill by user
 *
 * @author José Loguercio Silva <jose.loguercio@beeznest.com>
 * @package chamilo.badge
 */
require_once __DIR__.'/../inc/global.inc.php';

$userId = isset($_GET['user']) ? intval($_GET['user']) : 0;
$skillId = isset($_GET['skill']) ? intval($_GET['skill']) : 0;

if (!$userId || !$skillId) {
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$entityManager = Database::getManager();
$user = $entityManager->find('ChamiloUserBundle:User', $userId);
$skill = $entityManager->find('ChamiloCoreBundle:Skill', $skillId);
$skillRepo = $entityManager->getRepository('ChamiloCoreBundle:Skill');
$skillUserRepo = $entityManager->getRepository('ChamiloCoreBundle:SkillRelUser');
$skillLevelRepo = $entityManager->getRepository('ChamiloSkillBundle:Level');

$currentUserId = api_get_user_id();

if (!$user || !$skill) {
    Display::addFlash(
        Display::return_message(get_lang('NoResults'), 'error')
    );

    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$userSkills = $skillUserRepo->findBy([
    'user' => $user,
    'skill' => $skill
]);

$userInfo = [
    'id' => $user->getId(),
    'complete_name' => $user->getCompleteName()
];

$skillInfo = [
    'id' => $skill->getId(),
    'name' => $skill->getName(),
    'short_code' => $skill->getShortCode(),
    'description' => $skill->getDescription(),
    'criteria' => $skill->getCriteria(),
    'badge_image' => $skill->getWebIconPath(),
    'courses' => []
];

$allUserBadges = [];

foreach ($userSkills as $index => $skillIssue) {
    $currentUser = $entityManager->find('ChamiloUserBundle:User', $currentUserId);
    $allowDownloadExport = $currentUser ? $currentUser->getId() === $user->getId() : false;
    $allowComment = $currentUser ? Skill::userCanAddFeedbackToUser($currentUser, $user) : false;
    $skillIssueDate = api_get_local_time($skillIssue->getAcquiredSkillAt());
    $currentSkillLevel = get_lang('NoLevelAcquiredYet');
    if ($skillIssue->getAcquiredLevel()) {
        $currentSkillLevel = $skillLevelRepo->find(['id' => $skillIssue->getAcquiredLevel()])->getName();
    }
    $argumentationAuthor = api_get_user_info($skillIssue->getArgumentationAuthorId());

    $skillIssueInfo = [
        'id' => $skillIssue->getId(),
        'datetime' => api_format_date($skillIssueDate, DATE_TIME_FORMAT_SHORT),
        'acquired_level' => $currentSkillLevel,
        'argumentation_author_id' => $skillIssue->getArgumentationAuthorId(),
        'argumentation_author_name' => api_get_person_name($argumentationAuthor['firstname'], $argumentationAuthor['lastname']),
        'argumentation' => $skillIssue->getArgumentation(),
        'source_name' => $skillIssue->getSourceName(),
        'user_id' => $skillIssue->getUser()->getId(),
        'user_complete_name' => $skillIssue->getUser()->getCompleteName(),
        'skill_id' => $skillIssue->getSkill()->getId(),
        'skill_badge_image' => $skillIssue->getSkill()->getWebIconPath(),
        'skill_name' => $skillIssue->getSkill()->getName(),
        'skill_short_code' => $skillIssue->getSkill()->getShortCode(),
        'skill_description' => $skillIssue->getSkill()->getDescription(),
        'skill_criteria' => $skillIssue->getSkill()->getCriteria(),
        'badge_assertion' => $skillIssue->getAssertionUrl(),
        'comments' => [],
        'feedback_average' => $skillIssue->getAverage()
    ];

    $skillIssueComments = $skillIssue->getComments(true);

    $userId = $skillIssueInfo['user_id'];
    $skillId = $skillIssueInfo['skill_id'];

    foreach ($skillIssueComments as $comment) {
        $commentDate = api_get_local_time($comment->getFeedbackDateTime());

        $skillIssueInfo['comments'][] = [
            'text' => $comment->getFeedbackText(),
            'value' => $comment->getFeedbackValue(),
            'giver_complete_name' => $comment->getFeedbackGiver()->getCompleteName(),
            'datetime' => api_format_date($commentDate, DATE_TIME_FORMAT_SHORT)
        ];
    }

    $acquiredLevel = [];

    $profile = $skillRepo->find($skillId)->getProfile();

    if (!$profile) {

        $skillRelSkill = new SkillRelSkill();
        $parents = $skillRelSkill->get_skill_parents($skillId);

        krsort($parents);

        foreach ($parents as $parent) {
            $skillParentId = $parent['skill_id'];
            $profile = $skillRepo->find($skillParentId)->getProfile();

            if ($profile) {
                break;
            }

            if (!$profile && $parent['parent_id'] == 0) {
                $profile = $skillLevelRepo->findAll();
                $profile = !empty($profile) ? $profile[0] : [];
            }
        }
    }

    if ($profile) {
        $profileId = $profile->getId();
        $levels = $skillLevelRepo->findBy([
            'profile' => $profileId
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
        $skillIssue->getIssueUrlAll()
    );
    $formAcquiredLevel->addSelect('acquired_level', get_lang('AcquiredLevel'), $acquiredLevel);
    $formAcquiredLevel->addHidden('user', $skillIssue->getUser()->getId());
    $formAcquiredLevel->addHidden('issue', $skillIssue->getId());
    $formAcquiredLevel->addButtonSend(get_lang('Save'));

    if ($formAcquiredLevel->validate() && $allowComment) {
        $values = $formAcquiredLevel->exportValues();

        $level = $skillLevelRepo->find($values['acquired_level']);
        $skillIssue->setAcquiredLevel($level);

        $entityManager->persist($skillIssue);
        $entityManager->flush();

        header("Location: ".$skillIssue->getIssueUrlAll());
        exit;
    }

    $form = new FormValidator('comment'.$skillIssue->getId(), 'post', $skillIssue->getIssueUrlAll());
    $form->addTextarea('comment', get_lang('NewComment'), ['rows' => 4]);
    $form->applyFilter('comment', 'trim');
    $form->addRule('comment', get_lang('ThisFieldIsRequired'), 'required');
    $form->addSelect(
        'value',
        [get_lang('Value'), get_lang('RateTheSkillInPractice')],
        ['-', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
    );
    $form->addHidden('user', $skillIssue->getUser()->getId());
    $form->addHidden('issue', $skillIssue->getId());
    $form->addButtonSend(get_lang('Send'));

    if ($form->validate() && $allowComment) {
        $values = $form->exportValues();

        $skillUserComment = new Chamilo\CoreBundle\Entity\SkillRelUserComment();
        $skillUserComment
            ->setFeedbackDateTime(new DateTime)
            ->setFeedbackGiver($currentUser)
            ->setFeedbackText($values['comment'])
            ->setFeedbackValue($values['value'] ? $values['value'] : null)
            ->setSkillRelUser($skillIssue);

        $entityManager->persist($skillUserComment);
        $entityManager->flush();

        header("Location: ".$skillIssue->getIssueUrlAll());
        exit;
    }

    $badgeInfoError = '';
    $personalBadge = '';

    if ($allowDownloadExport) {
        $backpack = 'https://backpack.openbadges.org/';

        $configBackpack = api_get_setting('openbadges_backpack');

        if (strcmp($backpack, $configBackpack) !== 0) {
            $backpack = $configBackpack;
        }

        $htmlHeadXtra[] = '<script src="'.$backpack.'issuer.js"></script>';
        $objSkill = new Skill();
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

$template = new Template(get_lang('IssuedBadgeInformation'));
$template->assign('user_badges', $allUserBadges);


$content = $template->fetch(
    $template->get_template('skill/issued_all.tpl')
);

$template->assign('header', get_lang('IssuedBadgeInformation'));
$template->assign('content', $content);
$template->display_one_col_template();
