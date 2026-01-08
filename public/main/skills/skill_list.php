<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Framework\Container;

/**
 * Skill list for management.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @author Julio Montoya
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

SkillModel::isAllowed();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$skillId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$entityManager = Database::getManager();
$skillRepo = Container::getSkillRepository();

switch ($action) {
    case 'enable':
        /** @var Skill|null $skill */
        $skill = $skillRepo->find($skillId);

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
                    sprintf(get_lang('Skill "%s" enabled'), $skill->getTitle()),
                    'success'
                )
            );
        }

        header('Location: '.api_get_self());
        exit;

    case 'disable':
        /** @var Skill|null $skill */
        $skill = $skillRepo->find($skillId);

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
                /** @var Skill|null $childSkill */
                $childSkill = $skillRepo->find($child['id']);

                if (null === $childSkill) {
                    continue;
                }

                $childSkill->setStatus(0);
                $childSkill->setUpdatedAt($updatedAt);
                $entityManager->persist($childSkill);
            }

            $entityManager->flush();

            Display::addFlash(
                Display::return_message(
                    sprintf(get_lang('Skill "%s" disabled'), $skill->getTitle()),
                    'success'
                )
            );
        }

        header('Location: '.api_get_self());
        exit;

    case 'list':
    default:
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
            'name' => get_lang('Administration'),
        ];

        $toolbar = Display::url(
            Display::getMdiIcon(ActionIcon::ADD, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Create skill')),
            api_get_path(WEB_CODE_PATH).'skills/skill_create.php',
            ['title' => get_lang('Create skill')]
        );

        $toolbar .= Display::url(
            Display::getMdiIcon(ObjectIcon::WHEEL, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Skills wheel')),
            Container::getRouter()->generate('skill_wheel'),
            ['title' => get_lang('Skills wheel')]
        );

        $toolbar .= Display::url(
            Display::getMdiIcon(ActionIcon::IMPORT_ARCHIVE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Import skills from a CSV file')),
            api_get_path(WEB_CODE_PATH).'skills/skills_import.php',
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

        $skill = new SkillModel();
        $skillList = $skill->getAllSkills();

        // Tag filter (fix: avoid losing index 0 and use strict comparisons).
        $extraFieldSearchTagId = $_REQUEST['tag_id'] ?? 0;

        if ($extraFieldSearchTagId) {
            $skills = [];
            $skillsFiltered = $extraField->getAllSkillPerTag($arrayVals['id'], $extraFieldSearchTagId);

            foreach ($skillList as $index => $value) {
                // Use in_array with strict mode, because array_search() can return 0 and be treated as false.
                if (in_array($index, $skillsFiltered, true)) {
                    $skills[$index] = $value;
                }
            }

            $skillList = $skills;
        }

        $tpl = new Template(get_lang('Manage skills'));
        $tpl->assign('skills', $skillList);
        $tpl->assign('current_tag_id', $extraFieldSearchTagId);
        $tpl->assign('tags', $tags);

        $templateName = $tpl->get_template('skill/list.html.twig');
        $content = $tpl->fetch($templateName);

        $tpl->assign('actions', Display::toolbarAction('toolbar', [$toolbar]));
        $tpl->assign('content', $content);
        $tpl->display_one_col_template();
        break;
}
