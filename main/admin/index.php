<?php

/* For licensing terms, see /license.txt */

/**
 * Index page of the admin tools.
 */
// Resetting the course id.
$cidReset = true;

// Including some necessary chamilo files.
require_once __DIR__.'/../inc/global.inc.php';

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions.
api_protect_admin_script(true);

$nameTools = get_lang('PlatformAdmin');

$accessUrlId = 0;
$adminExtraContentDir = api_get_path(SYS_APP_PATH)."home/admin/";

if (api_is_multiple_url_enabled()) {
    $accessUrlId = api_get_current_access_url_id();

    if ($accessUrlId != -1) {
        $urlInfo = api_get_access_url($accessUrlId);
        $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $urlInfo['url']));
        $cleanUrl = str_replace('/', '-', $url);
        $adminExtraContentDir = api_get_path(SYS_APP_PATH)."home/$cleanUrl/admin/";
    }
}

// Displaying the header
if (api_is_platform_admin()) {
    if (is_dir(api_get_path(SYS_ARCHIVE_PATH)) &&
        !is_writable(api_get_path(SYS_ARCHIVE_PATH))
    ) {
        Display::addFlash(
            Display::return_message(get_lang('ArchivesDirectoryNotWriteableContactAdmin'), 'warning')
        );
    }

    /* ACTION HANDLING */
    if (!empty($_POST['Register'])) {
        api_register_campus(!$_POST['donotlistcampus']);
        $message = Display::return_message(get_lang('VersionCheckEnabled'), 'confirmation');
        Display::addFlash($message);
    }
    $keyword_url = Security::remove_XSS((empty($_GET['keyword']) ? '' : $_GET['keyword']));
}
$blocks = [];

// Instantiate Hook Event for Admin Block
$hook = HookAdminBlock::create();
if (!empty($hook)) {
    // If not empty, then notify Pre process to Hook Observers for Admin Block
    $hook->setEventData(['blocks' => $blocks]);
    $data = $hook->notifyAdminBlock(HOOK_EVENT_TYPE_PRE);
    // Check if blocks data is not null
    if (isset($data['blocks'])) {
        // Get modified blocks
        $blocks = $data['blocks'];
    }
}

/* Users */
$blocks['users']['icon'] = Display::return_icon(
    'members.png',
    get_lang('Users'),
    [],
    ICON_SIZE_MEDIUM,
    false
);
$blocks['users']['label'] = api_ucfirst(get_lang('Users'));
$blocks['users']['class'] = 'block-admin-users';

$usersBlockExtraFile = "{$adminExtraContentDir}block-admin-users_extra.html";

if (file_exists($usersBlockExtraFile)) {
    $blocks['users']['extraContent'] = file_get_contents($usersBlockExtraFile);
}

$search_form = '
    <form method="get" class="form-inline" action="user_list.php">
        <div class="form-group">
            <input class="form-control" type="text" name="keyword" value=""
             aria-label="'.get_lang('Search').'">
            <button class="btn btn-default" type="submit">
                <em class="fa fa-search"></em> '.get_lang('Search').'
            </button>
        </div>
    </form>';
$blocks['users']['search_form'] = $search_form;

