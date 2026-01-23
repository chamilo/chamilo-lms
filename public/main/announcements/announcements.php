<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CGroup;

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
$course = api_get_course_entity();
$_course = api_get_course_info_by_id($courseId);
$group_id = api_get_group_id();
$group = api_get_group_entity();
$sessionId = api_get_session_id();
$session = api_get_session_entity();
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
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => $groupEntity->getTitle(),
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

/**
 * Build a local URL for this announcements page.
 * Keeps cidreq and preserves legacy navigation params (origin/page) when present.
 */
function announcements_build_url(string $action, ?int $id = null, array $extra = []): string
{
    $url = api_get_self().'?action='.$action;
    if (null !== $id && $id > 0) {
        $url .= '&id='.(int) $id;
    }
    $url .= '&'.api_get_cidreq();

    // Preserve optional navigation context (legacy learnpath and similar flows).
    if (!empty($_REQUEST['origin'])) {
        $url .= '&origin='.rawurlencode((string) $_REQUEST['origin']);
    }
    if (!empty($_REQUEST['page'])) {
        $url .= '&page='.rawurlencode((string) $_REQUEST['page']);
    }

    foreach ($extra as $key => $value) {
        if (null === $value || '' === $value) {
            continue;
        }
        $url .= '&'.rawurlencode((string) $key).'='.rawurlencode((string) $value);
    }

    return $url;
}

/**
 * Resolve a safe return URL based only on explicit return_action/return_id params.
 * If params are missing/invalid, fallback to the provided URL.
 */
function announcements_get_return_url(string $fallbackUrl): string
{
    $returnAction = (string) ($_REQUEST['return_action'] ?? '');
    $returnId = (int) ($_REQUEST['return_id'] ?? 0);

    if ('list' === $returnAction) {
        return announcements_build_url('list');
    }

    if (in_array($returnAction, ['view', 'modify'], true) && $returnId > 0) {
        return announcements_build_url($returnAction, $returnId);
    }

    return $fallbackUrl;
}

$logInfo = [
    'tool' => TOOL_ANNOUNCEMENT,
    'action' => $action,
];
Event::registerLog($logInfo);

$announcementAttachmentIsDisabled = ('true' === api_get_setting('announcement.disable_announcement_attachment'));
$thisAnnouncementId = null;
$htmlHeadXtra[] = api_get_css_asset('select2/css/select2.min.css');
$htmlHeadXtra[] = api_get_asset('select2/js/select2.min.js');

