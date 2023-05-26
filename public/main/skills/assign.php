<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Skill;

/**
 * Page for assign skills to a user.
 *
 * @author: Jose Loguercio <jose.loguercio@beeznest.com>
 */
require_once __DIR__.'/../inc/global.inc.php';

$userId = isset($_REQUEST['user']) ? (int) $_REQUEST['user'] : 0;

if (empty($userId)) {
    api_not_allowed(true);
}

SkillModel::isAllowed($userId);

$user = api_get_user_entity($userId);

if (!$user) {
    api_not_allowed(true);
}

$entityManager = Database::getManager();
$skillManager = new SkillModel();
$skillRepo = $entityManager->getRepository(Skill::class);
$skillRelSkill = $entityManager->getRepository(\Chamilo\CoreBundle\Entity\SkillRelSkill::class);
$skillLevelRepo = $entityManager->getRepository(\Chamilo\CoreBundle\Entity\Level::class);
$skillUserRepo = $entityManager->getRepository(\Chamilo\CoreBundle\Entity\SkillRelUser::class);

$skillLevels = api_get_setting('skill.skill_levels_names', true);

$skillsOptions = ['' => get_lang('Select')];
$acquiredLevel = ['' => get_lang('none')];
$formDefaultValues = [];

if (empty($skillLevels)) {
    $skills = $skillRepo->findBy([
        'status' => Skill::STATUS_ENABLED,
    ]);
    /** @var Skill $skill */
    foreach ($skills as $skill) {
        $skillsOptions[$skill->getId()] = $skill->getName();
    }
} else {
    // Get only root elements
    $skills = $skillManager->getChildren(1);
    foreach ($skills as $skill) {
        $skillsOptions[$skill['data']['id']] = $skill['data']['name'];
    }
}
$skillIdFromGet = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$currentValue = isset($_REQUEST['current_value']) ? (int) $_REQUEST['current_value'] : 0;
$currentLevel = isset($_REQUEST['current']) ? (int) str_replace('sub_skill_id_', '', $_REQUEST['current']) : 0;

$subSkillList = isset($_REQUEST['sub_skill_list']) ? explode(',', $_REQUEST['sub_skill_list']) : [];
$subSkillList = array_unique($subSkillList);

if (!empty($subSkillList)) {
    // Compare asked skill with current level
    $correctLevel = false;
    if (isset($subSkillList[$currentLevel]) && $subSkillList[$currentLevel] == $currentValue) {
        $correctLevel = true;
    }

    // Level is wrong probably user change the level. Fix the subSkillList array
    if (!$correctLevel) {
        $newSubSkillList = [];
        $counter = 0;
        foreach ($subSkillList as $subSkillId) {
            if ($counter == $currentLevel) {
                $subSkillId = $currentValue;
            }
            $newSubSkillList[$counter] = $subSkillId;
            if ($counter == $currentLevel) {
                break;
            }
            $counter++;
        }
        $subSkillList = $newSubSkillList;
    }
}

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
    $skillFromLastSkill = $skillRepo->find(end($subSkillList));
    if ($skillFromLastSkill) {
        $profile = $skillFromLastSkill->getProfile();
    }
}

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
            $profile = isset($profile[0]) ? $profile[0] : false;
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

$formDefaultValues = ['skill' => $skillId];
$newSubSkillList = [];
$disableList = [];

$currentUrl = api_get_self().'?user='.$userId.'&current='.$currentLevel;

$form = new FormValidator('assign_skill', 'POST', $currentUrl);
$form->addHeader(get_lang('Assign skill'));
$form->addText('user_name', get_lang('Username'), false);

$levelName = get_lang('Skill');
if (!empty($skillLevels)) {
    if (isset($skillLevels['levels'][1])) {
        $levelName = get_lang($skillLevels['levels'][1]);
    }
}

$form->addSelect('skill', $levelName, $skillsOptions, ['id' => 'skill']);

