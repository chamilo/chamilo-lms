<?php
/* For licensing terms, see /license.txt */

use \Skill as SkillManager;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\UserBundle\Entity\User;

/**
 * Page for assign skills to a user
 *
 * @autor: Jose Loguercio <jose.loguercio@beeznest.com>
 * @package chamilo.badge
 */

require_once __DIR__.'/../inc/global.inc.php';

$userId = isset($_REQUEST['user']) ? (int) $_REQUEST['user'] : 0;

if (empty($userId)) {
    api_not_allowed(true);
}

SkillManager::isAllow($userId);

$user = api_get_user_entity($userId);

if (!$user) {
    api_not_allowed(true);
}

$entityManager = Database::getManager();
$skillManager = new SkillManager();
$skillRepo = $entityManager->getRepository('ChamiloCoreBundle:Skill');
$skillRelSkill = $entityManager->getRepository('ChamiloCoreBundle:SkillRelSkill');
$skillLevelRepo = $entityManager->getRepository('ChamiloSkillBundle:Level');
$skillUserRepo = $entityManager->getRepository('ChamiloCoreBundle:SkillRelUser');

$skills = $skillRepo->findBy([
    'status' => Skill::STATUS_ENABLED
]);

$skillsOptions = [];
$acquiredLevel = [];
$formDefaultValues = [];

foreach ($skills as $skill) {
    $skillsOptions[$skill->getId()] = $skill->getName();
}

$skillIdFromGet = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$currentLevel = isset($_REQUEST['current']) ? (int) str_replace('assign_skill_sub_skill_id_', '', $_REQUEST['current']) : 0;
$subSkillList = isset($_REQUEST['sub_skill_list']) ? explode(',', $_REQUEST['sub_skill_list']) : [];
$subSkillList = array_unique($subSkillList);

if (!empty($currentLevel)) {
    $level = $currentLevel + 1;
    if ($level < count($subSkillList)) {
        $remove = count($subSkillList) - $currentLevel;
        $newSubSkillList = array_slice($subSkillList, 0, count($subSkillList) - $level);
        $subSkillList = $newSubSkillList;
    }
}


$skillId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : key($skillsOptions);
$skill = $skillRepo->find($skillId);
$profile = false;
if ($skill) {
    $profile = $skill->getProfile();
}

if (!empty($subSkillList)) {
   // $skillId = end($subSkillList);
    $skillFromLastSkill = $skillRepo->find(end($subSkillList));
    if ($skillFromLastSkill) {
        $profile = $skillFromLastSkill->getProfile();
    }
}

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

        if (!$profile && $parent['parent_id'] == 0) {
            $profile = $skillLevelRepo->findAll();
            $profile = isset($profile[0]) ? $profile[0] : false;
        }
    }
}

