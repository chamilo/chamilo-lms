<?php
/**
 * This script initiates a video conference session, c the Google Meet.
 */
require_once __DIR__.'/../../vendor/autoload.php';

$course_plugin = 'google_meet'; //needed in order to load the plugin lang variables
require_once __DIR__.'/config.php';

$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(
        WEB_PLUGIN_PATH
    ).'google_meet/resources/css/style.css"/>';

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
                    $idMeet = isset($_GET['id_meet']) ? $_GET['id_meet'] : null;
                    $res = $plugin->deleteMeet($idMeet);
                    if ($res) {
                        $url = api_get_path(WEB_PLUGIN_PATH).'google_meet/start.php?'.api_get_cidreq();
                        header('Location: '.$url);
                    }

                    break;
                case 'add':
                    $actionLinks .= Display::url(
                        Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
                        api_get_path(WEB_PLUGIN_PATH).'google_meet/start.php?'.api_get_cidreq()
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
                            $plugin->get_lang('GoogleMeetURL'),
                            sprintf($plugin->get_lang('GoogleMeetURLHelp'), GoogleMeetPlugin::GOOGLE_MEET_URL),
                        ],
                        true,
                        [
                            'title' => sprintf($plugin->get_lang('GoogleMeetURLHelp'), GoogleMeetPlugin::GOOGLE_MEET_URL),
                        ]
                    );

                    try {
                        $form->addElement(
                            'color',
                            'meet_color',
                            [
                                $plugin->get_lang('MeetColor'),
                                $plugin->get_lang('MeetColorHelp'),
                            ],
                            [
                                'value' => '#1CC88A',
                            ]
                        );
                    } catch (HTML_QuickForm_Error $e) {
                        echo $e;
                    }

                    $form->addHtmlEditor(
                        'meet_description',
                        [
                            $plugin->get_lang('MeetingDescription'),
                            $plugin->get_lang('MeetingDescriptionHelp'),
                        ],
                        false,
                        false,
                        [
                            'ToolbarSet' => 'Minimal',
                        ]
                    );

                    $form->addHidden('type_meet', 1);

                    $form->addButtonSave($plugin->get_lang('Add'));

                    if (!empty($defaults)) {
                        $form->setDefaults($defaults);
                    }

                    try {
                        if ($form->validate()) {
                            $values = $form->exportValues();
                            $res = $plugin->saveMeet($values);
                            if ($res) {
                                $url = api_get_path(WEB_PLUGIN_PATH).'google_meet/start.php?'.api_get_cidreq();
                                header('Location: '.$url);
                            }
                        }
                    } catch (HTML_QuickForm_Error $e) {
                        echo $e;
                    }

                    $tpl->assign('form_room', $form->returnForm());

                    break;
                case 'edit':
                    $actionLinks .= Display::url(
                        Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
                        api_get_path(WEB_PLUGIN_PATH).'google_meet/start.php?'.api_get_cidreq()
                    );

                    $idMeet = isset($_GET['id_meet']) ? (int) $_GET['id_meet'] : 0;
                    $dataMeet = $plugin->getMeet($idMeet);

                    //create form
                    $form = new FormValidator(
                        'edit_meet',
                        'post',
                        api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&'.api_get_cidreq()
                    );

                    $form->addHeader(get_lang('EditMeet'));

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
                            $plugin->get_lang('GoogleMeetURL'),
                            sprintf($plugin->get_lang('GoogleMeetURLHelp'), GoogleMeetPlugin::GOOGLE_MEET_URL),
                        ],
                        true,
                        [
                            'title' => sprintf($plugin->get_lang('GoogleMeetURLHelp'), GoogleMeetPlugin::GOOGLE_MEET_URL),
                        ]
                    );
                    $form->addElement(
                        'color',
                        'meet_color',
                        [
                            $plugin->get_lang('MeetColor'),
                            $plugin->get_lang('MeetColorHelp'),
                        ]
                    );
                    $form->addHtmlEditor(
                        'meet_description',
                        [
                            $plugin->get_lang('MeetingDescription'),
                            $plugin->get_lang('MeetingDescriptionHelp'),
                        ],
                        false,
                        false,
                        [
                            'ToolbarSet' => 'Minimal',
                        ]
                    );

                    $form->addHidden('id', $idMeet);
                    $form->addButtonSave($plugin->get_lang('Save'));

                    $form->setDefaults($dataMeet);

                    if ($form->validate()) {
                        $values = $form->exportValues();
                        $res = $plugin->updateMeet($values);

                        if ($res) {
                            $url = api_get_path(WEB_PLUGIN_PATH).'google_meet/start.php?'.api_get_cidreq();
                            header('Location: '.$url);
                        }
                    }

                    $tpl->assign('form_room', $form->returnForm());

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
$content = $tpl->fetch('google_meet/view/meets.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
