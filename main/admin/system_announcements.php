<?php

/* For licensing terms, see /license.txt */

/**
 * This page allows the administrator to manage the system announcements.
 */

// Resetting the course id.
use Chamilo\CoreBundle\Entity\SysAnnouncement;
use Chamilo\PluginBundle\Zoom\Meeting;

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

$allowCareers = api_get_configuration_value('allow_careers_in_global_announcements');

// Setting breadcrumbs.
$interbreadcrumb[] = [
    'url' => 'index.php',
    'name' => get_lang('PlatformAdmin'),
];

$visibleList = SystemAnnouncementManager::getVisibilityList();

$tool_name = null;
if (empty($_GET['lang'])) {
    $_GET['lang'] = isset($_SESSION['user_language_choice']) ? $_SESSION['user_language_choice'] : null;
}

if (!empty($action)) {
    $interbreadcrumb[] = [
        'url' => 'system_announcements.php',
        'name' => get_lang('SystemAnnouncements'),
    ];
    if ($action == 'add') {
        $interbreadcrumb[] = [
            'url' => '#',
            'name' => get_lang('AddAnnouncement'),
        ];
    }
    if ($action == 'edit') {
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Edit')];
    }
} else {
    $tool_name = get_lang('SystemAnnouncements');
}
$url = api_get_path(WEB_AJAX_PATH).'career.ajax.php';

// Displaying the header.
Display::display_header($tool_name);
if ($action != 'add' && $action != 'edit') {
    echo '<div class="actions">';
    echo '<a href="?action=add">'.Display::return_icon('add.png', get_lang('AddAnnouncement'), [], 32).'</a>';
    echo '</div>';
}

/* MAIN CODE */
$show_announcement_list = true;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$type = $_REQUEST['type'] ?? null;

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
        echo Display::return_message(get_lang('Updated'), 'confirmation');
        break;
    case 'delete':
        // Delete an announcement.
        SystemAnnouncementManager::delete_announcement($_GET['id']);
        echo Display::return_message(get_lang('AnnouncementDeleted'), 'confirmation');
        break;
    case 'delete_selected':
        foreach ($_POST['id'] as $index => $id) {
            SystemAnnouncementManager::delete_announcement($id);
        }
        echo Display::return_message(get_lang('AnnouncementDeleted'), 'confirmation');
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
        if ($allowCareers) {
            $values['career_id'] = $announcement->career_id;
            $values['promotion_id'] = $announcement->promotion_id;
        }

        $values['lang'] = $announcement->lang;
        $values['action'] = 'edit';
        $groups = SystemAnnouncementManager::get_announcement_groups($announcement->id);
        if (!empty($groups)) {
            $values['groups'] = array_column($groups, 'group_id');
        }
        $action_todo = true;
        break;
}