if (api_is_platform_admin()) {
    $blocks['users']['editable'] = true;
    $items = [
        [
            'class' => 'item-user-list',
            'url' => 'user_list.php',
            'label' => get_lang('UserList'),
        ],
        [
            'class' => 'item-user-add',
            'url' => 'user_add.php',
            'label' => get_lang('AddUsers'),
        ],
        [
            'class' => 'item-user-export',
            'url' => 'user_export.php',
            'label' => get_lang('ExportUserListXMLCSV'),
        ],
        [
            'class' => 'item-user-import',
            'url' => 'user_import.php',
            'label' => get_lang('ImportUserListXMLCSV'),
        ],
        [
            'class' => 'item-user-import-update',
            'url' => 'user_update_import.php',
            'label' => get_lang('EditUserListCSV'),
        ],
        [
            'class' => 'item-user-import-anonymize',
            'url' => 'user_anonymize_import.php',
            'label' => get_lang('BulkAnonymizeUsers'),
        ],
    ];

    if (isset($extAuthSource) && isset($extAuthSource['extldap']) && count($extAuthSource['extldap']) > 0) {
        $items[] = [
            'class' => 'item-user-ldap-list',
            'url' => 'ldap_users_list.php',
            'label' => get_lang('ImportLDAPUsersIntoPlatform'),
        ];
    }
    $items[] = [
        'class' => 'item-user-field',
        'url' => 'extra_fields.php?type=user',
        'label' => get_lang('ManageUserFields'),
    ];
    $items[] = [
        'class' => 'item-user-groups',
        'url' => 'usergroups.php',
        'label' => get_lang('Classes'),
    ];
    if (api_get_configuration_value('show_link_request_hrm_user')) {
        $items[] = [
            'class' => 'item-user-linking-requests',
            'url' => 'user_linking_requests.php',
            'label' => get_lang('UserLinkingRequests'),
        ];
    }
} else {
    $items = [
        [
            'class' => 'item-user-list',
            'url' => 'user_list.php',
            'label' => get_lang('UserList'),
        ],
        [
            'class' => 'item-user-add',
            'url' => 'user_add.php',
            'label' => get_lang('AddUsers'),
        ],
        [
            'class' => 'item-user-import',
            'url' => 'user_import.php',
            'label' => get_lang('ImportUserListXMLCSV'),
        ],
        [
            'class' => 'item-user-groups',
            'url' => 'usergroups.php',
            'label' => get_lang('Classes'),
        ],
    ];

    if (api_is_session_admin()) {
        if ('true' === api_get_setting('limit_session_admin_role')) {
            $items = array_filter($items, function (array $item) {
                $urls = ['user_list.php', 'user_add.php'];

                return in_array($item['url'], $urls);
            });
        }

        if (true === api_get_configuration_value('limit_session_admin_list_users')) {
            $items = array_filter($items, function (array $item) {
                $urls = ['user_list.php'];

                return !in_array($item['url'], $urls);
            });
        }
    }

    if (api_get_configuration_value('allow_session_admin_extra_access')) {
        $items[] = [
            'class' => 'item-user-import-update',
            'url' => 'user_update_import.php',
            'label' => get_lang('EditUserListCSV'),
        ];
        $items[] = [
            'class' => 'item-user-export',
            'url' => 'user_export.php',
            'label' => get_lang('ExportUserListXMLCSV'),
        ];
    }
}

$blocks['users']['items'] = $items;
$blocks['users']['extra'] = null;

if (api_is_platform_admin()) {
    /* Courses */
    $blocks['courses']['icon'] = Display::return_icon(
        'course.png',
        get_lang('Courses'),
        [],
        ICON_SIZE_MEDIUM,
        false
    );
    $blocks['courses']['label'] = api_ucfirst(get_lang('Courses'));
    $blocks['courses']['class'] = 'block-admin-courses';
    $blocks['courses']['editable'] = true;

    $coursesBlockExtraFile = "{$adminExtraContentDir}block-admin-courses_extra.html";

    if (file_exists($coursesBlockExtraFile)) {
        $blocks['courses']['extraContent'] = file_get_contents($coursesBlockExtraFile);
    }

    $search_form = ' <form method="get" class="form-inline" action="course_list.php">
            <div class="form-group">
                <input class="form-control" type="text" name="keyword" value=""
                 aria-label="'.get_lang('Search').'">
                <button class="btn btn-default" type="submit">
                    <em class="fa fa-search"></em> '.get_lang('Search').'
                </button>
            </div>
        </form>';
    $blocks['courses']['search_form'] = $search_form;

    $items = [];
    $items[] = [
        'class' => 'item-course-list',
        'url' => 'course_list.php',
        'label' => get_lang('CourseList'),
    ];
    $items[] = [
        'class' => 'item-course-add',
        'url' => 'course_add.php',
        'label' => get_lang('AddCourse'),
    ];

    if (api_get_setting('course_validation') == 'true') {
        $items[] = [
            'class' => 'item-course-request',
            'url' => 'course_request_review.php',
            'label' => get_lang('ReviewCourseRequests'),
        ];
        $items[] = [
            'class' => 'item-course-request-accepted',
            'url' => 'course_request_accepted.php',
            'label' => get_lang('AcceptedCourseRequests'),
        ];
        $items[] = [
            'class' => 'item-course-request-rejected',
            'url' => 'course_request_rejected.php',
            'label' => get_lang('RejectedCourseRequests'),
        ];
    }

    $items[] = [
        'class' => 'item-course-export',
        'url' => 'course_export.php',
        'label' => get_lang('ExportCourses'),
    ];
    $items[] = [
        'class' => 'item-course-import',
        'url' => 'course_import.php',
        'label' => get_lang('ImportCourses'),
    ];
    $items[] = [
        'class' => 'item-course-category',
        'url' => 'course_category.php',
        'label' => get_lang('AdminCategories'),
    ];
    $items[] = [
        'class' => 'item-course-subscription',
        'url' => 'subscribe_user2course.php',
        'label' => get_lang('AddUsersToACourse'),
    ];
    $items[] = [
        'class' => 'item-course-subscription-import',
        'url' => 'course_user_import.php',
        'label' => get_lang('ImportUsersToACourse'),
    ];
    //$items[] = [
    //    'url'=>'course_intro_pdf_import.php',
    //    'label' => get_lang('ImportPDFIntroToCourses'),
    //];

    if (api_get_setting('gradebook_enable_grade_model') == 'true') {
        $items[] = [
            'class' => 'item-grade-model',
            'url' => 'grade_models.php',
            'label' => get_lang('GradeModel'),
        ];
    }

    if (isset($extAuthSource) && isset($extAuthSource['ldap']) && count($extAuthSource['ldap']) > 0) {
        $items[] = [
            'class' => 'item-course-subscription-ldap',
            'url' => 'ldap_import_students.php',
            'label' => get_lang('ImportLDAPUsersIntoCourse'),
        ];
    }

    $items[] = [
        'class' => 'item-course-field',
        'url' => 'extra_fields.php?type=course',
        'label' => get_lang('ManageCourseFields'),
    ];
    $items[] = [
        'class' => 'item-question-bank',
        'url' => 'questions.php',
        'label' => get_lang('Questions'),
    ];

    $blocks['courses']['items'] = $items;
    $blocks['courses']['extra'] = null;
}

