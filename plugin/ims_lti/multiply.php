<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Symfony\Component\HttpFoundation\Request;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = ImsLtiPlugin::create();
$webPluginPath = api_get_path(WEB_PLUGIN_PATH).'ims_lti/';

$em = Database::getManager();

try {
    if ($plugin->get('enabled') !== 'true') {
        throw new Exception(get_lang('NotAllowed'));
    }

    $request = Request::createFromGlobals();
    /** @var ImsLtiTool $tool */
    $tool = $em->find('ChamiloPluginBundle:ImsLti\ImsLtiTool', $request->query->getInt('id'));

    if (!$tool) {
        throw new Exception($plugin->get_lang('NoTool'));
    }

    if ($tool->getParent()) {
        throw new Exception($plugin->get_lang('NoAllowed'));
    }

    $content = '';

    $courses = ImsLtiPlugin::getCoursesForParentTool($tool);

    $slctCourses = [];

    /** @var \Chamilo\CoreBundle\Entity\Course $course */
    foreach ($courses as $course) {
        $slctCourses[$course->getId()] = $course->getName();
    }

    $selectedCoursesIds = array_keys($slctCourses);

    $form = new FormValidator('frm_multiply', 'post', api_get_self().'?id='.$tool->getId());
    $form->addLabel($plugin->get_lang('Tool'), $tool->getName());
    $form->addSelectAjax(
        'courses',
        get_lang('Courses'),
        $slctCourses,
        ['url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course', 'multiple' => true]
    );
    $form->addCheckBox('all_courses', '', $plugin->get_lang('AddInAllCourses'));
    $form->addCheckBox('tool_visible', get_lang('SetVisible'), get_lang('ToolIsNowVisible'));
    $form->addButtonExport(get_lang('Save'));

    if ($form->validate()) {
        $em = Database::getManager();
        $formValues = $form->exportValues();
        $formValues['courses'] = empty($formValues['courses']) ? [] : $formValues['courses'];
        $formValues['tool_visible'] = !empty($formValues['tool_visible']);

        if (!empty($formValues['all_courses'])) {
            $courseList = Database::select('id', Database::get_main_table(TABLE_MAIN_COURSE));
            $formValues['courses'] = array_keys($courseList);
        }

        $courseIdsToDelete = array_diff($selectedCoursesIds, $formValues['courses']);
        $newSelectedCourseIds = array_diff($formValues['courses'], $selectedCoursesIds);

        if ($courseIdsToDelete) {
            $toolLinks = [];

            /** @var ImsLtiTool $childInCourse */
            foreach ($tool->getChildrenInCourses($courseIdsToDelete) as $childInCourse) {
                $toolLinks[] = "ims_lti/start.php?id={$childInCourse->getId()}";

                $em->remove($childInCourse);
            }

            $em->flush();

            if (!empty($toolLinks)) {
                $em
                    ->createQuery(
                        "DELETE FROM ChamiloCourseBundle:CTool ct WHERE ct.category = :category AND ct.link IN (:links)"
                    )
                    ->execute(['category' => 'plugin', 'links' => $toolLinks]);
            }
        }

        if ($newSelectedCourseIds) {
            foreach ($newSelectedCourseIds as $newSelectedCourseId) {
                $newSelectedCourse = api_get_course_entity($newSelectedCourseId);

                $newTool = clone $tool;
                $newTool->setParent($tool);
                $newTool->setCourse($newSelectedCourse);

                $em->persist($newTool);
                $em->flush();

                if ($tool->isActiveDeepLinking()) {
                    continue;
                }

                $plugin->addCourseTool(
                    api_get_course_entity($newSelectedCourseId),
                    $newTool,
                    $formValues['tool_visible']
                );
            }
        }

        Display::addFlash(
            Display::return_message(get_lang('ItemUpdated'))
        );

        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'ims_lti/admin.php');
        exit;
    }

    $form->setDefaults(
        [
            'courses' => $selectedCoursesIds,
            'tool_visible' => true,
        ]
    );
    $form->protect();

    $content = $form->returnForm();

    $interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'admin/index.php', 'name' => get_lang('PlatformAdmin')];
    $interbreadcrumb[] = ['url' => api_get_path(WEB_PLUGIN_PATH).'ims_lti/admin.php', 'name' => $plugin->get_title()];

    $template = new Template($plugin->get_lang('AddInCourses'));
    $template->assign('header', $plugin->get_lang('AddInCourses'));
    $template->assign('content', $content);
    $template->display_one_col_template();
} catch (Exception $exception) {
    Display::addFlash(
        Display::return_message($exception->getMessage(), 'error')
    );

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'ims_lti/admin.php');
}
