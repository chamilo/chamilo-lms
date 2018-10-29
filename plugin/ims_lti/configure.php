<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script();
api_protect_teacher_script();

$plugin = ImsLtiPlugin::create();
$em = Database::getManager();
$toolsRepo = $em->getRepository('ChamiloPluginBundle:ImsLti\ImsLtiTool');

/** @var ImsLtiTool $baseTool */
$baseTool = isset($_REQUEST['type']) ? $toolsRepo->find(intval($_REQUEST['type'])) : null;
$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : 'add';

/** @var Course $course */
$course = $em->find('ChamiloCoreBundle:Course', api_get_course_int_id());
$addedTools = $toolsRepo->findBy(['course' => $course]);
$globalTools = $toolsRepo->findBy(['isGlobal' => true]);

if ($baseTool && !$baseTool->isGlobal()) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('ToolNotAvailable'), 'warning')
    );

    header('Location: '.api_get_self().'?'.api_get_cidreq());
    exit;
}

switch ($action) {
    case 'add':
        $form = new FormValidator('ims_lti_add_tool');
        $form->addHeader($plugin->get_lang('ToolSettings'));

        if ($baseTool) {
            $form->addHtml('<p class="lead">'.Security::remove_XSS($baseTool->getDescription()).'</p>');
        }

        $form->addText('name', get_lang('Name'));

        if (!$baseTool) {
            $form->addElement('url', 'url', $plugin->get_lang('LaunchUrl'));
            $form->addText('consumer_key', $plugin->get_lang('ConsumerKey'), true);
            $form->addText('shared_secret', $plugin->get_lang('SharedSecret'), true);
            $form->addRule('url', get_lang('Required'), 'required');
        }

        $form->addButtonAdvancedSettings('lti_adv');
        $form->addHtml('<div id="lti_adv_options" style="display:none;">');
        $form->addTextarea('description', get_lang('Description'), ['rows' => 3]);

        if (!$baseTool) {
            $form->addTextarea(
                'custom_params',
                [$plugin->get_lang('CustomParams'), $plugin->get_lang('CustomParamsHelp')]
            );
            $form->addCheckBox('deep_linking', $plugin->get_lang('SupportDeepLinking'), get_lang('Yes'));
        }

        if ($baseTool) {
            $form->addHidden('type', $baseTool->getId());
        }

        $form->addHtml('</div>');

        $form->addButtonCreate($plugin->get_lang('AddExternalTool'));

        if ($form->validate()) {
            $formValues = $form->getSubmitValues();
            $tool = null;

            if ($baseTool) {
                $tool = clone $baseTool;
            } else {
                $tool = new ImsLtiTool();
                $tool
                    ->setLaunchUrl($formValues['url'])
                    ->setConsumerKey($formValues['consumer_key'])
                    ->setSharedSecret($formValues['shared_secret'])
                    ->setCustomParams(
                        empty($formValues['custom_params']) ? null : $formValues['custom_params']
                    );
            }

            $tool
                ->setName($formValues['name'])
                ->setDescription(
                    empty($formValues['description']) ? null : $formValues['description']
                )
                ->setIsGlobal(false)
                ->setCourse($course);

            $em->persist($tool);
            $em->flush();

            $plugin->addCourseTool($course, $tool);

            Display::addFlash(
                Display::return_message($plugin->get_lang('ToolAdded'), 'success')
            );

            header('Location: '.api_get_self().'?'.api_get_cidreq());
            exit;
        }
        break;
    case 'edit':
        $form = new FormValidator('ims_lti_edit_tool');
        $form->addHeader($plugin->get_lang('ToolSettings'));

        /** @var ImsLtiTool|null $tool */
        $tool = null;

        if (!empty($_REQUEST['id'])) {
            $tool = $em->find('ChamiloPluginBundle:ImsLti\ImsLtiTool', (int) $_REQUEST['id']);
        }

        if (empty($tool)) {
            Display::addFlash(
                Display::return_message($plugin->get_lang('ToolNotAvailable'), 'warning')
            );

            break;
        }
        
        if (!ImsLtiPlugin::existsToolInCourse($tool->getId(), $course)) {
            Display::addFlash(
                Display::return_message($plugin->get_lang('ToolNotAvailable'), 'warning')
            );

            break;
        }

        $form->addText('name', get_lang('Title'));
        $form->addButtonAdvancedSettings('lti_adv');
        $form->addHtml('<div id="lti_adv_options" style="display:none;">');
        $form->addTextarea('description', get_lang('Description'), ['rows' => 3]);
        $form->addTextarea(
            'custom_params',
            [$plugin->get_lang('CustomParams'), $plugin->get_lang('CustomParamsHelp')]
        );
        $form->addHtml('</div>');
        $form->addButtonUpdate($plugin->get_lang('EditExternalTool'));
        $form->addHidden('id', $tool->getId());
        $form->addHidden('action', 'edit');
        $form->applyFilter('__ALL__', 'Security::remove_XSS');

        if ($form->validate()) {
            $formValues = $form->getSubmitValues();

            $tool
                ->setName($formValues['name'])
                ->setDescription($formValues['description'])
                ->setCustomParams(
                    empty($formValues['custom_params']) ? null : $formValues['custom_params']
                );

            $em->persist($tool);
            $em->flush();

            $courseTool = $plugin->findCourseToolByLink($course, $tool);

            if ($courseTool) {
                $plugin->updateCourseTool($courseTool, $tool);
            }

            Display::addFlash(
                Display::return_message($plugin->get_lang('ToolEdited'), 'success')
            );

            header('Location: '.api_get_self().'?'.api_get_cidreq());
            exit;
        }

        $form->setDefaults([
            'name' => $tool->getName(),
            'description' => $tool->getDescription(),
            'custom_params' => $tool->getCustomParams(),
        ]);
        break;
}

$categories = Category::load(null, null, $course->getCode());

$template = new Template($plugin->get_lang('AddExternalTool'));
$template->assign('type', $baseTool ? $baseTool->getId() : null);
$template->assign('added_tools', $addedTools);
$template->assign('global_tools', $globalTools);
$template->assign('form', $form->returnForm());

$content = $template->fetch('ims_lti/view/add.tpl');

$actions = Display::url(
    Display::return_icon('add.png', $plugin->get_lang('AddExternalTool'), [], ICON_SIZE_MEDIUM),
    api_get_self().'?'.api_get_cidreq()
);

if (!empty($categories)) {
    $actions .= Display::url(
        Display::return_icon('gradebook.png', get_lang('MakeQualifiable'), [], ICON_SIZE_MEDIUM),
        './gradebook/add_eval.php?selectcat='.$categories[0]->get_id().'&'.api_get_cidreq()
    );
}

$template->assign('actions', Display::toolbarAction('lti_toolbar', [$actions]));
$template->assign('content', $content);
$template->display_one_col_template();
