<?php
/**
 * This script initiates a video conference session, calling the Zoom Conector API.
 */
require_once __DIR__.'/../../vendor/autoload.php';

$course_plugin = 'zoom'; //needed in order to load the plugin lang variables
require_once __DIR__.'/config.php';

$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(
        WEB_PLUGIN_PATH
    ).'zoom/resources/css/style.css"/>';

$plugin = ZoomPlugin::create();

$tool_name = $plugin->get_lang('plugin_title');
$tpl = new Template($tool_name);
$message = null;
$userId = api_get_user_id();

$courseInfo = api_get_course_info();
$isTeacher = api_is_teacher();
$isAdmin = api_is_platform_admin();
$isStudent = api_is_student();

$action = isset($_GET['action']) ? $_GET['action'] : null;
$enable = $plugin->get('zoom_enabled') == 'true';
$viewCredentials = $plugin->get('view_credentials') == 'true';
$idCourse = $courseInfo['real_id'];

$urlHome = api_get_path(WEB_PLUGIN_PATH).'zoom/start.php?'.api_get_cidreq();
$urlListRoom = api_get_path(WEB_PLUGIN_PATH).'zoom/list.php?action=list&'.api_get_cidreq();
$urlChangeRoom = api_get_path(WEB_PLUGIN_PATH).'zoom/start.php?action=remove&'.api_get_cidreq();
$urlAddRoom = api_get_path(WEB_PLUGIN_PATH).'zoom/start.php?action=add&'.api_get_cidreq();

if ($enable) {
    if ($isAdmin || $isTeacher || $isStudent) {

        $idRoomAssociate = $plugin->getIdRoomAssociateCourse($idCourse);

        if ($idRoomAssociate) {
            $roomInfo = $plugin->getRoomInfo($idRoomAssociate);
            $tpl->assign('room', $roomInfo);
        }

        $listRooms = $list = [];

        $listRoomsAdmin = $plugin->listZoomsAdmin(1);
        $listRoomsUser = $plugin->listZooms(2, $userId, true);
        if (is_array($listRoomsAdmin) && is_array($listRoomsUser)) {
            $listRooms = array_merge($listRoomsAdmin, $listRoomsUser);
        } else {
            $listRooms = $plugin->listZooms(2, $userId, true);
        }

        foreach ($listRooms as $room) {
            $type = $plugin->get_lang('PersonalRoom');
            if ($room['type_room'] == 1) {
                $type = $plugin->get_lang('GeneralRoom');
            }
            $list[$room['id']] = $room['room_name'].' - '.$room['room_id'].' - '.$type;
        }

        //create form
        $form = new FormValidator('add_room', 'post', $urlAddRoom);
        $form->addHeader($plugin->get_lang('ZoomVideoConferencingAccess'));
        $form->addHidden(
            'action',
            'add'
        );
        $form->addSelect(
            'id_room',
            [
                $plugin->get_lang('ListRoomsAccounts'),
                $plugin->get_lang('ListRoomsAccountsHelp'),
            ],
            $list,
            [
                'title' => $plugin->get_lang('ListRoomsAccounts'),
            ]
        );
        $form->addButtonSave($plugin->get_lang('AssociateRoomCourse'));


        if ($action) {
            switch ($action) {
                case 'add':
                    if ($form->validate()) {
                        $values = $form->exportValues();
                        $idRoom = $values['id_room'];
                        $res = $plugin->associateRoomCourse($idCourse, $idRoom);
                        if ($res) {
                            header('Location: '.$urlHome);
                        }
                    }
                    $tpl->assign('is_add', true);
                    $tpl->assign('form_zoom', $form->returnForm());
                    break;
                case 'remove':

                    if ($idRoomAssociate) {
                        $res = $plugin->removeRoomZoomCourse($idCourse, $idRoomAssociate);
                        if ($res) {
                            header('Location: '.$urlAddRoom);
                        }
                    }

                    break;
            }

        }
    }
}


$tpl->assign('course', $courseInfo);
$tpl->assign('message', $message);
$tpl->assign('is_admin', $isAdmin);
$tpl->assign('is_student', $isStudent);
$tpl->assign('is_teacher', $isTeacher);
$tpl->assign('url_list_room', $urlListRoom);
$tpl->assign('url_change_room', $urlChangeRoom);
$tpl->assign('url_add_room', $urlAddRoom);
$tpl->assign('view_pass', $viewCredentials);
$content = $tpl->fetch('zoom/view/start.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();