/* Platform */
$blockPlatform = [
    'icon' => Display::return_icon(
        'platform.png',
        get_lang('Platform'),
        [],
        ICON_SIZE_MEDIUM,
        false
    ),
    'label' => api_ucfirst(get_lang('Platform')),
    'class' => 'block-admin-platform',
    'editable' => false,
    'extraContent' => '',
    'search_form' => '',
    'items' => [],
    'extra' => null,
];

if (api_is_platform_admin()) {
    $blockPlatform['editable'] = true;

    $platformBlockExtraFile = "{$adminExtraContentDir}block-admin-platform_extra.html";

    if (file_exists($platformBlockExtraFile)) {
        $blockPlatform['extraContent'] = file_get_contents($platformBlockExtraFile);
    }

    $search_form = ' <form method="get" action="settings.php" class="form-inline">
            <div class="form-group">
                <input class="form-control"
                type="text"
                name="search_field" value=""
                aria-label="'.get_lang('Search').'" >
                <input type="hidden" value="search_setting" name="category">
                <button class="btn btn-default" type="submit">
                    <em class="fa fa-search"></em> '.get_lang('Search').'
                </button>
            </div>
        </form>';
    $blockPlatform['search_form'] = $search_form;

    $items = [];
    $items[] = [
        'class' => 'item-setting-list',
        'url' => 'settings.php',
        'label' => get_lang('PlatformConfigSettings'),
    ];
    $items[] = [
        'class' => 'item-language-list',
        'url' => 'languages.php',
        'label' => get_lang('Languages'),
    ];
    $items[] = [
        'class' => 'item-plugin-list',
        'url' => 'settings.php?category=Plugins',
        'label' => get_lang('Plugins'),
    ];
    $items[] = [
        'class' => 'item-region-list',
        'url' => 'settings.php?category=Regions',
        'label' => get_lang('Regions'),
    ];
    $items[] = [
        'class' => 'item-global-announcement',
        'url' => 'system_announcements.php',
        'label' => get_lang('SystemAnnouncements'),
    ];
    $items[] = [
        'class' => 'item-global-agenda',
        'url' => api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?type=admin',
        'label' => get_lang('GlobalAgenda'),
    ];

    if (true === api_get_configuration_value('agenda_reminders')) {
        $items[] = [
            'class' => 'item-agenda-reminders',
            'url' => api_get_path(WEB_CODE_PATH).'admin/import_course_agenda_reminders.php',
            'label' => get_lang('ImportCourseEvents'),
        ];
    }

    $items[] = [
        'class' => 'item-homepage',
        'url' => 'configure_homepage.php',
        'label' => get_lang('ConfigureHomePage'),
    ];
    $items[] = [
        'class' => 'item-registration-page',
        'url' => 'configure_inscription.php',
        'label' => get_lang('ConfigureInscription'),
    ];
    $items[] = [
        'class' => 'item-stats',
        'url' => 'statistics/index.php',
        'label' => get_lang('Statistics'),
    ];
    $items[] = [
        'class' => 'item-stats-report',
        'url' => api_get_path(WEB_CODE_PATH).'mySpace/company_reports.php',
        'label' => get_lang('Reports'),
    ];
    $items[] = [
        'class' => 'item-teacher-time-report',
        'url' => api_get_path(WEB_CODE_PATH).'admin/teacher_time_report.php',
        'label' => get_lang('TeacherTimeReport'),
    ];
    if (api_get_configuration_value('chamilo_cms')) {
        $items[] = [
            'class' => 'item-cms',
            'url' => api_get_path(WEB_PATH).'web/app_dev.php/administration/dashboard',
            'label' => get_lang('CMS'),
        ];
    }

    /* Event settings */

    if (api_get_setting('activate_email_template') == 'true') {
        // @deprecated to be removed in 2.x
        $items[] = [
            'class' => 'item-message-management',
            'url' => 'event_controller.php?action=listing',
            'label' => get_lang('EventMessageManagement'),
        ];
    }

    $items[] = [
        'class' => 'item-field',
        'url' => 'extra_field_list.php',
        'label' => get_lang('ExtraFields'),
    ];

    if (!empty($_configuration['multiple_access_urls'])) {
        if (api_is_global_platform_admin()) {
            $items[] = [
                'class' => 'item-access-url',
                'url' => 'access_urls.php',
                'label' => get_lang('ConfigureMultipleAccessURLs'),
            ];
        }
    }

    if (api_get_plugin_setting('dictionary', 'enable_plugin_dictionary') == 'true') {
        $items[] = [
            'class' => 'item-dictionary',
            'url' => api_get_path(WEB_PLUGIN_PATH).'dictionary/terms.php',
            'label' => get_lang('Dictionary'),
        ];
    }

    if (api_get_setting('allow_terms_conditions') == 'true') {
        $items[] = [
            'class' => 'item-terms-and-conditions',
            'url' => 'legal_add.php',
            'label' => get_lang('TermsAndConditions'),
        ];
    }

    if (api_get_configuration_value('mail_template_system')) {
        $items[] = [
            'class' => 'item-mail-template',
            'url' => api_get_path(WEB_CODE_PATH).'mail_template/list.php',
            'label' => get_lang('MailTemplates'),
        ];
    }

    if (api_get_configuration_value('notification_event')) {
        $items[] = [
            'class' => 'item-notification-list',
            'url' => api_get_path(WEB_CODE_PATH).'notification_event/list.php',
            'label' => get_lang('Notifications'),
        ];
    }

    $allowJustification = api_get_plugin_setting('justification', 'tool_enable') === 'true';
    if ($allowJustification) {
        $items[] = [
            'class' => 'item-justification-list',
            'url' => api_get_path(WEB_PLUGIN_PATH).'justification/list.php',
            'label' => get_lang('Justification'),
        ];
    }

    $blockPlatform['items'] = $items;
} elseif (api_is_session_admin() && api_get_configuration_value('session_admin_access_system_announcement')) {
    $items = [];
    $items[] = [
        'class' => 'item-global-announcement',
        'url' => 'system_announcements.php',
        'label' => get_lang('SystemAnnouncements'),
    ];

    $blockPlatform['items'] = $items;
}

