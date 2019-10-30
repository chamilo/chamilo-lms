<?php
/* For licensing terms, see /license.txt */

/**
 * This page allows the administrator to manage the system announcements.
 *
 * @package chamilo.admin.announcement
 */

// Resetting the course id.
$cidReset = true;

// Including the global initialization file.
require_once __DIR__.'/../inc/global.inc.php';

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;
$_SESSION['this_section'] = $this_section;

$action = isset($_GET['action']) ? $_GET['action'] : null;
$action_todo = false;

// Access restrictions
api_protect_admin_script(true);

// Setting breadcrumbs.
$interbreadcrumb[] = [
    "url" => 'index.php',
    "name" => get_lang('Administration'),
];

$visibleList = SystemAnnouncementManager::getVisibilityList();

$tool_name = null;
if (empty($_GET['lang'])) {
    $_GET['lang'] = isset($_SESSION['user_language_choice']) ? $_SESSION['user_language_choice'] : null;
}

if (!empty($action)) {
    $interbreadcrumb[] = [
        "url" => "system_announcements.php",
        "name" => get_lang('Portal news'),
    ];
    if ($action == 'add') {
        $interbreadcrumb[] = [
            "url" => '#',
            "name" => get_lang('Add an announcement'),
        ];
    }
    if ($action == 'edit') {
        $interbreadcrumb[] = ["url" => '#', "name" => get_lang('Edit')];
    }
} else {
    $tool_name = get_lang('Portal news');
}

// Displaying the header.
Display :: display_header($tool_name);
if ($action != 'add' && $action != 'edit') {
    echo '<div class="actions">';
    echo '<a href="?action=add">'.Display::return_icon('add.png', get_lang('Add an announcement'), [], 32).'</a>';
    echo '</div>';
}

/* MAIN CODE */
$show_announcement_list = true;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;

// Form was posted?
if (isset($_POST['action'])) {
    $action_todo = true;
}

// Actions
switch ($action) {
    case 'make_visible':
    case 'make_invisible':
        $status = false;
        if ($action == 'make_visible') {
            $status = true;
        }
        SystemAnnouncementManager::set_visibility(
            $_GET['id'],
            $_GET['person'],
            $status
        );
        echo Display::return_message(get_lang('Update successful'), 'confirmation');
        break;
    case 'delete':
        // Delete an announcement.
        SystemAnnouncementManager::delete_announcement($_GET['id']);
        echo Display::return_message(get_lang('Announcement has been deleted'), 'confirmation');
        break;
    case 'delete_selected':
        foreach ($_POST['id'] as $index => $id) {
            SystemAnnouncementManager::delete_announcement($id);
        }
        echo Display::return_message(get_lang('Announcement has been deleted'), 'confirmation');
        $action_todo = false;
        break;
    case 'add':
        // Add an announcement.
        $values['action'] = 'add';
        // Set default time window: NOW -> NEXT WEEK
        $values['range_start'] = date('Y-m-d H:i:s', api_strtotime(api_get_local_time()));
        $values['range_end'] = date('Y-m-d H:i:s', api_strtotime(api_get_local_time()) + (7 * 24 * 60 * 60));
        $values['range'] =
            substr(api_get_local_time(time()), 0, 16).' / '.
            substr(api_get_local_time(time() + (7 * 24 * 60 * 60)), 0, 16);
        $action_todo = true;
        break;
    case 'edit':
        // Edit an announcement.
        $announcement = SystemAnnouncementManager::get_announcement($_GET['id']);
        $values['id'] = $announcement->id;
        $values['title'] = $announcement->title;
        $values['content'] = $announcement->content;
        $values['start'] = api_get_local_time($announcement->date_start);
        $values['end'] = api_get_local_time($announcement->date_end);
        $values['range'] = substr(api_get_local_time($announcement->date_start), 0, 16).' / '.
            substr(api_get_local_time($announcement->date_end), 0, 16);

        $data = (array) $announcement;
        foreach ($visibleList as $key => $value) {
            if (isset($data[$key])) {
                $values[$key] = $data[$key];
            }
        }

        $values['lang'] = $announcement->lang;
        $values['action'] = 'edit';
        $groups = SystemAnnouncementManager::get_announcement_groups($announcement->id);
        $values['group'] = isset($groups['group_id']) ? $groups['group_id'] : 0;
        $action_todo = true;
        break;
}

