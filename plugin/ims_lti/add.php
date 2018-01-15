<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script();
api_protect_teacher_script();

$plugin = ImsLtiPlugin::create();
$em = Database::getManager();

$type = isset($_GET['type']) ? intval($_GET['type']) : 0;

$course = $em->find('ChamiloCoreBundle:Course', api_get_course_int_id());
$tools = array_filter(
    ImsLtiTool::fetchAll(),
    function ($tool) {
        return (boolean) $tool['is_global'];
    }
);

$isGlobalTool = $type ? array_key_exists($type, $tools) : true;

if (!$isGlobalTool) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('ToolNotAvailable'), 'warning')
    );

    header('Location: '.api_get_self().'?'.api_get_cidreq());
    exit;
}

$form = new FormValidator('ims_lti_add_tool');
$form->addText('name', $plugin->get_lang('ToolName'));

if (!$type) {
    $form->addHtml('<div id="show_advanced_options">');
    $form->addElement('url', 'url', $plugin->get_lang('LaunchUrl'));
    $form->addText('consumer_key', $plugin->get_lang('ConsumerKey'), true);
    $form->addText('shared_secret', $plugin->get_lang('SharedSecret'), true);
    $form->addTextarea('custom_params', $plugin->get_lang('CustomParams'));
    $form->addHtml('</div>');
    $form->addRule('url', get_lang('Required'), 'required');
}

$form->addButtonCreate($plugin->get_lang('AddExternalTool'));

if ($form->validate()) {
    $formValues = $form->getSubmitValues();
    $tool = null;

    if ($type) {
        $baseTool = ImsLtiTool::fetch($type);

        if ($baseTool) {
            $baseTool->setName($formValues['name']);
        }

        $tool = $baseTool;
    } else {
        $tool = new ImsLtiTool();
        $tool
            ->setName($formValues['name'])
            ->setLaunchUrl($formValues['url'])
            ->setConsumerKey($formValues['consumer_key'])
            ->setSharedSecret($formValues['shared_secret'])
            ->setCustomParams($formValues['custom_params'])
            ->isGlobal(false);
        $tool->save();
    }

    if ($tool) {
        $plugin->addCourseTool($course, $tool);

        Display::addFlash(
            Display::return_message($plugin->get_lang('ToolAdded'), 'success')
        );
    } else {
        Display::addFlash(
            Display::return_message($plugin->get_lang('NoTool'), 'error')
        );
    }

    header('Location: '.api_get_course_url());
    exit;
}

$template = new Template($plugin->get_lang('AddExternalTool'));
$template->assign('type', $type);
$template->assign('tools', $tools);
$template->assign('form', $form->returnForm());

$content = $template->fetch('ims_lti/view/add.tpl');

$template->assign('content', $content);
$template->display_one_col_template();