if (api_is_platform_admin(true)) {
    $blocks['platform'] = $blockPlatform;
}

/* Sessions */
$blocks['sessions']['icon'] = Display::return_icon(
    'session.png',
    get_lang('Sessions'),
    [],
    ICON_SIZE_MEDIUM,
    false
);
$blocks['sessions']['label'] = api_ucfirst(get_lang('Sessions'));
$blocks['sessions']['class'] = 'block-admin-sessions';

$sessionsBlockExtraFile = "{$adminExtraContentDir}block-admin-sessions_extra.html";

if (file_exists($sessionsBlockExtraFile)) {
    $blocks['sessions']['extraContent'] = file_get_contents($sessionsBlockExtraFile);
}

if (api_is_platform_admin()) {
    $blocks['sessions']['editable'] = true;
}
$sessionPath = api_get_path(WEB_CODE_PATH).'session/';

$search_form = ' <form method="GET" class="form-inline" action="'.$sessionPath.'session_list.php">
                    <div class="form-group">
                        <input class="form-control"
                        type="text"
                        name="keyword"
                        value=""
                        aria-label="'.get_lang('Search').'">
                        <button class="btn btn-default" type="submit">
                            <em class="fa fa-search"></em> '.get_lang('Search').'
                        </button>
                    </div>
                </form>';
