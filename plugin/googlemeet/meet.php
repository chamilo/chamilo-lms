<?php
/**
 * This script initiates a video conference session, calling the Zoom Conector API.
 */
require_once __DIR__.'/../../vendor/autoload.php';

$course_plugin = 'googlemeet'; //needed in order to load the plugin lang variables
require_once __DIR__.'/config.php';

$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(
        WEB_PLUGIN_PATH
    ).'googlemeet/resources/css/style.css"/>';

$plugin = GoogleMeetPlugin::create();

$userId = api_get_user_id();
$tool_name = $plugin->get_lang('tool_title');
$tpl = new Template($tool_name);

$isAdmin = api_is_platform_admin();
$isTeacher = api_is_teacher();
$message = null;

$action = isset($_GET['action']) ? $_GET['action'] : null;
$enable = $plugin->get('google_meet_enabled') == 'true';
$actionLinks = '';

if ($enable) {
    if ($isAdmin || $isTeacher) {
        if ($action) {
            switch ($action) {
                case 'delete':
                    break;
                case 'add':
                    $actionLinks .= Display::url(
                        Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
                        api_get_path(WEB_PLUGIN_PATH).'googlemeet/start.php?'.api_get_cidreq()
                    );
                    //create form
                    $form = new FormValidator(
                        'add_meet',
                        'post',
                        api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&'.api_get_cidreq()
                    );

                    $form->addHeader(get_lang('AddMeet'));
                    $form->addText(
                        'meet_name',
                        [
                            $plugin->get_lang('MeetName'),
                            $plugin->get_lang('MeetNameHelp'),
                        ],
                        true,
                        [
                            'title' => $plugin->get_lang('MeetNameHelp'),
                        ]
                    );

                    $form->addText(
                        'meet_url',
                        [
                            $plugin->get_lang('InstantMeetURL'),
                            $plugin->get_lang('InstantMeetURLHelp'),
                        ],
                        true,
                        [
                            'title' => $plugin->get_lang('InstantMeetURLHelp'),
                        ]
                    );

                    $form->addHidden('type_meet', 1);

                    $form->addButtonSave($plugin->get_lang('Add'));

                    try {
                        if ($form->validate()) {
                            $values = $form->exportValues();
                            $res = $plugin->saveMeet($values);

                        }
                    } catch (HTML_QuickForm_Error $e) {
                        echo $e;
                    }

                    $tpl->assign('form_room', $form->returnForm());

                    break;
                case 'edit':
                    $actionLinks .= Display::url(
                        Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
                        api_get_self().'?action=list&'.api_get_cidreq()
                    );


                    break;
            }
        }
    }
}

if ($isAdmin || $isTeacher) {

    $tpl->assign(
        'actions',
        Display::toolbarAction('toolbar', [$actionLinks])
    );
}

$tpl->assign('message', $message);
$content = $tpl->fetch('googlemeet/view/meet.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();