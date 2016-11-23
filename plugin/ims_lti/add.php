<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script();
api_protect_teacher_script();

$plugin = ImsLtiPlugin::create();
$em = Database::getManager();

$course = $em->find('ChamiloCoreBundle:Course', api_get_course_int_id());
$tools = ImsLtiTool::fetchAll();

$types = [
    '0' => get_lang('None')
];

foreach ($tools as $tool) {
    $types[$tool['id']] = $tool['name'];
}

$form = new FormValidator('ims_lti_add_tool');
$form->addText('tool_name', $plugin->get_lang('ToolName'));
$form->addSelect('type', $plugin->get_lang('Type'), $types);
$form->addRule('type', get_lang('Required'), 'required');
$form->addHtml('<div id="show_advanced_options">');
$form->addElement('url', 'url', $plugin->get_lang('LaunchUrl'));
$form->addText('consumer_key', $plugin->get_lang('ConsumerKey'), false);
$form->addText('shared_secret', $plugin->get_lang('SharedSecret'), false);
$form->addTextarea('custom_params', $plugin->get_lang('CustomParams'));
$form->addHtml('</div>');
$form->addButtonCreate($plugin->get_lang('AddExternalTool'));

if ($form->validate()) {
    $formValues = $form->getSubmitValues();

    if (!empty($formValues['type'])) {
        $tool = ImsLtiTool::fetch($formValues['type']);

        if (!$tool) {
            Display::addFlash(
                Display::return_message($plugin->get_lang('NoTool'))
            );

            // redirect to course
            exit;
        }

        $plugin->addCourseTool($course, $tool);
    }
}

$template = new Template($plugin->get_lang('AddExternalTool'));
$template->assign('form', $form->returnForm());

$content = $template->fetch('ims_lti/view/add.tpl');

$template->assign('content', $content);
$template->display_one_col_template();