$blocks['sessions']['search_form'] = $search_form;
$items = [];
$items[] = [
    'class' => 'item-session-list',
    'url' => $sessionPath.'session_list.php',
    'label' => get_lang('ListSession'),
];
$items[] = [
    'class' => 'item-session-add',
    'url' => $sessionPath.'session_add.php',
    'label' => get_lang('AddSession'),
];
$items[] = [
    'class' => 'item-session-category',
    'url' => $sessionPath.'session_category_list.php',
    'label' => get_lang('ListSessionCategory'),
];
$items[] = [
    'class' => 'item-session-import',
    'url' => $sessionPath.'session_import.php',
    'label' => get_lang('ImportSessionListXMLCSV'),
];
$items[] = [
    'class' => 'item-session-import-hr',
    'url' => $sessionPath.'session_import_drh.php',
    'label' => get_lang('ImportSessionDrhList'),
];
if (isset($extAuthSource) && isset($extAuthSource['ldap']) && count($extAuthSource['ldap']) > 0) {
    $items[] = [
        'class' => 'item-session-subscription-ldap-import',
        'url' => 'ldap_import_students_to_session.php',
        'label' => get_lang('ImportLDAPUsersIntoSession'),
    ];
}
$items[] = [
    'class' => 'item-session-export',
    'url' => $sessionPath.'session_export.php',
    'label' => get_lang('ExportSessionListXMLCSV'),
];

$items[] = [
    'class' => 'item-session-course-copy',
    'url' => '../coursecopy/copy_course_session.php',
    'label' => get_lang('CopyFromCourseInSessionToAnotherSession'),
];

$allowCareer = api_get_configuration_value('allow_session_admin_read_careers');

if (api_is_platform_admin() || ($allowCareer && api_is_session_admin())) {
    // option only visible in development mode. Enable through code if required
    if (is_dir(api_get_path(SYS_TEST_PATH).'datafiller/')) {
        $items[] = [
            'class' => 'item-session-user-move-stats',
            'url' => 'user_move_stats.php',
            'label' => get_lang('MoveUserStats'),
        ];
    }

    $items[] = [
        'class' => 'item-session-user-move',
        'url' => '../coursecopy/move_users_from_course_to_session.php',
        'label' => get_lang('MoveUsersFromCourseToSession'),
    ];

    $items[] = [
        'class' => 'item-career-dashboard',
        'url' => 'career_dashboard.php',
        'label' => get_lang('CareersAndPromotions'),
    ];
    $items[] = [
        'class' => 'item-session-field',
        'url' => 'extra_fields.php?type=session',
        'label' => get_lang('ManageSessionFields'),
    ];
    $items[] = [
        'class' => 'item-resource-sequence',
        'url' => 'resource_sequence.php',
        'label' => get_lang('ResourcesSequencing'),
    ];
    $items[] = [
        'class' => 'item-export-exercise-results',
        'url' => 'export_exercise_results.php',
        'label' => get_lang('ExportExerciseAllResults'),
    ];
}

$blocks['sessions']['items'] = $items;
$blocks['sessions']['extra'] = null;

