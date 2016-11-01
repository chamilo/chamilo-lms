<?php
/* For licensing terms, see /license.txt */

/**
 * Skill list for management
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.admin
 */

$cidReset = true;

require_once '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

if (api_get_setting('allow_skills_tool') != 'true') {
    api_not_allowed();
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$skillId = isset($_GET['id']) ? intval($_GET['id']): 0;

$entityManager = Database::getManager();

switch ($action) {
    case 'enable':
        $skill = $entityManager->find('ChamiloCoreBundle:Skill', $skillId);

        if (is_null($skill)) {
            Display::addFlash(
                Display::return_message(
                    get_lang('SkillNotFound'),
                    'error'
                )
            );
        } else {
            $updatedAt = new DateTime(
                api_get_utc_datetime(),
                new DateTimeZone(api_get_timezone())
            );

            $skill->setStatus(1);
            $skill->setUpdatedAt($updatedAt);

            $entityManager->persist($skill);
            $entityManager->flush();

            Display::addFlash(
                Display::return_message(
                    sprintf(get_lang('SkillXEnabled'), $skill->getName()),
                    'success'
                )
            );
        }

        header('Location: ' . api_get_self());
        exit;
        break;
    case 'disable':
        $skill = $entityManager->find('ChamiloCoreBundle:Skill', $skillId);

        if (is_null($skill)) {
            Display::addFlash(
                Display::return_message(
                    get_lang('SkillNotFound'),
                    'error'
                )
            );
        } else {
            $updatedAt = new DateTime(
                api_get_utc_datetime(),
                new DateTimeZone(api_get_timezone())
            );

            $skill->setStatus(0);
            $skill->setUpdatedAt($updatedAt);

            $entityManager->persist($skill);

            $skillObj = new Skill();
            $childrens = $skillObj->get_children($skill->getId());

            foreach ($childrens as $children) {
                $skill = $entityManager->find(
                    'ChamiloCoreBundle:Skill',
                    $children['id']
                );

                if (empty($skill)) {
                    continue;
                }

                $skill->setStatus(0);
                $skill->setUpdatedAt($updatedAt);

                $entityManager->persist($skill);
            }

            $entityManager->flush();

            Display::addFlash(
                Display::return_message(
                    sprintf(get_lang('SkillXDisabled'), $skill->getName()),
                    'success'
                )
            );
        }

        header('Location: ' . api_get_self());
        exit;
        break;
    case 'list':
        //no break
    default:
        $interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));

        $toolbar = Display::toolbarButton(
            get_lang('CreateSkill'),
            api_get_path(WEB_CODE_PATH) . 'admin/skill_create.php',
            'plus',
            'success',
            ['title' => get_lang('CreateSkill')]
        );
        $toolbar .= Display::toolbarButton(
            get_lang('SkillsWheel'),
            api_get_path(WEB_CODE_PATH) . 'admin/skills_wheel.php',
            'bullseye',
            'primary',
            ['title' => get_lang('CreateSkill')]
        );
        $toolbar .= Display::toolbarButton(
            get_lang('BadgesManagement'),
            api_get_path(WEB_CODE_PATH) . 'admin/skill_badge_list.php',
            'shield',
            'warning',
            ['title' => get_lang('BadgesManagement')]
        );
        $toolbar .= Display::toolbarButton(
            get_lang('ImportSkillsListCSV'),
            api_get_path(WEB_CODE_PATH) . 'admin/skills_import.php',
            'arrow-up',
            'info',
            ['title' => get_lang('BadgesManagement')]
        );

        $extraField = new ExtraField('skill');
        $arrayVals = $extraField->get_handler_field_info_by_tags('tags');
        $tags = [];

        if (isset($arrayVals['options'])) {
            foreach ($arrayVals['options'] as $value) {
                $tags[] = $value;
            }
        }

        /* View */
        $skill = new Skill();
        $skillList = $skill->get_all();
        $extraFieldSearchTagId = isset($_REQUEST['tag_id']) ? $_REQUEST['tag_id'] : 0;

        if ($extraFieldSearchTagId) {
            $skills = [];

            $skillsFiltered = $extraField->getAllSkillPerTag($arrayVals['id'], $extraFieldSearchTagId);
            foreach ($skillList as $index => $value) {
                if (array_search($index, $skillsFiltered)) {
                    $skills[$index] = $value;
                }
            }
            $skillList = $skills;
        }

        $tpl = new Template(get_lang('ManageSkills'));
        $tpl->assign('skills', $skillList);
        $tpl->assign('current_tag_id', $extraFieldSearchTagId);
        $tpl->assign('tags', $tags);
        $templateName = $tpl->get_template('skill/list.tpl');
        $content = $tpl->fetch($templateName);

        $tpl->assign('actions', $toolbar);
        $tpl->assign('content', $content);
        $tpl->display_one_col_template();

        break;
}