if ($action_todo) {
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'add') {
        $form_title = get_lang('AddNews');
        $url = api_get_self();
    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit') {
        $form_title = get_lang('EditNews');
        $url = api_get_self().'?id='.intval($_GET['id']);
    }
    $form = new FormValidator('system_announcement', 'post', $url);

    if ('add' === $action && 'zoom_conference' == $type && $meetingId = $_REQUEST['meeting'] ?? 0) {
        $plugin = ZoomPlugin::create();

        if ($plugin->isEnabled(true)) {
            /** @var Meeting $meeting */
            $meeting = ZoomPlugin::getMeetingRepository()->findOneBy(['meetingId' => $meetingId]);
            $meetingUrl = api_get_path(WEB_PLUGIN_PATH).'zoom/subscription.php?meetingId='.$meeting->getMeetingId();

            $endDate = new DateTime($meeting->formattedStartTime);
            $endDate->add($meeting->durationInterval);

            $values['title'] = $meeting->getTopic();
            $values['content'] = '<p>'.$meeting->getAgenda().'</p>'
                .'<p>'.$plugin->get_lang('UrlForSelfRegistration').'<br>'.Display::url($meetingUrl, $meetingUrl).'</p>';
            $values['range_start'] = $meeting->formattedStartTime;
            $values['range_end'] = $endDate->format('Y-m-d H:i');
            $values['range'] = "{$values['range_start']} / {$values['range_end']}";
            $values['send_mail'] = true;
            $values['add_to_calendar'] = true;

            $form->addHidden('type', 'zoom_conference');
            $form->addHidden('meeting', $meeting->getMeetingId());
        }
    }

    $form->addHeader($form_title);
    $form->addText('title', get_lang('Title'), true);
    $form->applyFilter('title', 'html_filter');

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
    $form->addDateRangePicker(
        'range',
        get_lang('StartTimeWindow'),
        true,
        ['id' => 'range']
    );

    if ($allowCareers) {
        Career::addCareerFieldsToForm($form, $values ?? []);
    }

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
        $form->addSelect(
            'groups',
            get_lang('AnnouncementForGroup'),
            $group_list,
            ['multiple' => 'multiple']
        );
    }

    $values['groups'] = isset($values['groups']) ? $values['groups'] : [];
    $form->addElement('checkbox', 'send_mail', null, get_lang('SendMail'));

    if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'add') {
        $form->addElement('checkbox', 'add_to_calendar', null, get_lang('AddToCalendar'));
        $text = get_lang('AddNews');
        $class = 'add';
        $form->addElement('hidden', 'action', 'add');
    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] === 'edit') {
        $text = get_lang('EditNews');
        $class = 'save';
        $form->addElement('hidden', 'action', 'edit');
    }
    $form->addElement('checkbox', 'send_email_test', null, get_lang('SendOnlyAnEmailToMySelfToTest'));
    $form->addButtonSend($text, 'submit');
    $form->setDefaults($values);

    if ($form->validate()) {
        $values = $form->getSubmitValues();
        $visibilityResult = [];
        foreach ($visibleList as $key => $value) {
            if (!isset($values[$key])) {
                $values[$key] = false;
            }
            $visibilityResult[$key] = $values[$key];
        }

        if ($values['lang'] === 'all') {
            $values['lang'] = null;
        }

        $sendMail = isset($values['send_mail']) ? $values['send_mail'] : null;
        $groupsToSend = $values['groups'] ?? [];

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
                    empty($values['send_email_test']) ? false : true,
                    isset($values['career_id']) ? $values['career_id'] : 0,
                    isset($values['promotion_id']) ? $values['promotion_id'] : 0,
                    $groupsToSend
                );

                if ($announcement_id !== false) {
                    if (!empty($groupsToSend)) {
                        SystemAnnouncementManager::announcement_for_groups($announcement_id, $groupsToSend);
                    }

                    if (isset($meeting)) {
                        $em = Database::getManager();
                        $sysAnnouncement = $em->find(SysAnnouncement::class, $announcement_id);
                        $meeting->setSysAnnouncement($sysAnnouncement);
                        $em->flush();
                    }

                    echo Display::return_message(get_lang('AnnouncementAdded'), 'confirmation');
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
                    $sendMailTest,
                    isset($values['career_id']) ? $values['career_id'] : 0,
                    isset($values['promotion_id']) ? $values['promotion_id'] : 0,
                    $groupsToSend
                )) {
                    if (!empty($groupsToSend)) {
                        SystemAnnouncementManager::announcement_for_groups($values['id'], $groupsToSend);
                        echo Display::return_message(
                            get_lang('AnnouncementUpdated'),
                            'confirmation'
                        );
                    } else {
                        // Delete groups
                        SystemAnnouncementManager::announcement_for_groups($values['id'], []);
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
    $announcements = SystemAnnouncementManager::get_all_announcements();
    $announcement_data = [];
    foreach ($announcements as $index => $announcement) {
        $row = [];
        $row[] = $announcement->id;
        $row[] = Display::return_icon(($announcement->visible ? 'accept.png' : 'exclamation.png'), ($announcement->visible ? get_lang('AnnouncementAvailable') : get_lang('AnnouncementNotAvailable')));
        $row[] = $announcement->title;
        $row[] = $announcement->date_start;
        $row[] = $announcement->date_end;

        $data = (array) $announcement;
        foreach ($visibleList as $key => $value) {
            $value = $data[$key];
            $action = $value ? 'make_invisible' : 'make_visible';
            $row[] = "<a href=\"?id=".$announcement->id."&person=".$key."&action=".$action."\">".
                Display::return_icon(($value ? 'eyes.png' : 'eyes-close.png'), get_lang('ShowOrHide'))."</a>";
        }
        /*$row[] = "<a href=\"?id=".$announcement->id."&person=".SystemAnnouncementManager::VISIBLE_TEACHER."&action=".($announcement->visible_teacher ? 'make_invisible' : 'make_visible')."\">".Display::return_icon(($announcement->visible_teacher ? 'eyes.png' : 'eyes-close.png'), get_lang('ShowOrHide'))."</a>";
        $row[] = "<a href=\"?id=".$announcement->id."&person=".SystemAnnouncementManager::VISIBLE_STUDENT."&action=".($announcement->visible_student ? 'make_invisible' : 'make_visible')."\">".Display::return_icon(($announcement->visible_student ? 'eyes.png' : 'eyes-close.png'), get_lang('ShowOrHide'))."</a>";
        $row[] = "<a href=\"?id=".$announcement->id."&person=".SystemAnnouncementManager::VISIBLE_GUEST."&action=".($announcement->visible_guest ? 'make_invisible' : 'make_visible')."\">".Display::return_icon(($announcement->visible_guest ? 'eyes.png' : 'eyes-close.png'), get_lang('ShowOrHide'))."</a>";*/

        $row[] = $announcement->lang;
        $row[] = "<a href=\"?action=edit&id=".$announcement->id."\">".Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL)."</a> <a href=\"?action=delete&id=".$announcement->id."\"  onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."')) return false;\">".Display::return_icon('delete.png', get_lang('Delete'), [], ICON_SIZE_SMALL)."</a>";
        $announcement_data[] = $row;
    }
    $table = new SortableTableFromArray($announcement_data);
    $table->set_header(0, '', false, 'width="20px"');
    $table->set_header(1, get_lang('Active'));
    $table->set_header(2, get_lang('Title'));
    $table->set_header(3, get_lang('StartTimeWindow'));
    $table->set_column_filter(3, function ($data) {
        return api_convert_and_format_date($data);
    });
    $table->set_column_filter(4, function ($data) {
        return api_convert_and_format_date($data);
    });
    $table->set_header(4, get_lang('EndTimeWindow'));

    $count = 5;
    foreach ($visibleList as $key => $title) {
        $table->set_header($count, $title);
        $count++;
    }

    $table->set_header($count++, get_lang('Language'));
    $table->set_header($count++, get_lang('Modify'), false, 'width="50px"');
    $form_actions = [];
    $form_actions['delete_selected'] = get_lang('Delete');
    $table->set_form_actions($form_actions);
    $table->display();
}

Display::display_footer();
