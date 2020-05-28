<?php
/* For licensing terms, see /license.txt */

/**
 * Skill list for management.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

Skill::isAllowed();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$skillId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

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

        header('Location: '.api_get_self());
        exit;
        break;
    case 'disable':
        /** @var \Chamilo\CoreBundle\Entity\Skill $skill */
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
            $children = $skillObj->getChildren($skill->getId());

            foreach ($children as $child) {
                $skill = $entityManager->find(
                    'ChamiloCoreBundle:Skill',
                    $child['id']
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

        header('Location: '.api_get_self());
        exit;
        break;
    case 'list':
    default:
        $interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];

        $toolbar = Display::url(
            Display::return_icon(
                'add.png',
                get_lang('CreateSkill'),
                null,
                ICON_SIZE_MEDIUM
            ),
            api_get_path(WEB_CODE_PATH).'admin/skill_create.php',
            ['title' => get_lang('CreateSkill')]
        );

        $toolbar .= Display::url(
            Display::return_icon(
                'wheel_skill.png',
                get_lang('SkillsWheel'),
                null,
                ICON_SIZE_MEDIUM
            ),
            api_get_path(WEB_CODE_PATH).'admin/skills_wheel.php',
            ['title' => get_lang('SkillsWheel')]
        );

        $toolbar .= Display::url(
            Display::return_icon(
                'import_csv.png',
                get_lang('ImportSkillsListCSV'),
                null,
                ICON_SIZE_MEDIUM
            ),
            api_get_path(WEB_CODE_PATH).'admin/skills_import.php',
            ['title' => get_lang('ImportSkillsListCSV')]
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

        $tpl->assign(
            'actions',
            Display::toolbarAction('toolbar', [$toolbar], [12])
        );
        $tpl->assign('content', $content);
        $tpl->display_one_col_template();
        break;
}
