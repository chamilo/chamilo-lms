<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CAnnouncement;

/**
 * @author Frederik Vermeire <frederik.vermeire@pandora.be>, UGent Internship
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University: code cleaning
 * @author Julio Montoya <gugli100@gmail.com>, MORE code cleaning 2011
 *
 * @abstract The task of the internship was to integrate the 'send messages to specific users' with the
 *             Announcements tool and also add the resource linker here. The database also needed refactoring
 *             as there was no title field (the title was merged into the content field)
 */
$use_anonymous = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);
api_protect_course_group(GroupManager::GROUP_TOOL_ANNOUNCEMENT);

$token = Security::get_existing_token();

$courseId = api_get_course_int_id();
$_course = api_get_course_info_by_id($courseId);
$group_id = api_get_group_id();
$sessionId = api_get_session_id();
$current_course_tool = TOOL_ANNOUNCEMENT;
$this_section = SECTION_COURSES;
$nameTools = get_lang('Announcements');
$repo = Container::getAnnouncementRepository();

$allowToEdit = (
    api_is_allowed_to_edit(false, true) ||
    (1 === (int) api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous()) ||
    ($sessionId && api_is_coach() && ('true' === api_get_setting('announcement.allow_coach_to_edit_announcements')))
);
$allowStudentInGroupToSend = false;

$drhHasAccessToSessionContent = api_drh_can_access_all_session_content();
if (!empty($sessionId) && $drhHasAccessToSessionContent) {
    $allowToEdit = $allowToEdit || api_is_drh();
}

// Database Table Definitions
$tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);

$isTutor = false;
if (!empty($group_id)) {
    $groupEntity = api_get_group_entity($group_id);
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('Group area').' '.$groupEntity->getName(),
    ];

    if (false === $allowToEdit) {
        // Check if user is tutor group
        $isTutor = $groupEntity->hasTutor(api_get_user_entity());
        if ($isTutor) {
            $allowToEdit = true;
        }
        // Last chance ... students can send announcements
        if (GroupManager::TOOL_PRIVATE_BETWEEN_USERS == $groupEntity->getAnnouncementsState()) {
            $allowStudentInGroupToSend = true;
        }
    }
}

Event::event_access_tool(TOOL_ANNOUNCEMENT);

$announcement_id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : 'list';
$announcement_number = AnnouncementManager::getNumberAnnouncements();

$homeUrl = api_get_self().'?action=list&'.api_get_cidreq();
$content = '';
$searchFormToString = '';

$logInfo = [
    'tool' => TOOL_ANNOUNCEMENT,
    'action' => $action,
];
Event::registerLog($logInfo);

$announcementAttachmentIsDisabled = ('true' === api_get_setting('announcement.disable_announcement_attachment'));
$thisAnnouncementId = null;