/* Settings */
if (api_is_platform_admin()) {
    $blocks['settings']['icon'] = Display::return_icon(
        'settings.png',
        get_lang('System'),
        [],
        ICON_SIZE_MEDIUM,
        false
    );
    $blocks['settings']['label'] = api_ucfirst(get_lang('System'));
    $blocks['settings']['class'] = 'block-admin-settings';

    $items = [];
    $items[] = [
        'class' => 'item-clean-cache',
        'url' => 'archive_cleanup.php',
        'label' => get_lang('ArchiveDirCleanup'),
    ];

    $items[] = [
        'class' => 'item-special-export',
        'url' => 'special_exports.php',
        'label' => get_lang('SpecialExports'),
    ];
    /*$items[] = [
        'url' => 'periodic_export.php',
        'label' => get_lang('PeriodicExport'),
    ];*/
    $items[] = [
        'class' => 'item-system-status',
        'url' => 'system_status.php',
        'label' => get_lang('SystemStatus'),
    ];
    if (is_dir(api_get_path(SYS_TEST_PATH).'datafiller/')) {
        $items[] = [
            'class' => 'item-data-filler',
            'url' => 'filler.php',
            'label' => get_lang('DataFiller'),
        ];
    }

    if (is_dir(api_get_path(SYS_TEST_PATH))) {
        $items[] = [
            'class' => 'item-email-tester',
            'url' => 'email_tester.php',
            'label' => get_lang('EMailTester'),
        ];
    }

    $items[] = [
        'class' => 'item-ticket-system',
        'url' => api_get_path(WEB_CODE_PATH).'ticket/tickets.php',
        'label' => get_lang('TicketSystem'),
    ];

    if (api_get_configuration_value('db_manager_enabled') == true &&
        api_is_global_platform_admin()
    ) {
        $host = $_configuration['db_host'];
        $username = $_configuration['db_user'];
        $databaseName = $_configuration['main_database'];

        $items[] = [
            'url' => "db.php?username=$username&db=$databaseName&server=$host",
            'label' => get_lang('DatabaseManager'),
        ];
    }

    if (api_get_configuration_value('allow_session_status')) {
        $items[] = [
            'url' => api_get_path(WEB_CODE_PATH).'session/cron_status.php',
            'label' => get_lang('UpdateSessionStatus'),
        ];
    }

    $blocks['settings']['items'] = $items;
    $blocks['settings']['extra'] = null;
    $blocks['settings']['search_form'] = null;

    // Skills
    if (Skill::isToolAvailable()) {
        $blocks['skills']['icon'] = Display::return_icon(
            'skill-badges.png',
            get_lang('Skills'),
            [],
            ICON_SIZE_MEDIUM,
            false
        );
        $blocks['skills']['label'] = get_lang('Skills');
        $blocks['skills']['class'] = 'block-admin-skills';

        $items = [];
        $items[] = [
            'class' => 'item-skill-wheel',
            'url' => 'skills_wheel.php',
            'label' => get_lang('SkillsWheel'),
        ];
        $items[] = [
            'class' => 'item-skill-import',
            'url' => 'skills_import.php',
            'label' => get_lang('SkillsImport'),
        ];
        $items[] = [
            'class' => 'item-skill-list',
            'url' => 'skill_list.php',
            'label' => get_lang('ManageSkills'),
        ];
        $items[] = [
            'class' => 'item-skill-level',
            'url' => 'skill.php',
            'label' => get_lang('ManageSkillsLevels'),
        ];

        $items[] = [
            'class' => 'item-skill-ranking',
            'url' => api_get_path(WEB_CODE_PATH).'social/skills_ranking.php',
            'label' => get_lang('SkillsRanking'),
        ];
        $items[] = [
            'class' => 'item-skill-gradebook',
            'url' => 'skills_gradebook.php',
            'label' => get_lang('SkillsAndGradebooks'),
        ];
        /*$items[] = [
            'url' => api_get_path(WEB_CODE_PATH).'admin/skill_badge.php',
            'label' => get_lang('Badges'),
        ];*/
        $blocks['skills']['items'] = $items;
        $blocks['skills']['extra'] = null;
        $blocks['skills']['search_form'] = null;
    }

    $allow = api_get_configuration_value('gradebook_dependency');
    if ($allow) {
        $blocks['gradebook']['icon'] = Display::return_icon(
            'gradebook.png',
            get_lang('Gradebook'),
            [],
            ICON_SIZE_MEDIUM,
            false
        );
        $blocks['gradebook']['label'] = get_lang('Gradebook');
        $blocks['gradebook']['class'] = 'block-admin-gradebook';

        $items = [];
        $items[] = [
            'class' => 'item-gradebook-list',
            'url' => 'gradebook_list.php',
            'label' => get_lang('List'),
        ];
        $blocks['gradebook']['items'] = $items;
        $blocks['gradebook']['extra'] = null;
        $blocks['gradebook']['search_form'] = null;
    }
}