if ($action_todo) {
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'add') {
        $form_title = get_lang('Add news');
        $url = api_get_self();
    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit') {
        $form_title = get_lang('Edit News');
        $url = api_get_self().'?id='.intval($_GET['id']);
    }
    $form = new FormValidator('system_announcement', 'post', $url);
    $form->addElement('header', '', $form_title);
    $form->addText('title', get_lang('Title'), true);

    $extraOption = [];
    $extraOption['all'] = get_lang('All');
    $form->addSelectLanguage(
        'lang',
        get_lang('Language'),
        $extraOption,
        ['set_custom_default' => 'all']
    );

    $form->addHtmlEditor(
        'content',
        get_lang('Content'),
        true,
        false,
        [
            'ToolbarSet' => 'PortalNews',
            'Width' => '100%',
            'Height' => '300',
        ]
    );

    // Add Picture Announcements
    try {
        $form->addFile(
            'picture',
            [
                get_lang('Add Picture'),
                get_lang('The image must have a maximum dimension of 950 x 712 pixelss'),
            ],
            ['id' => 'picture', 'class' => 'picture-form', 'crop_image' => true, 'crop_ratio' => '4/3']
        );

        $allowed_picture_types = api_get_supported_image_extensions(false);

        $form->addRule(
            'picture',
            get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(',', $allowed_picture_types).')',
            'filetype',
            $allowed_picture_types
        );

        $image = '';
        // Display announcements picture
        $store_path = api_get_path(SYS_UPLOAD_PATH).'announcements'; // course path
        if (file_exists($store_path.'/announcement_'.$announcement->id.'.png')) {
            $announcementsPath = api_get_path(WEB_UPLOAD_PATH).'announcements'; // announcement web path
            $announcementsImage = $announcementsPath.'/announcement_'.$announcement->id.'_100x100.png?'.rand(1, 1000); // redimensioned image 85x85
            $image = '<div class="row"><label class="col-md-2 control-label">'.get_lang('Image').'</label> 
                    <div class="col-md-8"><img class="img-thumbnail" src="'.$announcementsImage.'" /></div></div>';

            $form->addHtml($image);
            $form->addElement('checkbox', 'delete_picture', null, get_lang('Delete picture'));
        }
    } catch (Exception $e) {
    } catch (HTML_QuickForm_Error $e) {
        error_log($e);
    }

    $form->addDateRangePicker(
        'range',
        get_lang('Start'),
        true,
        ['id' => 'range']
    );

    $group = [];
    foreach ($visibleList as $key => $name) {
        $group[] = $form->createElement(
            'checkbox',
            $key,
            null,
            $name
        );
    }

    $form->addGroup($group, null, get_lang('Visible'));
    $form->addElement('hidden', 'id');
    $userGroup = new UserGroup();
    $group_list = $userGroup->get_all();

    if (!empty($group_list)) {
        $group_list = array_column($group_list, 'name', 'id');
        $group_list[0] = get_lang('All');
        $form->addElement(
            'select',
            'group',
            get_lang('Announcement for a group'),
            $group_list
        );
    }

    $values['group'] = isset($values['group']) ? $values['group'] : '0';
    $form->addElement('checkbox', 'send_mail', null, get_lang('Send mail'));

    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'add') {
        $form->addElement('checkbox', 'add_to_calendar', null, get_lang('Add to calendar'));
        $text = get_lang('Add news');
        $class = 'add';
        $form->addElement('hidden', 'action', 'add');
    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit') {
        $text = get_lang('Edit News');
        $class = 'save';
        $form->addElement('hidden', 'action', 'edit');
    }
    $form->addElement('checkbox', 'send_email_test', null, get_lang('Send an email to myself for testing purposes.'));
    $form->addButtonSend($text, 'submit');
    $form->setDefaults($values);

    if ($form->validate()) {
        $values = $form->getSubmitValues();
        $visibilityResult = [];
        foreach ($visibleList as $key => $value) {
            if (!isset($values[$key])) {
                $values[$key] = false;
            }
            $visibilityResult[$key] = (int) $values[$key];
        }

        if ($values['lang'] == 'all') {
            $values['lang'] = null;
        }

        $sendMail = isset($values['send_mail']) ? $values['send_mail'] : null;

        switch ($values['action']) {
            case 'add':
                $announcement_id = SystemAnnouncementManager::add_announcement(
                    $values['title'],
                    $values['content'],
                    $values['range_start'],
                    $values['range_end'],
                    $visibilityResult,
                    $values['lang'],
                    $sendMail,
                    empty($values['add_to_calendar']) ? false : true,
                    empty($values['send_email_test']) ? false : true
                );

                if ($announcement_id !== false) {
                    // ADD Picture
                    $picture = $_FILES['picture'];
                    if (!empty($picture['name'])) {
                        $picture_uri = SystemAnnouncementManager::update_announcements_picture(
                            $announcement_id,
                            $picture['tmp_name'],
                            $values['picture_crop_result']
                        );
                    }

                    if (isset($values['group'])) {
                        SystemAnnouncementManager::announcement_for_groups(
                            $announcement_id,
                            [$values['group']]
                        );
                        echo Display::return_message(
                            get_lang('Announcement has been added'),
                            'confirmation'
                        );
                    }
                } else {
                    $show_announcement_list = false;
                    $form->display();
                }
                break;
            case 'edit':
                $sendMailTest = isset($values['send_email_test']) ? $values['send_email_test'] : null;

                if (SystemAnnouncementManager::update_announcement(
                    $values['id'],
                    $values['title'],
                    $values['content'],
                    $values['range_start'],
                    $values['range_end'],
                    $visibilityResult,
                    $values['lang'],
                    $sendMail,
                    $sendMailTest
                )) {
                    $deletePicture = isset($values['delete_picture']) ? $values['delete_picture'] : '';

                    if ($deletePicture) {
                        SystemAnnouncementManager::deleteAnnouncementPicture($values['id']);
                    } else {
                        $picture = $_FILES['picture'];
                        if (!empty($picture['name'])) {
                            $picture_uri = SystemAnnouncementManager::update_announcements_picture(
                                $values['id'],
                                $picture['tmp_name'],
                                $values['picture_crop_result']
                            );
                        }
                    }

                    if (isset($values['group'])) {
                        SystemAnnouncementManager::announcement_for_groups(
                            $values['id'],
                            [$values['group']]
                        );
                        echo Display::return_message(
                            get_lang('AnnouncementUpdate successful'),
                            'confirmation'
                        );
                    }
                } else {
                    $show_announcement_list = false;
                    $form->display();
                }
                break;
            default:
                break;
        }
        $show_announcement_list = true;
    } else {
        $form->display();
        $show_announcement_list = false;
    }
}