switch ($action) {
    case 'move':
        throw new Exception('@todo move');
        if (!$allowToEdit) {
            api_not_allowed(true);
        }

        /* Move announcement up/down */
        if (!empty($_GET['down'])) {
            $thisAnnouncementId = (int) ($_GET['down']);
            $sortDirection = 'DESC';
        }
        /*
        if (!empty($_GET['up'])) {
            $thisAnnouncementId = (int) ($_GET['up']);
            $sortDirection = 'ASC';
        }

        if (!empty($sortDirection)) {
            if (!in_array(trim(strtoupper($sortDirection)), ['ASC', 'DESC'])) {
                $sortDirection = 'ASC';
            }

            $sql = "SELECT DISTINCT announcement.id, announcement.display_order
                    FROM $tbl_announcement announcement
                    INNER JOIN $tbl_item_property itemproperty
                    ON (announcement.c_id = itemproperty.c_id)
                    WHERE
                        announcement.c_id = $courseId AND
                        itemproperty.c_id = $courseId AND
                        itemproperty.ref = announcement.id AND
                        itemproperty.tool = '".TOOL_ANNOUNCEMENT."'  AND
                        itemproperty.visibility <> 2
                    ORDER BY display_order $sortDirection";
            $result = Database::query($sql);
            $thisAnnouncementOrderFound = false;
            $thisAnnouncementOrder = null;

            while (list($announcementId, $announcementOrder) = Database::fetch_row($result)) {
                if ($thisAnnouncementOrderFound) {
                    $nextAnnouncementId = $announcementId;
                    $nextAnnouncementOrder = $announcementOrder;
                    $sql = "UPDATE $tbl_announcement SET display_order = '$nextAnnouncementOrder'
                            WHERE c_id = $courseId AND id = $thisAnnouncementId";
                    Database::query($sql);
                    $sql = "UPDATE $tbl_announcement  SET display_order = '$thisAnnouncementOrder'
                            WHERE c_id = $courseId AND id =  $nextAnnouncementId";
                    Database::query($sql);
                    break;
                }
                // STEP 1 : FIND THE ORDER OF THE ANNOUNCEMENT
                if ($announcementId == $thisAnnouncementId) {
                    $thisAnnouncementOrder = $announcementOrder;
                    $thisAnnouncementOrderFound = true;
                }
            }
            Display::addFlash(Display::return_message(get_lang('The announcement has been moved')));
            header('Location: '.$homeUrl);
            exit;
        }*/

        break;
    case 'view':
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'announcements/announcements.php?'.api_get_cidreq(),
            'name' => $nameTools,
        ];
        $nameTools = get_lang('View');
        $content = AnnouncementManager::displayAnnouncement($announcement_id);
        if (empty($content)) {
            api_not_allowed(true);
        }

        break;
    case 'list':
        $htmlHeadXtra[] = api_get_jqgrid_js();

        $searchForm = new FormValidator(
            'search_simple',
            'post',
            api_get_self().'?'.api_get_cidreq(),
            '',
            [],
            FormValidator::LAYOUT_INLINE
        );

        $searchForm->addElement('text', 'keyword', get_lang('Title'));
        $users = CourseManager::get_user_list_from_course_code(api_get_course_id(), $sessionId);
        $userList = ['' => ''];
        if (!empty($users)) {
            foreach ($users as $user) {
                $userList[$user['user_id']] = api_get_person_name($user['firstname'], $user['lastname']);
            }
        }
        $users = [];
        $searchForm->addSelect('user_id', get_lang('Users'), $userList);
        $searchForm->addButtonSearch(get_lang('Search'));

        $filterData = [];
        $keyword = '';
        $userIdToSearch = 0;

        if ($searchForm->validate()) {
            $filterData = $searchForm->getSubmitValues();
            $keyword = $filterData['keyword'];
            $userIdToSearch = $filterData['user_id'];
        }

        // jqgrid will use this URL to do the selects
        $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_course_announcements&'.api_get_cidreq().'&title_to_search='.$keyword.'&user_id_to_search='.$userIdToSearch;
        $deleteUrl = api_get_path(WEB_AJAX_PATH).'announcement.ajax.php?a=delete_item&'.api_get_cidreq();
        $columns = [
            get_lang('Title'),
            get_lang('By'),
            get_lang('Latest update'),
            get_lang('Detail'),
        ];

        // Column config
        $columnModel = [
            [
                'name' => 'title',
                'index' => 'title',
                'width' => '300',
                'align' => 'left',
                'sortable' => 'false',
            ],
            [
                'name' => 'username',
                'index' => 'username',
                'width' => '100',
                'align' => 'left',
                'sortable' => 'false',
            ],
            [
                'name' => 'lastedit_date',
                'index' => 'lastedit_date',
                'width' => '200',
                'align' => 'left',
                'sortable' => 'false',
            ],
            [
                'name' => 'actions',
                'index' => 'actions',
                'width' => '150',
                'align' => 'left',
                'sortable' => 'false',
            ],
        ];

        // Autowidth
        $extra_params['autowidth'] = 'true';
        // height auto
        $extra_params['height'] = 'auto';
        $editOptions = '';

        if ($isTutor || api_is_allowed_to_edit()) {
            $extra_params['multiselect'] = true;
            $editOptions = '
            $("#announcements").jqGrid(
                "navGrid",
                "#announcements_pager",
                { edit: false, add: false, del: true },
                { height:280, reloadAfterSubmit:false }, // edit options
                { height:280, reloadAfterSubmit:false }, // add options
                { reloadAfterSubmit:false, url: "'.$deleteUrl.'" }, // del options
                { width:500 } // search options
            );
            ';
        }

        $content = '<script>
        $(function() {'.
            Display::grid_js(
                'announcements',
                $url,
                $columns,
                $columnModel,
                $extra_params,
                [],
                '',
                true
            ).$editOptions.'
        });
        </script>';

        $count = AnnouncementManager::getAnnouncements(
            $token,
            $announcement_number,
            true
        );

        if (empty($count)) {
            $html = '';
            if (($allowToEdit || $allowStudentInGroupToSend) &&
                (empty($_GET['origin']) || 'learnpath' !== $_GET['origin'])
            ) {
                $html .= Display::noDataView(
                    get_lang('Announcements'),
                    Display::return_icon('valves.png', '', [], 64),
                    get_lang('Add an announcement'),
                    api_get_self().'?'.api_get_cidreq().'&action=add'
                );
            } else {
                $html = Display::return_message(get_lang('There are no announcements.'), 'warning');
            }
            $content = $html;
        } else {
            $content .= Display::grid_html('announcements');
        }

        break;
    case 'delete':
        /* Delete announcement */
        $id = (int) $_GET['id'];
        if (0 != $sessionId && false == api_is_allowed_to_session_edit(false, true)) {
            api_not_allowed();
        }
        $delete = false;
        if (api_is_platform_admin()) {
            $delete = true;
        }

        if (!api_is_session_general_coach() || api_is_element_in_the_session(TOOL_ANNOUNCEMENT, $id)) {
            $delete = true;
        }

        if ($delete) {
            AnnouncementManager::delete_announcement($_course, $id);
            Display::addFlash(Display::return_message(get_lang('Announcement has been deleted')));
        }
        header('Location: '.$homeUrl);
        exit;

        break;
    case 'delete_all':
        if (api_is_allowed_to_edit()) {
            $allow = ('true' === api_get_setting('announcement.disable_delete_all_announcements'));
            if (false === $allow) {
                AnnouncementManager::delete_all_announcements($_course);
                Display::addFlash(Display::return_message(get_lang('Announcement has been deletedAll')));
            }
            header('Location: '.$homeUrl);
            exit;
        }

        break;
    case 'delete_attachment':
        $id = (int) $_GET['id_attach'];

        if (api_is_allowed_to_edit()) {
            AnnouncementManager::delete_announcement_attachment_file($id);
        }

        header('Location: '.$homeUrl);
        exit;

        break;
    case 'set_visibility':
        if (!empty($announcement_id)) {
            if (0 != $sessionId &&
                false == api_is_allowed_to_session_edit(false, true)
            ) {
                api_not_allowed();
            }

            $status = isset($_GET['status']) ? $_GET['status'] : null;
            if (!$allowToEdit) {
                api_not_allowed(true);
            }

            if (!api_is_session_general_coach() ||
                api_is_element_in_the_session(TOOL_ANNOUNCEMENT, $announcement_id)
            ) {
                AnnouncementManager::change_visibility_announcement(
                    $_course,
                    $announcement_id,
                    $status
                );
                Display::addFlash(Display::return_message(get_lang('The visibility has been changed.')));
                header('Location: '.$homeUrl);
                exit;
            }
        }
        break;
    case 'add':
    case 'modify':
        if (0 != $sessionId &&
            false == api_is_allowed_to_session_edit(false, true)
        ) {
            api_not_allowed(true);
        }

        if (false === $allowStudentInGroupToSend) {
            if (!$allowToEdit) {
                api_not_allowed(true);
            }
        }

        // DISPLAY ADD ANNOUNCEMENT COMMAND
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $url = api_get_self().'?action='.$action.'&id='.$id.'&'.api_get_cidreq();

        $form = new FormValidator(
            'announcement',
            'post',
            $url,
            null,
            ['enctype' => 'multipart/form-data']
        );

        $form_name = get_lang('Edit announcement');
        if (empty($id)) {
            $form_name = get_lang('Add an announcement');
        }
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'announcements/announcements.php?'.api_get_cidreq(),
            'name' => $nameTools,
        ];

        $nameTools = $form_name;
        $form->addHeader($form_name);
        $form->addButtonAdvancedSettings(
            'choose_recipients',
            [get_lang('Choose recipients')]
        );
        $form->addHtml('<div id="choose_recipients_options" style="display:none;">');

        $to = [];
        if (empty($group_id)) {
            if (isset($_GET['remind_inactive'])) {
                $email_ann = '1';
                $content_to_modify = sprintf(
                    get_lang('Dear user,<br /><br /> you are not active on %s since more than %s days.'),
                    api_get_setting('siteName'),
                    7
                );
                $title_to_modify = sprintf(
                    get_lang('Inactivity on %s'),
                    api_get_setting('siteName')
                );
            } elseif (isset($_GET['remindallinactives']) && 'true' === $_GET['remindallinactives']) {
                // we want to remind inactive users. The $_GET['since'] parameter
                // determines which users have to be warned (i.e the users who have been inactive for x days or more
                $since = 6;
                if (isset($_GET['since'])) {
                    if ('never' === $_GET['since']) {
                        $since = 'never';
                    } else {
                        $since = (int) $_GET['since'];
                    }
                }

                // Getting the users who have to be reminded
                $to = Tracking::getInactiveStudentsInCourse(
                    api_get_course_int_id(),
                    $since,
                    $sessionId
                );

                // setting the variables for the form elements: the users who need to receive the message
                foreach ($to as &$user) {
                    $user = 'USER:'.$user;
                }
                // setting the variables for the form elements: the message has to be sent by email
                $email_ann = '1';
                // setting the variables for the form elements: the title of the email
                $title_to_modify = sprintf(
                    get_lang('Inactivity on %s'),
                    api_get_setting('siteName')
                );
                // setting the variables for the form elements: the message of the email
                $content_to_modify = sprintf(
                    get_lang('Dear user,<br /><br /> you are not active on %s since more than %s days.'),
                    api_get_setting('siteName'),
                    $since
                );
                // when we want to remind the users who have never been active
                // then we have a different subject and content for the announcement
                if ('never' === $_GET['since']) {
                    $title_to_modify = sprintf(
                        get_lang('Inactivity on %s'),
                        api_get_setting('siteName')
                    );
                    $content_to_modify = get_lang(
                        'YourAccountIsActiveYouCanLoginAndCheckYourCourses'
                    );
                }
            }
            $element = CourseManager::addUserGroupMultiSelect($form, []);
        } else {
            $element = CourseManager::addGroupMultiSelect($form, $groupEntity, []);
        }

        $form->addHtml('</div>');
        $form->addCheckBox('email_ann', '', get_lang('Send this announcement by email to selected groups/users'));

        if (!isset($announcement_to_modify)) {
            $announcement_to_modify = '';
        }

        $announcementInfo = null;
        if (!empty($id)) {
            /** @var CAnnouncement $announcementInfo */
            $announcementInfo = $repo->find($id);
        }

        $showSubmitButton = true;
        if (!empty($announcementInfo)) {
            $to = AnnouncementManager::loadEditUsers($announcementInfo);

            if (!empty($group_id)) {
                $separated = AbstractResource::separateUsersGroups($to);
                if (isset($separated['groups']) && count($separated['groups']) > 1) {
                    $form->freeze();
                    Display::addFlash(Display::return_message(get_lang('Disabled by trainer')));
                    $showSubmitButton = false;
                }
            }

            $defaults = [
                'title' => $announcementInfo->getTitle(),
                'content' => $announcementInfo->getContent(),
                'id' => $announcementInfo->getIid(),
                'users' => $to,
            ];
        } else {
            $defaults = [];
            if (!empty($to)) {
                $defaults['users'] = $to;
            }
        }

        $ajaxUrl = api_get_path(WEB_AJAX_PATH).'announcement.ajax.php?'.api_get_cidreq().'&a=preview';

        $form->addHtml("
            <script>
                $(function () {
                    $('#announcement_preview').on('click', function() {
                        var users = [];
                        $('#users_to option').each(function() {
                            users.push($(this).val());
                        });

                        var form = $('#announcement').serialize();
                        $.ajax({
                            type: 'POST',
                            dataType: 'json',
                            url: '".$ajaxUrl."',
                            data: {users : JSON.stringify(users), form: form},
                            beforeSend: function() {
                                $('#announcement_preview_result').html('<i class=\"fa fa-spinner\"></i>');
                                $('#send_button').hide();
                            },
                            success: function(result) {
                                var resultToString = '';
                                $.each(result, function(index, value) {
                                    resultToString += '&nbsp;' + value;
                                });
                                $('#announcement_preview_result').html('' +
                                    '".addslashes(get_lang('Announcement will be sent to'))."<br/>' + resultToString
                                );
                                $('#announcement_preview_result').show();
                                $('#send_button').show();
                            }
                        });
                    });
                });
            </script>
        ");

        if (isset($defaults['users'])) {
            foreach ($defaults['users'] as $value) {
                $parts = explode(':', $value);
                if (!isset($parts[1]) || empty($parts[1])) {
                    continue;
                }
                $form->addHtml(
                    "
                    <script>
                        $(function () {
                            $('#choose_recipients').click();
                        });
                    </script>
                ");

                break;
            }
        }

        $defaults['email_ann'] = true;
        $form->addText(
            'title',
            get_lang('Subject'),
            ['onkeypress' => 'return event.keyCode != 13;']
        );
        $form->addRule('title', get_lang('Required field'), 'required');
        $form->addElement('hidden', 'id');
        $htmlTags = '';
        $tags = AnnouncementManager::getTags();
        foreach ($tags as $tag) {
            $htmlTags .= "<b>$tag</b><br />";
        }
        $form->addButtonAdvancedSettings('tags', get_lang('Tags'));
        $form->addElement('html', '<div id="tags_options" style="display:none">');
        $form->addLabel('', Display::return_message($htmlTags, 'normal', false));
        $form->addElement('html', '</div>');
        $form->addHtmlEditor(
            'content',
            get_lang('Description'),
            true,
            false,
            ['ToolbarSet' => 'Announcements']
        );

        if (!$announcementAttachmentIsDisabled) {
            $form->addElement('file', 'user_upload', get_lang('Add attachment'));
            $form->addElement('textarea', 'file_comment', get_lang('File comment'));
        }

        $form->addHidden('sec_token', $token);

        if (empty($sessionId)) {
            $form->addCheckBox(
                'send_to_users_in_session',
                null,
                get_lang('Send to users in all sessions of this course')
            );
        }

        $config = api_get_configuration_value('announcement.hide_send_to_hrm_users');

        if (false === $config) {
            $form->addCheckBox(
                'send_to_hrm_users',
                null,
                get_lang('Send a copy to HR managers of selected students'),
                ['id' => 'send_to_hrm_users']
            );
        }

        $form->addCheckBox('send_me_a_copy_by_email', null, get_lang('Send a copy by email to myself.'));
        $defaults['send_me_a_copy_by_email'] = true;

        if (empty($id)) {
            $form->addButtonAdvancedSettings(
                'add_event',
                get_lang('Add event in course calendar')
            );
            $form->addHtml('<div id="add_event_options" style="display:none;">');
            $form->addDateTimePicker('event_date_start', get_lang('Date start'));
            $form->addDateTimePicker('event_date_end', get_lang('Date end'));
            $form->addHtml('</div>');
        }

        if ($showSubmitButton) {
            $form->addLabel('',
                Display::url(
                    get_lang('Preview'),
                    'javascript:void(0)',
                    ['class' => 'btn btn--plain', 'id' => 'announcement_preview']
                ).'<div id="announcement_preview_result" style="display:none"></div>'
            );
            $form->addHtml('<div id="send_button" style="display:none">');
            $form->addButtonSave(get_lang('Send announcement'));
            $form->addHtml('</div>');
        }
        $form->setDefaults($defaults);

        if ($form->validate()) {
            $data = $form->getSubmitValues();
            $data['users'] = $data['users'] ?? [];
            $sendToUsersInSession = isset($data['send_to_users_in_session']);
            $sendMeCopy = isset($data['send_me_a_copy_by_email']);
            if (isset($id) && $id) {
                // there is an Id => the announcement already exists => update mode
                $file_comment = $announcementAttachmentIsDisabled ? null : $_POST['file_comment'];
                $file = $announcementAttachmentIsDisabled ? [] : $_FILES['user_upload'];
                $announcement = AnnouncementManager::edit_announcement(
                    $id,
                    $data['title'],
                    $data['content'],
                    $data['users'],
                    $file,
                    $file_comment,
                    $sendToUsersInSession
                );

                // Send mail
                $messageSentTo = [];
                if (isset($_POST['email_ann']) && empty($_POST['onlyThoseMails'])) {
                    $messageSentTo = AnnouncementManager::sendEmail(
                        api_get_course_info(),
                        api_get_session_id(),
                        $announcement,
                        $sendToUsersInSession,
                        isset($data['send_to_hrm_users'])
                    );
                }

                if ($sendMeCopy && !in_array(api_get_user_id(), $messageSentTo)) {
                    $email = new AnnouncementEmail(api_get_course_info(), api_get_session_id(), $announcement);
                    $email->sendAnnouncementEmailToMySelf();
                }

                Display::addFlash(
                    Display::return_message(
                        get_lang('Announcement has been modified'),
                        'success'
                    )
                );
                Security::clear_token();
                header('Location: '.$homeUrl);
                exit;
            } else {
                // Insert mode
                $file = $_FILES['user_upload'];
                $file_comment = $data['file_comment'];

                if (empty($group_id)) {
                    $announcement = AnnouncementManager::add_announcement(
                        api_get_course_info(),
                        api_get_session_id(),
                        $data['title'],
                        $data['content'],
                        $data['users'],
                        $file,
                        $file_comment,
                        null,
                        $sendToUsersInSession
                    );
                } else {
                    $announcement = AnnouncementManager::addGroupAnnouncement(
                        $data['title'],
                        $data['content'],
                        $group_id,
                        $data['users'],
                        $file,
                        $file_comment,
                        $sendToUsersInSession
                    );
                }

                if ($announcement) {
                    if (!empty($data['event_date_start']) && !empty($data['event_date_end'])) {
                        Container::getCalendarEventRepository()
                            ->createFromAnnouncement(
                                $announcement,
                                api_get_utc_datetime($data['event_date_start'], true, true),
                                api_get_utc_datetime($data['event_date_end'], true, true),
                                $data['users'],
                                api_get_course_entity(),
                                api_get_session_entity(),
                                api_get_group_entity()
                            );
                    }

                    Display::addFlash(
                        Display::return_message(
                            get_lang('Announcement has been added'),
                            'success'
                        )
                    );

                    // Send mail
                    $messageSentTo = [];
                    if (isset($data['email_ann']) && $data['email_ann']) {
                        $messageSentTo = AnnouncementManager::sendEmail(
                            api_get_course_info(),
                            api_get_session_id(),
                            $announcement,
                            $sendToUsersInSession
                        );
                    }

                    if ($sendMeCopy && !in_array(api_get_user_id(), $messageSentTo)) {
                        $email = new AnnouncementEmail(api_get_course_info(), api_get_session_id(), $announcement);
                        $email->sendAnnouncementEmailToMySelf();
                    }

                    Security::clear_token();
                    header('Location: '.$homeUrl);
                    exit;
                }
                api_not_allowed(true);
            }
        }
        $content = $form->returnForm();

        break;
}

if (!empty($_GET['remind_inactive'])) {
    $to[] = 'USER:'.(int) ($_GET['remind_inactive']);
}

if (empty($_GET['origin']) || 'learnpath' !== $_GET['origin']) {
    // We are not in the learning path
    Display::display_header($nameTools, get_lang('Announcements'));
}

// Tool introduction
if (empty($_GET['origin']) || 'learnpath' !== $_GET['origin']) {
    Display::display_introduction_section(TOOL_ANNOUNCEMENT);
}

// Actions
$show_actions = false;
$actionsLeft = '';
if (($allowToEdit || $allowStudentInGroupToSend) && (empty($_GET['origin']) || 'learnpath' !== $_GET['origin'])) {
    if (in_array($action, ['add', 'modify', 'view'])) {
        $actionsLeft .= "<a href='".api_get_self().'?'.api_get_cidreq()."'>".
            Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM).
            '</a>';
    } else {
        $actionsLeft .= "<a href='".api_get_self().'?'.api_get_cidreq()."&action=add'>".
            Display::return_icon('new_announce.png', get_lang('Add an announcement'), '', ICON_SIZE_MEDIUM).
            '</a>';
    }
    $show_actions = true;
} else {
    if (in_array($action, ['view'])) {
        $actionsLeft .= "<a href='".api_get_self().'?'.api_get_cidreq()."'>".
            Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM).'</a>';
    }
}

/*
if ($allowToEdit && 0 == api_get_group_id()) {
    $allow = ('true' === api_get_setting('announcement.disable_delete_all_announcements'));
    if (false === $allow && api_is_allowed_to_edit()) {
        if (!isset($_GET['action']) ||
            isset($_GET['action']) && 'list' == $_GET['action']
        ) {
            $actionsLeft .= '<a href="'.api_get_self().'?'.api_get_cidreq()."&action=delete_all\" onclick=\"javascript:if(!confirm('".get_lang('Please confirm your choice')."')) return false;\">".
                Display::return_icon(
                    'delete_announce.png',
                    get_lang('Clear list of announcements'),
                    '',
                    ICON_SIZE_MEDIUM
                ).'</a>';
        }
    }
}*/

if ($show_actions) {
    echo Display::toolbarAction('toolbar', [$actionsLeft, $searchFormToString]);
}

echo $content;

if (empty($_GET['origin']) || 'learnpath' !== $_GET['origin']) {
    //we are not in learnpath tool
    Display::display_footer();
}