if ($profile) {
    $profileId = $profile->getId();
    $levels = $skillLevelRepo->findBy([
        'profile' => $profileId
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

$formDefaultValues = ['skill' => $skillId];

$form = new FormValidator('assign_skill', 'POST', api_get_self().'?user='.$userId.'&');
$form->addHeader(get_lang('AssignSkill'));
$form->addText('user_name', get_lang('UserName'), false);
$form->addSelect('skill', get_lang('Skill'), $skillsOptions, ['id' => 'skill']);

$newSubSkillList = [];
$disableList = [];
if (!empty($skillIdFromGet)) {
    if (empty($subSkillList)) {
        $subSkillList[] = $skillIdFromGet;
    }
    $oldSkill = $skillRepo->find($skillIdFromGet);
    $counter = 0;
    foreach ($subSkillList as $subSkillId) {
        $children = $skillManager->getChildren($subSkillId);

        if (isset($subSkillList[$counter-1])) {
            $oldSkill = $skillRepo->find($subSkillList[$counter]);
        }
        $skillsOptions = [];
        if ($oldSkill) {
            $skillsOptions = [$oldSkill->getId() => ' -- '.$oldSkill->getName()];
        }

        if ($counter < count($subSkillList) - 1) {
            $disableList[] =  'sub_skill_id_'.($counter+1);
        }

        foreach ($children as $child) {
            $skillsOptions[$child['id']] = $child['data']['name'];
        }

        $form->addSelect(
            'sub_skill_id_'.($counter+1),
            get_lang('SubSkill'),
            $skillsOptions,
            [
                'id' => 'sub_skill_id_'.($counter+1),
                'class' => 'sub_skill'
            ]
        );

        if (isset($subSkillList[$counter+1])) {
            $nextSkill = $skillRepo->find($subSkillList[$counter+1]);
            $formDefaultValues['sub_skill_id_'.($counter+1)] = $nextSkill->getId();
        }
        $newSubSkillList[] = $subSkillId;
        $counter++;
    }
    $subSkillList = $newSubSkillList;
}

$subSkillListToString = implode(',', $subSkillList);
$form->addHidden('sub_skill_list', $subSkillListToString);
$form->addHidden('user', $user->getId());
$form->addHidden('id', $skillId);
$form->addRule('skill', get_lang('ThisFieldIsRequired'), 'required');
$form->addSelect('acquired_level', get_lang('AcquiredLevel'), $acquiredLevel);
//$form->addRule('acquired_level', get_lang('ThisFieldIsRequired'), 'required');
$form->addTextarea('argumentation', get_lang('Argumentation'), ['rows' => 6]);
$form->addRule('argumentation', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule(
    'argumentation',
    sprintf(get_lang('ThisTextShouldBeAtLeastXCharsLong'), 10),
    'mintext',
    10
);
$form->applyFilter('argumentation', 'trim');
$form->addButtonSave(get_lang('Save'));
$form->setDefaults($formDefaultValues);

if ($form->validate()) {
    $values = $form->exportValues();
    $skill = $skillRepo->find($values['id']);

    if (!$skill) {
        Display::addFlash(
            Display::return_message(get_lang('SkillNotFound'), 'error')
        );

        header('Location: '.api_get_self().'?'.http_build_query(['user' => $user->getId()]));
        exit;
    }

    if ($user->hasSkill($skill)) {
        Display::addFlash(
            Display::return_message(
                sprintf(
                    get_lang('TheUserXHasAlreadyAchievedTheSkillY'),
                    $user->getCompleteName(),
                    $skill->getName()
                ),
                'warning'
            )
        );

        header('Location: '.api_get_self().'?'.http_build_query(['user' => $user->getId()]));
        exit;
    }

    $skillUser = new SkillRelUser();
    $skillUser->setUser($user);
    $skillUser->setSkill($skill);
    $level = $skillLevelRepo->find(intval($values['acquired_level']));
    $skillUser->setAcquiredLevel($level);
    $skillUser->setArgumentation($values['argumentation']);
    $skillUser->setArgumentationAuthorId(api_get_user_id());
    $skillUser->setAcquiredSkillAt(new DateTime());
    $skillUser->setAssignedBy(0);

    $entityManager->persist($skillUser);
    $entityManager->flush();

    Display::addFlash(
        Display::return_message(
            sprintf(
                get_lang('SkillXAssignedToUserY'),
                $skill->getName(),
                $user->getCompleteName()
            ),
            'success'
        )
    );

    header('Location: '.api_get_path(WEB_PATH)."badge/{$skillUser->getId()}");
    exit;
}

$form->setDefaults(['user_name' => $user->getCompleteNameWithUsername()]);
$form->freeze(['user_name']);

if (api_is_drh()) {
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'mySpace/index.php',
        "name" => get_lang('MySpace')
    );
    if ($user->getStatus() == COURSEMANAGER) {
        $interbreadcrumb[] = array(
            "url" => api_get_path(WEB_CODE_PATH).'mySpace/teachers.php',
            'name' => get_lang('Teachers')
        );
    } else {
        $interbreadcrumb[] = array(
            "url" => api_get_path(WEB_CODE_PATH).'mySpace/student.php',
            'name' => get_lang('MyStudents')
        );
    }
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.$userId,
        'name' => $user->getCompleteName()
    );
} else {
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
        'name' => get_lang('PlatformAdmin')
    );
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'admin/user_list.php',
        'name' => get_lang('UserList')
    );
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.$userId,
        'name' => $user->getCompleteName()
    );
}

$url = api_get_path(WEB_CODE_PATH).'badge/assign.php?user='.$userId.'&id=';

$disableSelect = '';
if ($disableList) {
    foreach ($disableList as $name) {
        $disableSelect .= "$('#".$name."').prop('disabled', true);";
        $disableSelect .= "$('#".$name."').selectpicker('refresh');";
    }
}

$htmlHeadXtra[] = '<script>
$(document).ready(function() {
    $("#skill").on("change", function() {
        $(location).attr("href", "'. $url.'"+$(this).val());
    });
    $(".sub_skill").on("change", function() {
        $(location).attr("href", "'.$url.'&id='.$skillIdFromGet.'&current="+$(this).attr("id")+"&sub_skill_list='.$subSkillListToString.',"+$(this).val());
    });
    '.$disableSelect.'
});
</script>';

$template = new Template(get_lang('AddSkill'));
$template->assign('content', $form->returnForm());
$template->display_one_col_template();