switch ($action) {
    case 'move':

        if (!$allowToEdit) {
            api_not_allowed(true);
        }

        $em = Database::getManager();
        $repo = Container::getAnnouncementRepository();

        /* Move announcement up/down */
        $thisAnnouncementId = null;
        $sortDirection = null;

        if (!empty($_GET['down'])) {
            $thisAnnouncementId = (int) $_GET['down'];
            $sortDirection = 'down';
        } elseif (!empty($_GET['up'])) {
            $thisAnnouncementId = (int) $_GET['up'];
            $sortDirection = 'up';
        }

        /** @var CAnnouncement $currentAnnouncement */
        $currentAnnouncement = $repo->find($thisAnnouncementId);
        if ($currentAnnouncement) {
            $resourceNode = $currentAnnouncement->getResourceNode();
            $link = $resourceNode->getResourceLinkByContext($course, $session, $group);

            if ($link) {
                if ('down' === $sortDirection) {
                    $link->moveDownPosition();
                } else {
                    $link->moveUpPosition();
                }

                $em->flush();
            }
        }

        header('Location: '.$homeUrl);
        exit;

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

        $gid = (int) ($_GET['gid'] ?? 0);
        $group = null;
        if ($gid > 0) {
            $group = Database::getManager()->getRepository(CGroup::class)->find($gid); // PK = iid
        }
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
                'width' => '400px',
                'align' => 'left',
                'sortable' => 'false',
            ],
            [
                'name' => 'username',
                'index' => 'username',
                'width' => '350px',
                'align' => 'left',
                'sortable' => 'false',
            ],
            [
                'name' => 'lastedit_date',
                'index' => 'lastedit_date',
                'width' => '350px',
                'align' => 'left',
                'sortable' => 'false',
            ],
            [
                'name' => 'actions',
                'index' => 'actions',
                'width' => '150px',
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

        // Safe responsive resize (ES5 only). This avoids blank pages caused by modern JS syntax.
        $resizeJs = '
            (function () {
                function resizeAnnouncementsGrid() {
                    var $grid = $("#announcements");
                    if (!$grid.length) {
                        return;
                    }
                    // jqGrid marks the grid on the DOM node when initialized
                    if (!$grid[0].grid) {
                        return;
                    }
                    var $wrap = $grid.closest(".ui-jqgrid").parent();
                    if ($wrap.length && $wrap.width()) {
                        $grid.jqGrid("setGridWidth", $wrap.width(), true);
                    }
                }

                // Run after init + also after a short delay (layout may still be settling)
                resizeAnnouncementsGrid();
                setTimeout(resizeAnnouncementsGrid, 250);

                // Keep it responsive on window resize
                $(window).off("resize.announcementsGrid").on("resize.announcementsGrid", function () {
                    resizeAnnouncementsGrid();
                });
            })();
        ';

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
            ).$editOptions.$resizeJs.'
        });
        </script>';

        $count = AnnouncementManager::getNumberAnnouncements();

        if (empty($count)) {
            $html = '';
            if (($allowToEdit || $allowStudentInGroupToSend) &&
                (empty($_GET['origin']) || 'learnpath' !== $_GET['origin'])
            ) {
                $html .= Display::noDataView(
                    get_lang('Announcements'),
                    Display::getMdiIcon(ObjectIcon::ANNOUNCEMENT, 'ch-tool-icon', null, ICON_SIZE_BIG, get_lang('Add an announcement')),
                    get_lang('Add an announcement'),
                    api_get_self().'?'.api_get_cidreq().'&action=add'
                );
            } else {
                $html = Display::return_message(get_lang('There are no announcements.'), 'warning');
            }
            $content = $html;
        } else {
            // Use inline style (no Tailwind dependency) to allow horizontal scroll on small screens.
            $content .= '<div style="width:100%; overflow-x:auto;">'.Display::grid_html('announcements').'</div>';
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
    case 'delete_all':
        if (api_is_allowed_to_edit()) {
            $allow = ('true' === api_get_setting('announcement.disable_delete_all_announcements'));
            if (false === $allow) {
                AnnouncementManager::delete_all_announcements($_course);
                Display::addFlash(Display::return_message(get_lang('Announcement has been deleted')));
            }
            header('Location: '.$homeUrl);
            exit;
        }

        break;
    case 'delete_attachment':
        $id = (int) ($_GET['id_attach'] ?? 0);

        if (api_is_allowed_to_edit() && $id > 0) {
            AnnouncementManager::delete_announcement_attachment_file($id);
        }

        $redirectUrl = announcements_get_return_url($homeUrl);
        header('Location: '.$redirectUrl);
        exit;
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
                    $announcement_id,
                    $status,
                    $course,
                    $session
                );
                Display::addFlash(Display::return_message(get_lang('The visibility has been changed.')));

                $redirectUrl = announcements_get_return_url($homeUrl);
                header('Location: '.$redirectUrl);
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

        $url = announcements_build_url(
            $action,
            $id > 0 ? $id : null,
            [
                'return_action' => (string) ($_REQUEST['return_action'] ?? ''),
                'return_id' => (int) ($_REQUEST['return_id'] ?? 0),
            ]
        );

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
            if (!empty($sessionId)) {
                $userGroups = Container::getUsergroupRepository()->findBySession($session);
                $groupSelectTitle = get_lang('Classes of session').' '.$session->getTitle();
            } else {
                $userGroups = Container::getUsergroupRepository()->findByCourse($course);
                $groupSelectTitle = get_lang('Classes of course');
            }

            if (!empty($userGroups)) {
                $groupSelect = ['' => get_lang('Select a class')];
                foreach ($userGroups as $group) {
                    $groupSelect[$group->getId()] = $group->getTitle();
                }
                $form->addSelect('usergroup_id', $groupSelectTitle, $groupSelect, ['id' => 'usergroup_id']);
            }
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
                $since = 6;
                if (isset($_GET['since'])) {
                    if ('never' === $_GET['since']) {
                        $since = 'never';
                    } else {
                        $since = (int) $_GET['since'];
                    }
                }

                $to = Tracking::getInactiveStudentsInCourse(
                    api_get_course_int_id(),
                    $since,
                    $sessionId
                );

                foreach ($to as &$user) {
                    $user = 'USER:'.$user;
                }
                $email_ann = '1';
                $title_to_modify = sprintf(
                    get_lang('Inactivity on %s'),
                    api_get_setting('siteName')
                );
                $content_to_modify = sprintf(
                    get_lang('Dear user,<br /><br /> you are not active on %s since more than %s days.'),
                    api_get_setting('siteName'),
                    $since
                );
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
        $ajaxUserGroupUrl = api_get_path(WEB_AJAX_PATH).'usergroup.ajax.php?'.api_get_cidreq();
        $form->addHtml("
        <script>
            $(function () {
                $('#usergroup_id').on('change', function () {
                    const groupId = $(this).val();
                    const selected = $('#users_to');
                    selected.empty();
                    if (!groupId) return;
                    $.ajax({
                        url: '".$ajaxUserGroupUrl."',
                        type: 'POST',
                        data: {
                            a: 'get_users_by_group_course',
                            group_id: groupId,
                            course_code: '".api_get_course_id()."',
                            session_id: '".api_get_session_id()."'
                        },
                        success: function (response) {
                            const result = JSON.parse(response);
                            for (let user of result) {
                                selected.append(new Option(user.name, 'USER:' + user.id));
                            }
                            $('#announcement_preview_result').html('');
                        }
                    })
                });

                $('#announcement_preview').on('click', function () {
                    const selectedClass = $('#usergroup_id').val();
                    if (selectedClass) {
                        var users = [];
                        var userLabels = [];
                        $('#users_to option').each(function () {
                            users.push($(this).val());
                            userLabels.push($(this).text());
                        });
                        if (users.length === 0) {
                            $('#announcement_preview_result').html('');
                            $('#announcement_preview_result').show();
                            return;
                        }
                        var resultHtml = '<strong>".addslashes(get_lang('Announcement will be sent to'))."</strong><ul>';
                        userLabels.forEach(function (name) {
                            resultHtml += '<li>' + name + '</li>';
                        });
                        resultHtml += '</ul>';
                        $('#announcement_preview_result').html(resultHtml);
                        $('#announcement_preview_result').show();
                        $('#send_button').show();
                    } else {
                        var users = [];
                        var form = $('#announcement').serialize();
                        $('#users_to option').each(function () {
                            users.push($(this).val());
                        });
                        $.ajax({
                            type: 'POST',
                            dataType: 'json',
                            url: '" . $ajaxUrl . "',
                            data: {users: JSON.stringify(users), form: form},
                            beforeSend: function () {
                                $('#announcement_preview_result').html('<i class=\"fa fa-spinner\"></i>');
                                $('#send_button').hide();
                            },
                            success: function (result) {
                                let list = '<ul>';
                                for (let name of result) {
                                    list += '<li>' + name + '</li>';
                                }
                                list += '</ul>';
                                $('#announcement_preview_result').html(
                                    '<strong>".addslashes(get_lang('Announcement will be sent to'))."</strong><br>' + list
                                );
                                $('#announcement_preview_result').show();
                                $('#send_button').show();
                            }
                        });
                    }
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
        // Allow multiple files in one selection
        $form->addElement('file', 'user_upload[]', get_lang('Add attachment'), ['multiple' => 'multiple']);
        $form->addElement('textarea', 'file_comment', get_lang('File comment'));

        // Existing attachments (edit mode)
        if (!empty($announcementInfo)) {
            $attachRepo = Container::getAnnouncementAttachmentRepository();
            $stok = Security::get_existing_token();

            $baseUrl = api_get_self().'?'.api_get_cidreq();
            $returnQs = '&return_action=modify&return_id='.(int) $id;

            $attachments = $announcementInfo->getAttachments();
            if (count($attachments) > 0) {
                $html = '<div class="announcement-attachments" style="margin:20px 0;">';
                $html .= '<strong>'.get_lang('Attachments').'</strong>';
                $html .= '<ul style="margin:6px 0 0 18px;">';

                foreach ($attachments as $attachment) {
                    $downloadUrl = $attachRepo->getResourceFileDownloadUrl($attachment).'?'.api_get_cidreq();
                    $deleteUrl = $baseUrl
                        .'&action=delete_attachment'
                        .'&id_attach='.(int) $attachment->getIid()
                        .$returnQs
                        .'&sec_token='.$stok;

                    $html .= '<li style="margin:4px 0;">';
                    $html .= Display::getMdiIcon(ObjectIcon::ATTACHMENT, 'ch-tool-icon', null, ICON_SIZE_TINY);
                    $html .= ' <a href="'.$downloadUrl.'">'.api_htmlentities($attachment->getFilename()).'</a>';

                    $comment = trim((string) $attachment->getComment());
                    if ('' !== $comment) {
                        $html .= ' - <span class="forum_attach_comment">'.api_htmlentities($comment).'</span>';
                    }

                    $html .= ' '.Display::url(
                            Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_TINY, get_lang('Delete')),
                            $deleteUrl
                        );
                    $html .= '</li>';
                }

                $html .= '</ul></div><br />';
                $form->addElement('html', $html);
            }
        }
    }


    $form->addHidden('sec_token', $token);

        if (empty($sessionId)) {
            $form->addCheckBox(
                'send_to_users_in_session',
                null,
                get_lang('Send to users in all sessions of this course')
            );
        }

        $config = api_get_setting('announcement.announcements_hide_send_to_hrm_users');

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
            $form->addDateTimePicker('event_date_start', get_lang('Start date'));
            $form->addDateTimePicker('event_date_end', get_lang('End date'));

            $form->addHtml('<hr><div id="notification_list"></div>');
            $form
                ->addButton(
                    'add_notification',
                    get_lang('Add reminder'),
                    ActionIcon::ADD_EVENT_REMINDER->value,
                    'plain'
                )
                ->setType('button')
            ;
            $form->addHtml('<hr>');
            $form->addHtml('</div>');
            $htmlHeadXtra[] = '<script>
            $(function () {
              var $list = $("#notification_list");

              $(document).on("click", "#announcement_add_notification", function (e) {
                e.preventDefault();

                var $row = $(`
                  <div class="js-reminder-row flex items-center gap-2 mb-2">
                    <input type="number" min="0" step="1" name="notification_count[]" class="form-control w-24" value="0" />
                    <select name="notification_period[]" class="form-control js-reminder-period w-44">
                      <option value="i">'.addslashes(get_lang('Minutes')).'</option>
                      <option value="h">'.addslashes(get_lang('Hours')).'</option>
                      <option value="d">'.addslashes(get_lang('Days')).'</option>
                      <option value="w">'.addslashes(get_lang('Weeks')).'</option>
                    </select>
                    <button type="button" class="btn btn--danger js-reminder-remove" title="'.addslashes(get_lang('Delete')).'">×</button>
                  </div>
                `);

                $list.append($row);
              });

              $(document).on("click", ".js-reminder-remove", function (e) {
                e.preventDefault();
                $(this).closest(".js-reminder-row").remove();
              });
            });
            </script>';
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

            $notificationCount = $data['notification_count'] ?? [];
            $notificationPeriod = $data['notification_period'] ?? [];

            $reminders = $notificationCount ? array_map(null, $notificationCount, $notificationPeriod) : [];
            if (!empty($id)) {
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
                $redirectUrl = announcements_get_return_url($homeUrl);
                header('Location: '.$redirectUrl);
                exit;
            } else {
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
                                api_get_group_entity(),
                                $reminders
                            );
                    }

                    Display::addFlash(
                        Display::return_message(
                            get_lang('Announcement has been added'),
                            'success'
                        )
                    );

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
    Display::display_header($nameTools, get_lang('Announcements'));
}

if (empty($_GET['origin']) || 'learnpath' !== $_GET['origin']) {
    Display::display_introduction_section(TOOL_ANNOUNCEMENT);
}

$show_actions = false;
$actionsLeft = '';
if (($allowToEdit || $allowStudentInGroupToSend) && (empty($_GET['origin']) || 'learnpath' !== $_GET['origin'])) {
    if (in_array($action, ['add', 'modify', 'view'])) {
        $actionsLeft .= "<a href='".api_get_self().'?'.api_get_cidreq()."'>".
            Display::getMdiIcon('arrow-left-bold-box', 'ch-tool-icon', null, 32, get_lang('Back')).
            '</a>';
    } else {
        $actionsLeft .= "<a href='".api_get_self().'?'.api_get_cidreq()."&action=add'>".
            Display::getMdiIcon('bullhorn', 'ch-tool-icon', null, 32, get_lang('Add an announcement')).
            '</a>';
    }
    $show_actions = true;
} else {
    if (in_array($action, ['view'])) {
        $actionsLeft .= "<a href='".api_get_self().'?'.api_get_cidreq()."'>".
            Display::getMdiIcon('arrow-left-bold-box', 'ch-tool-icon', null, 32, get_lang('Back')).'</a>';
    }
}

if ($show_actions) {
    echo Display::toolbarAction('toolbar', [$actionsLeft, $searchFormToString]);
}

echo $content;

if (empty($_GET['origin']) || 'learnpath' !== $_GET['origin']) {
    Display::display_footer();
}