if (!empty($skillIdFromGet)) {
    if (empty($subSkillList)) {
        $subSkillList[] = $skillIdFromGet;
    }
    $oldSkill = $skillRepo->find($skillIdFromGet);
    $counter = 0;
    foreach ($subSkillList as $subSkillId) {
        $children = $skillManager->getChildren($subSkillId);

        if (isset($subSkillList[$counter - 1])) {
            $oldSkill = $skillRepo->find($subSkillList[$counter]);
        }
        $skillsOptions = [];
        if ($oldSkill) {
            $skillsOptions = [$oldSkill->getId() => ' -- '.$oldSkill->getName()];
        }

        if ($counter < count($subSkillList) - 1) {
            $disableList[] = 'sub_skill_id_'.($counter + 1);
        }

        foreach ($children as $child) {
            $skillsOptions[$child['id']] = $child['data']['name'];
        }

        $levelName = get_lang('Sub-skill');
        if (!empty($skillLevels)) {
            if (isset($skillLevels['levels'][$counter + 2])) {
                $levelName = get_lang($skillLevels['levels'][$counter + 2]);
            }
        }

        $form->addSelect(
            'sub_skill_id_'.($counter + 1),
            $levelName,
            $skillsOptions,
            [
                'id' => 'sub_skill_id_'.($counter + 1),
                'class' => 'sub_skill',
            ]
        );

        if (isset($subSkillList[$counter + 1])) {
            $nextSkill = $skillRepo->find($subSkillList[$counter + 1]);
            if ($nextSkill) {
                $formDefaultValues['sub_skill_id_'.($counter + 1)] = $nextSkill->getId();
            }
        }
        $newSubSkillList[] = $subSkillId;
        $counter++;
    }
    $subSkillList = $newSubSkillList;
}

$subSkillListToString = implode(',', $subSkillList);

$currentUrl = api_get_self().'?user='.$userId.'&current='.$currentLevel.'&sub_skill_list='.$subSkillListToString;

$form->addHidden('sub_skill_list', $subSkillListToString);
$form->addHidden('user', $user->getId());
$form->addHidden('id', $skillId);
$form->addRule('skill', get_lang('Required field'), 'required');

$showLevels = ('false' === api_get_setting('skill.hide_skill_levels'));

if ($showLevels) {
    $form->addSelect('acquired_level', get_lang('Level acquired'), $acquiredLevel);
    //$form->addRule('acquired_level', get_lang('Required field'), 'required');
}

$form->addTextarea('argumentation', get_lang('Argumentation'), ['rows' => 6]);
$form->addRule('argumentation', get_lang('Required field'), 'required');
$form->addRule(
    'argumentation',
    sprintf(get_lang('This text should be at least %s characters long'), 10),
    'mintext',
    10
);
$form->applyFilter('argumentation', 'trim');
$form->addButtonSave(get_lang('Save'));
$form->setDefaults($formDefaultValues);

