<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Skill;

/**
 * Skill list for management.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

SkillModel::isAllowed();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$skillId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$entityManager = Database::getManager();

switch ($action) {
    case 'enable':
        $skill = $entityManager->find(Skill::class, $skillId);

        if (is_null($skill)) {
            Display::addFlash(
                Display::return_message(
                    get_lang('Skill not found'),
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
                    sprintf(get_lang('Skill "%s" enabled'), $skill->getName()),
                    'success'
                )
            );
        }

        header('Location: '.api_get_self());
        exit;
        break;
    case 'disable':
        /** @var Skill $skill */
        $skill = $entityManager->find(Skill::class, $skillId);

        if (is_null($skill)) {
            Display::addFlash(
                Display::return_message(
                    get_lang('Skill not found'),
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

            $skillObj = new SkillModel();
            $children = $skillObj->getChildren($skill->getId());

            foreach ($children as $child) {
                $skill = $entityManager->find(
                    Skill::class,
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
                    sprintf(get_lang('Skill "%s" disabled'), $skill->getName()),
                    'success'
                )
            );
        }

        header('Location: '.api_get_self());
        exit;
        break;
    case 'list':
    default:
        $interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];

        $toolbar = Display::url(
            Display::return_icon(
                'add.png',
                get_lang('Create skill'),
                null,
                ICON_SIZE_MEDIUM
            ),
            api_get_path(WEB_CODE_PATH).'admin/skill_create.php',
            ['title' => get_lang('Create skill')]
        );

        $toolbar .= Display::url(
            Display::return_icon(
                'wheel_skill.png',
                get_lang('Skills wheel'),
                null,
                ICON_SIZE_MEDIUM
            ),
            api_get_path(WEB_CODE_PATH).'admin/skills_wheel.php',
            ['title' => get_lang('Skills wheel')]
        );

        $toolbar .= Display::url(
            Display::return_icon(
                'import_csv.png',
                get_lang('Import skills from a CSV file'),
                null,
                ICON_SIZE_MEDIUM
            ),
            api_get_path(WEB_CODE_PATH).'admin/skills_import.php',
            ['title' => get_lang('Import skills from a CSV file')]
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
        $skill = new SkillModel();
        $skillList = $skill->getAllSkills();
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

        $tpl = new Template(get_lang('Manage skills'));
        $tpl->assign('skills', $skillList);
        $tpl->assign('current_tag_id', $extraFieldSearchTagId);
        $tpl->assign('tags', $tags);
        $templateName = $tpl->get_template('skill/list.tpl');
        $content = $tpl->fetch($templateName);

        $tpl->assign(
            'actions',
            Display::toolbarAction('toolbar', [$toolbar])
        );
        $tpl->assign('content', $content);
        $tpl->display_one_col_template();
        break;
}