if (api_is_platform_admin()) {
    /* Plugins */
    global $_plugins;
    if (isset($_plugins['menu_administrator']) &&
        count($_plugins['menu_administrator']) > 0
    ) {
        $menuAdministratorItems = $_plugins['menu_administrator'];

        if ($menuAdministratorItems) {
            $blocks['plugins']['icon'] = Display::return_icon(
                'plugins.png',
                get_lang('Plugins'),
                [],
                ICON_SIZE_MEDIUM,
                false
            );
            $blocks['plugins']['label'] = get_lang('Plugins');
            $blocks['plugins']['class'] = 'block-admin-plugins';
            $blocks['plugins']['editable'] = true;

            $plugin_obj = new AppPlugin();
            $items = [];

            foreach ($menuAdministratorItems as $pluginName) {
                $pluginInfo = $plugin_obj->getPluginInfo($pluginName, true);
                /** @var \Plugin $plugin */
                $plugin = $pluginInfo['obj'];
                $pluginUrl = null;
                if ($plugin) {
                    $pluginUrl = $plugin->getAdminUrl();
                }

                if (empty($pluginUrl)) {
                    continue;
                }

                $items[] = [
                    'class' => 'item-plugin-'.$pluginInfo['title'],
                    'url' => $pluginUrl,
                    'label' => $pluginInfo['title'],
                ];
            }

            $blocks['plugins']['items'] = $items;
            $blocks['plugins']['extra'] = '';
        }
    }

    if (!api_get_configuration_value('disable_gdpr')) {
        // Data protection
        $blocks['data_privacy']['icon'] = Display::return_icon(
            'platform.png',
            get_lang('Platform'),
            [],
            ICON_SIZE_MEDIUM,
            false
        );
        $blocks['data_privacy']['label'] = api_ucfirst(get_lang('PersonalDataPrivacy'));
        $blocks['data_privacy']['class'] = 'block-admin-privacy';
        $blocks['data_privacy']['editable'] = false;

        $items = [];
        $items[] = [
            'class' => 'item-privacy-consent',
            'url' => api_get_path(WEB_CODE_PATH).'admin/user_list_consent.php',
            'label' => get_lang('UserList'),
        ];

        $blocks['data_privacy']['items'] = $items;
        $blocks['data_privacy']['extra'] = null;
        $blocks['data_privacy']['search_form'] = null;
    }
    /* Chamilo.org */
    $blocks['chamilo']['icon'] = Display::return_icon(
        'platform.png',
        'Chamilo.org',
        [],
        ICON_SIZE_MEDIUM,
        false
    );
    $blocks['chamilo']['label'] = 'Chamilo.org';
    $blocks['chamilo']['class'] = 'block-admin-chamilo';

    $items = [];
    $items[] = [
        'class' => 'item-software-homepage',
        'url' => 'https://chamilo.org/',
        'label' => get_lang('ChamiloHomepage'),
    ];
    // Custom linking to user guides in the existing languages
    $guideLinks = [
        'french' => 'v/1.11.x-fr/',
        'spanish' => 'v/1.11.x-es/',
        'dutch' => 'v/1.11.x-nl/',
        'galician' => 'v/1.11.x-ga/',
    ];
    $guideLink = 'https://docs.chamilo.org/';
    if (!empty($guideLinks[$language_interface])) {
        $guideLink .= $guideLinks[$language_interface];
    }
    $items[] = [
        'class' => 'item-user-guides',
        'url' => $guideLink,
        'label' => get_lang('UserGuides'),
    ];
    $items[] = [
        'class' => 'item-forum',
        'url' => 'https://forum.chamilo.org/',
        'label' => get_lang('ChamiloForum'),
    ];
    $items[] = [
        'class' => 'item-installation-guide',
        'url' => '../../documentation/installation_guide.html',
        'label' => get_lang('InstallationGuide'),
    ];
    $items[] = [
        'class' => 'item-changelog',
        'url' => '../../documentation/changelog.html',
        'label' => get_lang('ChangesInLastVersion'),
    ];
    $items[] = [
        'class' => 'item-credits',
        'url' => '../../documentation/credits.html',
        'label' => get_lang('ContributorsList'),
    ];
    $items[] = [
        'class' => 'item-security',
        'url' => '../../documentation/security.html',
        'label' => get_lang('SecurityGuide'),
    ];
    $items[] = [
        'class' => 'item-optimization',
        'url' => '../../documentation/optimization.html',
        'label' => get_lang('OptimizationGuide'),
    ];
    $items[] = [
        'class' => 'item-extensions',
        'url' => 'https://chamilo.org/extensions',
        'label' => get_lang('ChamiloExtensions'),
    ];
    $items[] = [
        'class' => 'item-providers',
        'url' => 'https://chamilo.org/providers',
        'label' => get_lang('ChamiloOfficialServicesProviders'),
    ];

    $blocks['chamilo']['items'] = $items;
    $blocks['chamilo']['extra'] = null;
    $blocks['chamilo']['search_form'] = null;

    // Version check
    $blocks['version_check']['icon'] = Display::return_icon(
        'platform.png',
        'Chamilo.org',
        [],
        ICON_SIZE_MEDIUM,
        false
    );
    $blocks['version_check']['label'] = get_lang('VersionCheck');
    $blocks['version_check']['extra'] = '<div class="admin-block-version"></div>';
    $blocks['version_check']['search_form'] = null;
    $blocks['version_check']['items'] = '<div class="block-admin-version_check"></div>';
    $blocks['version_check']['class'] = 'block-admin-version-check';

    // Check Hook Event for Admin Block Object
    if (!empty($hook)) {
        // If not empty, then notify Post process to Hook Observers for Admin Block
        $hook->setEventData(['blocks' => $blocks]);
        $data = $hook->notifyAdminBlock(HOOK_EVENT_TYPE_POST);
        // Check if blocks data is not null
        if (isset($data['blocks'])) {
            // Get modified blocks
            $blocks = $data['blocks'];
        }
    }

    //Hack for fix migration on session_rel_user
    $tableColumns = Database::getManager()
        ->getConnection()
        ->getSchemaManager()
        ->listTableColumns(
            Database::get_main_table(TABLE_MAIN_SESSION_USER)
        );

    if (!array_key_exists('duration', $tableColumns)) {
        try {
            $dbSchema = Database::getManager()->getConnection()->getSchemaManager();
            $durationColumn = new \Doctrine\DBAL\Schema\Column(
                'duration',
                Doctrine\DBAL\Types\Type::getType(\Doctrine\DBAL\Types\Type::INTEGER),
                ['notnull' => false]
            );
            $tableDiff = new \Doctrine\DBAL\Schema\TableDiff('session_rel_user', [$durationColumn]);
            $dbSchema->alterTable($tableDiff);
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }
    //end hack
}
$admin_ajax_url = api_get_path(WEB_AJAX_PATH).'admin.ajax.php';

$tpl = new Template();

// Display the Site Use Cookie Warning Validation
$useCookieValidation = api_get_setting('cookie_warning');
if ($useCookieValidation === 'true') {
    if (isset($_POST['acceptCookies'])) {
        api_set_site_use_cookie_warning_cookie();
    } elseif (!api_site_use_cookie_warning_cookie_exist()) {
        if (Template::isToolBarDisplayedForUser()) {
            $tpl->assign('toolBarDisplayed', true);
        } else {
            $tpl->assign('toolBarDisplayed', false);
        }
        $tpl->assign('displayCookieUsageWarning', true);
    }
}

$tpl->assign('web_admin_ajax_url', $admin_ajax_url);
$tpl->assign('blocks', $blocks);

if (api_is_platform_admin()) {
    $extraContentForm = new FormValidator(
        'block_extra_data',
        'post',
        '#',
        null,
        [
            'id' => 'block-extra-data',
            'class' => '',
        ],
        FormValidator::LAYOUT_BOX_NO_LABEL
    );
    $extraContentFormRenderer = $extraContentForm->getDefaultRenderer();

    if ($extraContentForm->validate()) {
        $extraData = $extraContentForm->getSubmitValues();
        $extraData = array_map(['Security', 'remove_XSS'], $extraData);

        if (!empty($extraData['block'])) {
            if (!is_dir($adminExtraContentDir)) {
                mkdir(
                    $adminExtraContentDir,
                    api_get_permissions_for_new_directories(),
                    true
                );
            }

            if (!is_writable($adminExtraContentDir)) {
                exit;
            }

            $fullFilePath = $adminExtraContentDir.$extraData['block'];
            $fullFilePath .= "_extra.html";

            file_put_contents($fullFilePath, $extraData['extra_content']);

            header('Location: '.api_get_self());
            exit;
        }
    }

    $extraContentForm->addTextarea(
        'extra_content',
        null,
        ['id' => 'extra_content']
    );
    $extraContentFormRenderer->setElementTemplate(
        '<div class="form-group">{element}</div>',
        'extra_content'
    );
    $extraContentForm->addElement(
        'hidden',
        'block',
        null,
        [
            'id' => 'extra-block',
        ]
    );
    $extraContentForm->addButtonExport(
        get_lang('Save'),
        'submit_extra_content'
    );

    $tpl->assign('extraDataForm', $extraContentForm->returnForm());
}

// The template contains the call to the AJAX version checker
$admin_template = $tpl->get_template('admin/settings_index.tpl');
$content = $tpl->fetch($admin_template);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