if ($form->validate()) {
    $values = $form->exportValues();
    $skillToProcess = $values['id'];
    if (!empty($subSkillList)) {
        $counter = 1;
        foreach ($subSkillList as $subSkill) {
            if (isset($values["sub_skill_id_$counter"])) {
                $skillToProcess = $values["sub_skill_id_$counter"];
            }
            $counter++;
        }
    }
    $skill = $skillRepo->find($skillToProcess);

    if (!$skill) {
        Display::addFlash(
            Display::return_message(get_lang('Skill not found'), 'error')
        );

        header('Location: '.api_get_self().'?'.$currentUrl);
        exit;
    }

    if ($user->hasSkill($skill)) {
        Display::addFlash(
            Display::return_message(
                sprintf(
                    get_lang('The user %s has already achieved the skill %s'),
                    UserManager::formatUserFullName($user),
                    $skill->getName()
                ),
                'warning'
            )
        );

        header('Location: '.$currentUrl);
        exit;
    }

    $skillUser = $skillManager->addSkillToUserBadge(
        $user,
        $skill,
        isset($values['acquired_level']) ? (int) $values['acquired_level'] : 0,
        $values['argumentation'],
        api_get_user_id()
    );

    // Send email depending of children_auto_threshold
    $skillRelSkill = new SkillRelSkillModel();
    $skillModel = new SkillModel();
    $parents = $skillModel->getDirectParents($skillToProcess);

    $extraFieldValue = new ExtraFieldValue('skill');
    foreach ($parents as $parentInfo) {
        $parentId = $parentInfo['skill_id'];
        $parentData = $skillModel->get($parentId);

        $data = $extraFieldValue->get_values_by_handler_and_field_variable($parentId, 'children_auto_threshold');
        if (!empty($data) && !empty($data['value'])) {
            // Search X children
            $requiredSkills = $data['value'];
            $children = $skillRelSkill->getChildren($parentId);
            $counter = 0;
            foreach ($children as $child) {
                if ($skillModel->userHasSkill($userId, $child['id'])) {
                    $counter++;
                }
            }

            if ($counter >= $requiredSkills) {
                $bossList = UserManager::getStudentBossList($userId);
                if (!empty($bossList)) {
                    Display::addFlash(Display::return_message(get_lang('Message Sent')));
                    $url = api_get_path(WEB_CODE_PATH).'skills/assign.php?user='.$userId.'&id='.$parentId;
                    $link = Display::url($url, $url);
                    $subject = get_lang('A student has obtained the number of sub-skills needed to validate the mother skill.');
                    $message = sprintf(
                        get_lang('Learner %s has enough sub-skill to get skill %s. To assign this skill it is possible to go here : %s'),
                        UserManager::formatUserFullName($user),
                        $parentData['name'],
                        $link
                    );
                    foreach ($bossList as $boss) {
                        MessageManager::send_message_simple(
                            $boss['boss_id'],
                            $subject,
                            $message
                        );
                    }
                }
                break;
            }
        }
    }

    Display::addFlash(
        Display::return_message(
            sprintf(
                get_lang('The skill %s has been assigned to user %s'),
                $skill->getName(),
                UserManager::formatUserFullName($user)
            ),
            'success'
        )
    );

    Display::addFlash(
        Display::return_message(
            sprintf(
                get_lang('To assign a new skill to this user, click <a href="%s">here</a>'),
                api_get_self().'?'.http_build_query(['user' => $user->getId()])
            ),
            'info',
            false
        )
    );

    header('Location: '.api_get_path(WEB_PATH)."badge/{$skillUser->getId()}");
    exit;
}

$form->setDefaults(['user_name' => UserManager::formatUserFullName($user, true)]);
$form->freeze(['user_name']);

if (api_is_drh()) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'my_space/index.php',
        "name" => get_lang('Reporting'),
    ];
    if (COURSEMANAGER == $user->getStatus()) {
        $interbreadcrumb[] = [
            "url" => api_get_path(WEB_CODE_PATH).'my_space/teachers.php',
            'name' => get_lang('Trainers'),
        ];
    } else {
        $interbreadcrumb[] = [
            "url" => api_get_path(WEB_CODE_PATH).'my_space/student.php',
            'name' => get_lang('My learners'),
        ];
    }
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'my_space/myStudents.php?student='.$userId,
        'name' => UserManager::formatUserFullName($user),
    ];
} else {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
        'name' => get_lang('Administration'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'admin/user_list.php',
        'name' => get_lang('User list'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.$userId,
        'name' => UserManager::formatUserFullName($user),
    ];
}

$url = api_get_path(WEB_CODE_PATH).'skills/assign.php?user='.$userId;

$disableSelect = '';
if ($disableList) {
    foreach ($disableList as $name) {
        //$disableSelect .= "$('#".$name."').prop('disabled', true);";
        //$disableSelect .= "$('#".$name."').selectpicker('refresh');";
    }
}

$htmlHeadXtra[] = '<script>
$(function() {
    $("#skill").on("change", function() {
        $(location).attr("href", "'.$url.'&id="+$(this).val());
    });
    $(".sub_skill").on("change", function() {
        $(location).attr("href", "'.$url.'&id='.$skillIdFromGet.'&current_value="+$(this).val()+"&current="+$(this).attr("id")+"&sub_skill_list='.$subSkillListToString.',"+$(this).val());
    });
    '.$disableSelect.'
});
</script>';

$template = new Template(get_lang('Add skill'));
$template->assign('content', $form->returnForm());
$template->display_one_col_template();