if ($show_announcement_list) {
    $announcements = SystemAnnouncementManager :: get_all_announcements();
    $announcement_data = [];
    foreach ($announcements as $index => $announcement) {
        $row = [];
        $row[] = $announcement->id;
        $row[] = Display::return_icon(($announcement->visible ? 'accept.png' : 'exclamation.png'), ($announcement->visible ? get_lang('The announcement is available') : get_lang('The announcement is not available')));
        $row[] = $announcement->title;
        $row[] = api_convert_and_format_date($announcement->date_start);
        $row[] = api_convert_and_format_date($announcement->date_end);

        $data = (array) $announcement;
        foreach ($visibleList as $key => $value) {
            $value = $data[$key];
            $action = $value ? 'make_invisible' : 'make_visible';
            $row[] = "<a href=\"?id=".$announcement->id."&person=".$key."&action=".$action."\">".
                Display::return_icon(($value ? 'eyes.png' : 'eyes-close.png'), get_lang('Show/Hide'))."</a>";
        }
        /*$row[] = "<a href=\"?id=".$announcement->id."&person=".SystemAnnouncementManager::VISIBLE_TEACHER."&action=".($announcement->visible_teacher ? 'make_invisible' : 'make_visible')."\">".Display::return_icon(($announcement->visible_teacher ? 'eyes.png' : 'eyes-close.png'), get_lang('Show/Hide'))."</a>";
        $row[] = "<a href=\"?id=".$announcement->id."&person=".SystemAnnouncementManager::VISIBLE_STUDENT."&action=".($announcement->visible_student ? 'make_invisible' : 'make_visible')."\">".Display::return_icon(($announcement->visible_student ? 'eyes.png' : 'eyes-close.png'), get_lang('Show/Hide'))."</a>";
        $row[] = "<a href=\"?id=".$announcement->id."&person=".SystemAnnouncementManager::VISIBLE_GUEST."&action=".($announcement->visible_guest ? 'make_invisible' : 'make_visible')."\">".Display::return_icon(($announcement->visible_guest ? 'eyes.png' : 'eyes-close.png'), get_lang('Show/Hide'))."</a>";*/

        $row[] = $announcement->lang;
        $row[] = "<a href=\"?action=edit&id=".$announcement->id."\">".Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL)."</a> <a href=\"?action=delete&id=".$announcement->id."\" title=".addslashes(api_htmlentities(get_lang('Please confirm your choice')))." class='delete-swal' >".Display::return_icon('delete.png', get_lang('Delete'), [], ICON_SIZE_SMALL)."</a>";
        $announcement_data[] = $row;
    }
    $table = new SortableTableFromArray($announcement_data);
    $table->set_header(0, '', false, 'width="20px"');
    $table->set_header(1, get_lang('active'));
    $table->set_header(2, get_lang('Title'));
    $table->set_header(3, get_lang('Start'));
    $table->set_header(4, get_lang('End'));

    $count = 5;
    foreach ($visibleList as $key => $title) {
        $table->set_header($count, $title);
        $count++;
    }

    $table->set_header($count++, get_lang('Language'));
    $table->set_header($count++, get_lang('Edit'), false, 'width="50px"');
    $form_actions = [];
    $form_actions['delete_selected'] = get_lang('Delete');
    $table->set_form_actions($form_actions);
    $table->display();
}

Display :: display_footer();
