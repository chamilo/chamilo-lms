<?php

/* For licensing terms, see /license.txt */

/**
 * Central registry for legacy and modern reporting pages.
 *
 * The registry documents existing reports, their expected audience, their
 * permission category and their legacy/canonical URLs. Canonical report links
 * use this registry to apply a consistent role matrix while preserving the
 * original legacy report implementations.
 */
class ReportRegistry
{
    public const CATEGORY_PLATFORM = 'platform';
    public const CATEGORY_COURSE = 'course';
    public const CATEGORY_SECURITY = 'security';
    public const CATEGORY_LEARNING_ANALYTICS = 'learning_analytics';
    public const CATEGORY_GRADEBOOK = 'gradebook';
    public const CATEGORY_SOCIAL = 'social';
    public const CATEGORY_EXPORT = 'export';

    /**
     * Standard roles used for the initial report/role matrix.
     */
    public static function getRoles(): array
    {
        return [
            'ROLE_ADMIN' => get_lang('Administrator'),
            'ROLE_GLOBAL_ADMIN' => get_lang('Global administrator'),
            'ROLE_SESSION_MANAGER' => get_lang('Session manager'),
            'ROLE_TEACHER' => get_lang('Teacher'),
            'ROLE_STUDENT' => get_lang('Student'),
            'ROLE_HR' => get_lang('Human resources manager'),
            'ROLE_STUDENT_BOSS' => get_lang('Student boss'),
            'ROLE_QUESTION_MANAGER' => get_lang('Question manager'),
            'ROLE_INVITEE' => get_lang('Invitee'),
            'ROLE_CURRENT_COURSE_TEACHER' => get_lang('Course teacher'),
            'ROLE_CURRENT_COURSE_STUDENT' => get_lang('Course student'),
        ];
    }

    public static function getCategories(): array
    {
        return [
            self::CATEGORY_PLATFORM => get_lang('Platform reports'),
            self::CATEGORY_SECURITY => get_lang('Security reports'),
            self::CATEGORY_COURSE => get_lang('Course reports'),
            self::CATEGORY_LEARNING_ANALYTICS => get_lang('Learning analytics'),
            self::CATEGORY_GRADEBOOK => get_lang('Gradebook reports'),
            self::CATEGORY_SOCIAL => get_lang('Social reports'),
            self::CATEGORY_EXPORT => get_lang('Exports'),
        ];
    }

    public static function getPermissionCategories(): array
    {
        return [
            'reports.platform' => [
                'label' => get_lang('Platform reports'),
                'description' => get_lang('Reports about platform-wide usage, users, courses and sessions.'),
                'roles' => ['ROLE_ADMIN', 'ROLE_GLOBAL_ADMIN', 'ROLE_SESSION_MANAGER'],
                'permission_slug' => 'report:viewplatform',
            ],
            'reports.security' => [
                'label' => get_lang('Security reports'),
                'description' => get_lang('Audit and security-sensitive reports.'),
                'roles' => ['ROLE_ADMIN', 'ROLE_GLOBAL_ADMIN'],
                'permission_slug' => 'report:viewsecurity',
            ],
            'reports.course' => [
                'label' => get_lang('Course reports'),
                'description' => get_lang('Reports scoped to courses managed by the user.'),
                'roles' => ['ROLE_ADMIN', 'ROLE_GLOBAL_ADMIN', 'ROLE_SESSION_MANAGER', 'ROLE_TEACHER', 'ROLE_CURRENT_COURSE_TEACHER'],
                'permission_slug' => 'report:viewcourse',
            ],
            'reports.learning_analytics' => [
                'label' => get_lang('Learning analytics'),
                'description' => get_lang('Reports related to learner activity and follow-up.'),
                'roles' => ['ROLE_ADMIN', 'ROLE_GLOBAL_ADMIN', 'ROLE_SESSION_MANAGER', 'ROLE_TEACHER', 'ROLE_HR', 'ROLE_STUDENT_BOSS'],
                'permission_slug' => 'report:viewlearninganalytics',
            ],
            'reports.gradebook' => [
                'label' => get_lang('Gradebook reports'),
                'description' => get_lang('Reports related to scores, certificates and gradebook data.'),
                'roles' => ['ROLE_ADMIN', 'ROLE_GLOBAL_ADMIN', 'ROLE_SESSION_MANAGER', 'ROLE_TEACHER', 'ROLE_CURRENT_COURSE_TEACHER'],
                'permission_slug' => 'report:viewgradebook',
            ],
            'reports.export' => [
                'label' => get_lang('Exports'),
                'description' => get_lang('Reports and exports that generate downloadable files.'),
                'roles' => ['ROLE_ADMIN', 'ROLE_GLOBAL_ADMIN', 'ROLE_SESSION_MANAGER'],
                'permission_slug' => 'report:viewexports',
            ],
        ];
    }

    public static function getReports(): array
    {
        $admin = ['ROLE_ADMIN', 'ROLE_GLOBAL_ADMIN'];
        $adminSession = ['ROLE_ADMIN', 'ROLE_GLOBAL_ADMIN', 'ROLE_SESSION_MANAGER'];
        $courseManagers = ['ROLE_ADMIN', 'ROLE_GLOBAL_ADMIN', 'ROLE_SESSION_MANAGER', 'ROLE_TEACHER', 'ROLE_CURRENT_COURSE_TEACHER'];
        $learningFollowUp = ['ROLE_ADMIN', 'ROLE_GLOBAL_ADMIN', 'ROLE_SESSION_MANAGER', 'ROLE_TEACHER', 'ROLE_HR', 'ROLE_STUDENT_BOSS'];

        return [
            [
                'id' => 'platform_global_statistics',
                'title' => get_lang('Global statistics'),
                'description' => get_lang('Main platform statistics dashboard.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_courses',
                'title' => get_lang('Courses'),
                'description' => get_lang('Number of courses and course distribution report.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=courses',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_tools_access',
                'title' => get_lang('Tools access'),
                'description' => get_lang('Platform-wide access to course tools.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=tools',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_tool_resource_count',
                'title' => get_lang('Tool-based resource count'),
                'description' => get_lang('Number of resources per tool.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=tool_usage',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_latest_course_access',
                'title' => get_lang('Latest access'),
                'description' => get_lang('Latest access to courses.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=courselastvisit',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_courses_by_language',
                'title' => get_lang('Number of courses by language'),
                'description' => get_lang('Course distribution by language.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=coursebylanguage',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_courses_usage',
                'title' => get_lang('Courses usage'),
                'description' => get_lang('Course usage report.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=courses_usage',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_users',
                'title' => get_lang('Number of users'),
                'description' => get_lang('Platform user count report.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=users',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_recent_logins',
                'title' => get_lang('Logins'),
                'description' => get_lang('Recent logins chart.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=recentlogins',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_logins_by_month',
                'title' => get_lang('Logins').' ('.get_lang('Month').')',
                'description' => get_lang('Login distribution by month.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=logins&type=month',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_logins_by_day',
                'title' => get_lang('Logins').' ('.get_lang('Day').')',
                'description' => get_lang('Login distribution by day.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=logins&type=day',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_logins_by_hour',
                'title' => get_lang('Logins').' ('.get_lang('Hour').')',
                'description' => get_lang('Login distribution by hour.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=logins&type=hour',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_users_pictures',
                'title' => get_lang('Number of users').' ('.get_lang('Picture').')',
                'description' => get_lang('Users with profile picture.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=pictures',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_logins_by_date',
                'title' => get_lang('Logins by date'),
                'description' => get_lang('Login report filtered by date.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=logins_by_date',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_no_login_users',
                'title' => get_lang('Not logged in for some time'),
                'description' => get_lang('Users who have not logged in recently.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=no_login_users',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_zombies',
                'title' => get_lang('Zombies'),
                'description' => get_lang('Inactive or suspicious user accounts report.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=zombies',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_users_statistics',
                'title' => get_lang('Users statistics'),
                'description' => get_lang('Users activity statistics.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=users_active',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_users_online',
                'title' => get_lang('Users online'),
                'description' => get_lang('Currently online users report.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=users_online',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_new_user_registrations',
                'title' => get_lang('New users registrations'),
                'description' => get_lang('New user registrations over time.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=new_user_registrations',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_subscriptions_by_day',
                'title' => get_lang('Course/Session subscriptions by day'),
                'description' => get_lang('Course and session subscriptions over time.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=subscription_by_day',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_duplicated_users',
                'title' => get_lang('Duplicate users'),
                'description' => get_lang('Duplicate user detection and maintenance report.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=duplicated_users',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_user_session_stats',
                'title' => get_lang('Portal user session stats'),
                'description' => get_lang('Platform user/session statistics.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=user_session',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'platform_quarterly_report',
                'title' => get_lang('Quarterly report'),
                'description' => get_lang('Quarterly platform report.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=quarterly_report',
                'roles' => $adminSession,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'security_activities_audit',
                'title' => get_lang('Activities audit'),
                'description' => get_lang('Audit report for important administrative and platform activities.'),
                'category' => self::CATEGORY_SECURITY,
                'url' => '/main/admin/activities_audit.php',
                'roles' => $admin,
                'permission' => 'reports.security',
            ],
            [
                'id' => 'social_messages_received',
                'title' => get_lang('Number of messages received'),
                'description' => get_lang('Social messages received report.'),
                'category' => self::CATEGORY_SOCIAL,
                'url' => '/main/admin/statistics/index.php?report=messagereceived',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'social_messages_sent',
                'title' => get_lang('Number of messages sent'),
                'description' => get_lang('Social messages sent report.'),
                'category' => self::CATEGORY_SOCIAL,
                'url' => '/main/admin/statistics/index.php?report=messagesent',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'social_contacts_count',
                'title' => get_lang('Contacts count'),
                'description' => get_lang('Social contacts count report.'),
                'category' => self::CATEGORY_SOCIAL,
                'url' => '/main/admin/statistics/index.php?report=friends',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'session_by_date',
                'title' => get_lang('Sessions by date'),
                'description' => get_lang('Sessions created over time.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/statistics/index.php?report=session_by_date',
                'roles' => $adminSession,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'learning_analytics_dashboard',
                'title' => get_lang('Learning analytics'),
                'description' => get_lang('Learning analytics dashboard.'),
                'category' => self::CATEGORY_LEARNING_ANALYTICS,
                'url' => '/main/my_space/index.php',
                'roles' => $learningFollowUp,
                'permission' => 'reports.learning_analytics',
            ],
            [
                'id' => 'learning_teacher_time_report',
                'title' => get_lang('Teachers time report'),
                'description' => get_lang('Teacher time report.'),
                'category' => self::CATEGORY_LEARNING_ANALYTICS,
                'url' => '/main/admin/teacher_time_report.php',
                'roles' => $adminSession,
                'permission' => 'reports.learning_analytics',
            ],
            [
                'id' => 'learning_teachers_time_by_session_report',
                'title' => get_lang('Teachers time by session report'),
                'description' => get_lang('Teacher time report grouped by session.'),
                'category' => self::CATEGORY_LEARNING_ANALYTICS,
                'url' => '/main/admin/teachers_time_by_session_report.php',
                'roles' => $adminSession,
                'permission' => 'reports.learning_analytics',
            ],
            [
                'id' => 'learning_corporate_report',
                'title' => get_lang('Corporate report'),
                'description' => get_lang('Corporate learning analytics report.'),
                'category' => self::CATEGORY_LEARNING_ANALYTICS,
                'url' => '/main/my_space/company_reports.php',
                'roles' => ['ROLE_ADMIN', 'ROLE_HR', 'ROLE_STUDENT_BOSS'],
                'permission' => 'reports.learning_analytics',
            ],
            [
                'id' => 'course_learners_tracking',
                'title' => get_lang('Report on learners'),
                'description' => get_lang('Course learners tracking report.'),
                'category' => self::CATEGORY_COURSE,
                'url' => '/main/tracking/courseLog.php',
                'roles' => $courseManagers,
                'permission' => 'reports.course',
            ],
            [
                'id' => 'course_activity_statistics',
                'title' => get_lang('Course activity statistics'),
                'description' => get_lang('Course activity statistics with connected and inactive users.'),
                'category' => self::CATEGORY_COURSE,
                'url' => '/main/tracking/course_activity_statistics.php',
                'roles' => $courseManagers,
                'permission' => 'reports.course',
            ],
            [
                'id' => 'course_learner_tracking_details',
                'title' => get_lang('Course tracking details'),
                'description' => get_lang('Detailed tracking report for a learner inside a course.'),
                'category' => self::CATEGORY_COURSE,
                'url' => '/main/tracking/course_user_details.php',
                'roles' => $courseManagers,
                'permission' => 'reports.course',
            ],
            [
                'id' => 'gradebook_overview',
                'title' => get_lang('Gradebook'),
                'description' => get_lang('Course gradebook overview.'),
                'category' => self::CATEGORY_GRADEBOOK,
                'url' => '/main/gradebook/index.php',
                'roles' => $courseManagers,
                'permission' => 'reports.gradebook',
            ],
            [
                'id' => 'gradebook_statistics',
                'title' => get_lang('Gradebook statistics'),
                'description' => get_lang('Gradebook statistics report.'),
                'category' => self::CATEGORY_GRADEBOOK,
                'url' => '/main/gradebook/gradebook_statistics.php',
                'roles' => $courseManagers,
                'permission' => 'reports.gradebook',
            ],
            [
                'id' => 'gradebook_certificate_report',
                'title' => get_lang('Certificate report'),
                'description' => get_lang('Certificate report for course gradebook.'),
                'category' => self::CATEGORY_GRADEBOOK,
                'url' => '/main/gradebook/certificate_report.php',
                'roles' => $courseManagers,
                'permission' => 'reports.gradebook',
            ],
            [
                'id' => 'gradebook_user_stats',
                'title' => get_lang('User statistics'),
                'description' => get_lang('Course gradebook statistics for a user.'),
                'category' => self::CATEGORY_GRADEBOOK,
                'url' => '/main/gradebook/user_stats.php',
                'roles' => $courseManagers,
                'permission' => 'reports.gradebook',
            ],
            [
                'id' => 'gradebook_personal_stats',
                'title' => get_lang('Personal statistics'),
                'description' => get_lang('Personal gradebook statistics.'),
                'category' => self::CATEGORY_GRADEBOOK,
                'url' => '/main/gradebook/personal_stats.php',
                'roles' => ['ROLE_STUDENT', 'ROLE_CURRENT_COURSE_STUDENT', 'ROLE_TEACHER', 'ROLE_CURRENT_COURSE_TEACHER'],
                'permission' => 'reports.gradebook',
            ],
            [
                'id' => 'reports_catalog',
                'title' => get_lang('Reports catalog'),
                'description' => get_lang('Central catalog of available reports, roles and permission categories.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/reports_catalog.php',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'course_log_events',
                'title' => get_lang('Course log events'),
                'description' => get_lang('Detailed course tracking events.'),
                'category' => self::CATEGORY_COURSE,
                'url' => '/main/tracking/course_log_events.php',
                'roles' => $courseManagers,
                'permission' => 'reports.course',
            ],
            [
                'id' => 'course_log_tools',
                'title' => get_lang('Course tool usage'),
                'description' => get_lang('Course tracking report grouped by tools.'),
                'category' => self::CATEGORY_COURSE,
                'url' => '/main/tracking/course_log_tools.php',
                'roles' => $courseManagers,
                'permission' => 'reports.course',
            ],
            [
                'id' => 'course_log_resources',
                'title' => get_lang('Course resource usage'),
                'description' => get_lang('Course tracking report grouped by resources.'),
                'category' => self::CATEGORY_COURSE,
                'url' => '/main/tracking/course_log_resources.php',
                'roles' => $courseManagers,
                'permission' => 'reports.course',
            ],
            [
                'id' => 'course_log_groups',
                'title' => get_lang('Course group tracking'),
                'description' => get_lang('Tracking report for course groups.'),
                'category' => self::CATEGORY_COURSE,
                'url' => '/main/tracking/course_log_groups.php',
                'roles' => $courseManagers,
                'permission' => 'reports.course',
            ],
            [
                'id' => 'course_session_report',
                'title' => get_lang('Course session report'),
                'description' => get_lang('Tracking report for a course inside sessions.'),
                'category' => self::CATEGORY_COURSE,
                'url' => '/main/tracking/course_session_report.php',
                'roles' => $courseManagers,
                'permission' => 'reports.course',
            ],
            [
                'id' => 'course_learning_path_report',
                'title' => get_lang('Learning path report'),
                'description' => get_lang('Learning path tracking report.'),
                'category' => self::CATEGORY_LEARNING_ANALYTICS,
                'url' => '/main/tracking/lp_report.php',
                'roles' => $courseManagers,
                'permission' => 'reports.learning_analytics',
            ],
            [
                'id' => 'course_learning_path_results_by_user',
                'title' => get_lang('Learning path results by user'),
                'description' => get_lang('Learning path results grouped by user.'),
                'category' => self::CATEGORY_LEARNING_ANALYTICS,
                'url' => '/main/tracking/lp_results_by_user.php',
                'roles' => $courseManagers,
                'permission' => 'reports.learning_analytics',
            ],
            [
                'id' => 'course_exams_tracking',
                'title' => get_lang('Exercises report'),
                'description' => get_lang('Course exercises and exams tracking report.'),
                'category' => self::CATEGORY_LEARNING_ANALYTICS,
                'url' => '/main/tracking/exams.php',
                'roles' => $courseManagers,
                'permission' => 'reports.learning_analytics',
            ],
            [
                'id' => 'course_question_report',
                'title' => get_lang('Question report'),
                'description' => get_lang('Course question tracking report.'),
                'category' => self::CATEGORY_LEARNING_ANALYTICS,
                'url' => '/main/tracking/question_course_report.php',
                'roles' => $courseManagers,
                'permission' => 'reports.learning_analytics',
            ],
            [
                'id' => 'course_messages_tracking',
                'title' => get_lang('Messages report'),
                'description' => get_lang('Course messages tracking report.'),
                'category' => self::CATEGORY_COURSE,
                'url' => '/main/tracking/messages.php',
                'roles' => $courseManagers,
                'permission' => 'reports.course',
            ],
            [
                'id' => 'course_total_time',
                'title' => get_lang('Total time'),
                'description' => get_lang('Total time tracking report for course learners.'),
                'category' => self::CATEGORY_LEARNING_ANALYTICS,
                'url' => '/main/tracking/total_time.php',
                'roles' => $courseManagers,
                'permission' => 'reports.learning_analytics',
            ],
            [
                'id' => 'gradebook_display_summary',
                'title' => get_lang('Gradebook summary'),
                'description' => get_lang('Summary view of gradebook results.'),
                'category' => self::CATEGORY_GRADEBOOK,
                'url' => '/main/gradebook/gradebook_display_summary.php',
                'roles' => $courseManagers,
                'permission' => 'reports.gradebook',
            ],
            [
                'id' => 'gradebook_flat_view',
                'title' => get_lang('Flat view'),
                'description' => get_lang('Flat gradebook results view.'),
                'category' => self::CATEGORY_GRADEBOOK,
                'url' => '/main/gradebook/gradebook_flatview.php',
                'roles' => $courseManagers,
                'permission' => 'reports.gradebook',
            ],
            [
                'id' => 'gradebook_evaluation_log',
                'title' => get_lang('Evaluation log'),
                'description' => get_lang('Change log for gradebook evaluations.'),
                'category' => self::CATEGORY_GRADEBOOK,
                'url' => '/main/gradebook/gradebook_showlog_eval.php',
                'roles' => $courseManagers,
                'permission' => 'reports.gradebook',
            ],
            [
                'id' => 'gradebook_link_log',
                'title' => get_lang('Link log'),
                'description' => get_lang('Change log for gradebook links.'),
                'category' => self::CATEGORY_GRADEBOOK,
                'url' => '/main/gradebook/gradebook_showlog_link.php',
                'roles' => $courseManagers,
                'permission' => 'reports.gradebook',
            ],
            [
                'id' => 'gradebook_result_detail',
                'title' => get_lang('Result details'),
                'description' => get_lang('Detailed view of a gradebook result.'),
                'category' => self::CATEGORY_GRADEBOOK,
                'url' => '/main/gradebook/gradebook_view_result.php',
                'roles' => $courseManagers,
                'permission' => 'reports.gradebook',
            ],
            [
                'id' => 'gradebook_my_certificates',
                'title' => get_lang('My certificates'),
                'description' => get_lang('Personal certificates report.'),
                'category' => self::CATEGORY_GRADEBOOK,
                'url' => '/main/gradebook/my_certificates.php',
                'roles' => ['ROLE_STUDENT', 'ROLE_CURRENT_COURSE_STUDENT', 'ROLE_TEACHER', 'ROLE_CURRENT_COURSE_TEACHER'],
                'permission' => 'reports.gradebook',
            ],
            [
                'id' => 'admin_user_move_stats',
                'title' => get_lang('User move statistics'),
                'description' => get_lang('Report for user move and transfer statistics.'),
                'category' => self::CATEGORY_PLATFORM,
                'url' => '/main/admin/user_move_stats.php',
                'roles' => $admin,
                'permission' => 'reports.platform',
            ],
            [
                'id' => 'admin_user_consent_list',
                'title' => get_lang('User consent list'),
                'description' => get_lang('Report of users and consent status.'),
                'category' => self::CATEGORY_SECURITY,
                'url' => '/main/admin/user_list_consent.php',
                'roles' => $admin,
                'permission' => 'reports.security',
            ],
            [
                'id' => 'export_special_exports',
                'title' => get_lang('Special exports'),
                'description' => get_lang('Special platform exports.'),
                'category' => self::CATEGORY_EXPORT,
                'url' => '/main/admin/special_exports.php',
                'roles' => $admin,
                'permission' => 'reports.export',
            ],
            [
                'id' => 'export_certificates',
                'title' => get_lang('Export certificates'),
                'description' => get_lang('Export platform certificates.'),
                'category' => self::CATEGORY_EXPORT,
                'url' => '/main/admin/export_certificates.php',
                'roles' => $admin,
                'permission' => 'reports.export',
            ],
            [
                'id' => 'export_exercise_results',
                'title' => get_lang('Export exercise results'),
                'description' => get_lang('Export exercise results from the platform.'),
                'category' => self::CATEGORY_EXPORT,
                'url' => '/main/admin/export_exercise_results.php',
                'roles' => $admin,
                'permission' => 'reports.export',
            ],

            [
                'id' => 'course_survey_reporting',
                'title' => get_lang('Survey reporting'),
                'description' => get_lang('Course survey results and reporting.'),
                'category' => self::CATEGORY_COURSE,
                'url' => '/main/survey/reporting.php',
                'roles' => $courseManagers,
                'permission' => 'reports.course',
            ],
            [
                'id' => 'course_attendance_report',
                'title' => get_lang('Attendance report'),
                'description' => get_lang('Course attendance tracking and reports.'),
                'category' => self::CATEGORY_COURSE,
                'url' => '/main/attendance/index.php',
                'roles' => $courseManagers,
                'permission' => 'reports.course',
            ],
            [
                'id' => 'course_exercise_global_report',
                'title' => get_lang('Exercises global report'),
                'description' => get_lang('Global exercise results report for the course.'),
                'category' => self::CATEGORY_LEARNING_ANALYTICS,
                'url' => '/main/exercise/exercise_global_report.php',
                'roles' => $courseManagers,
                'permission' => 'reports.learning_analytics',
            ],
            [
                'id' => 'course_exercise_report',
                'title' => get_lang('Exercise report'),
                'description' => get_lang('Detailed exercise report.'),
                'category' => self::CATEGORY_LEARNING_ANALYTICS,
                'url' => '/main/exercise/exercise_report.php',
                'roles' => $courseManagers,
                'permission' => 'reports.learning_analytics',
            ],
            [
                'id' => 'course_exercise_history',
                'title' => get_lang('Exercise history'),
                'description' => get_lang('Exercise attempt history.'),
                'category' => self::CATEGORY_LEARNING_ANALYTICS,
                'url' => '/main/exercise/exercise_history.php',
                'roles' => $courseManagers,
                'permission' => 'reports.learning_analytics',
            ],
            [
                'id' => 'course_exercise_results',
                'title' => get_lang('Exercise results'),
                'description' => get_lang('Exercise results and corrections.'),
                'category' => self::CATEGORY_LEARNING_ANALYTICS,
                'url' => '/main/exercise/result.php',
                'roles' => $courseManagers,
                'permission' => 'reports.learning_analytics',
            ],
            [
                'id' => 'course_exercise_question_stats',
                'title' => get_lang('Question statistics'),
                'description' => get_lang('Question-level exercise statistics.'),
                'category' => self::CATEGORY_LEARNING_ANALYTICS,
                'url' => '/main/exercise/question_stats.php',
                'roles' => $courseManagers,
                'permission' => 'reports.learning_analytics',
            ],
            [
                'id' => 'course_exercise_stats',
                'title' => get_lang('Exercise statistics'),
                'description' => get_lang('Exercise statistics page.'),
                'category' => self::CATEGORY_LEARNING_ANALYTICS,
                'url' => '/main/exercise/stats.php',
                'roles' => $courseManagers,
                'permission' => 'reports.learning_analytics',
            ],
            [
                'id' => 'course_exercise_live_stats',
                'title' => get_lang('Live exercise statistics'),
                'description' => get_lang('Live exercise statistics for teachers.'),
                'category' => self::CATEGORY_LEARNING_ANALYTICS,
                'url' => '/main/exercise/live_stats.php',
                'roles' => $courseManagers,
                'permission' => 'reports.learning_analytics',
            ],
            [
                'id' => 'course_lp_stats',
                'title' => get_lang('Learning path statistics'),
                'description' => get_lang('Learning path item statistics.'),
                'category' => self::CATEGORY_LEARNING_ANALYTICS,
                'url' => '/main/lp/lp_stats.php',
                'roles' => $courseManagers,
                'permission' => 'reports.learning_analytics',
            ],
            [
                'id' => 'course_lp_report_legacy',
                'title' => get_lang('Learning path report'),
                'description' => get_lang('Learning path report inside a course.'),
                'category' => self::CATEGORY_LEARNING_ANALYTICS,
                'url' => '/main/lp/lp_report.php',
                'roles' => $courseManagers,
                'permission' => 'reports.learning_analytics',
            ],
            [
                'id' => 'social_skills_report',
                'title' => get_lang('Skills report'),
                'description' => get_lang('User skills report.'),
                'category' => self::CATEGORY_SOCIAL,
                'url' => '/main/social/my_skills_report.php',
                'roles' => $learningFollowUp,
                'permission' => 'reports.learning_analytics',
            ],
            [
                'id' => 'export_periodic_export',
                'title' => get_lang('Periodic export'),
                'description' => get_lang('Scheduled or periodic export configuration.'),
                'category' => self::CATEGORY_EXPORT,
                'url' => '/main/admin/periodic_export.php',
                'roles' => $admin,
                'permission' => 'reports.export',
            ],
            [
                'id' => 'export_user_export',
                'title' => get_lang('User export'),
                'description' => get_lang('Export users from the platform.'),
                'category' => self::CATEGORY_EXPORT,
                'url' => '/main/admin/user_export.php',
                'roles' => $admin,
                'permission' => 'reports.export',
            ],
        ];
    }

    public static function findReport(string $id): ?array
    {
        foreach (self::getReports() as $report) {
            if ($id === $report['id']) {
                return $report;
            }
        }

        return null;
    }

    public static function getReportsByCategory(): array
    {
        $grouped = [];

        foreach (self::getReports() as $report) {
            $grouped[$report['category']][] = $report;
        }

        return $grouped;
    }

    public static function getLegacyUrl(array $report): string
    {
        return (string) ($report['url'] ?? '#');
    }

    public static function getFriendlyUrl(array $report): string
    {
        if (!empty($report['friendly_url'])) {
            return (string) $report['friendly_url'];
        }

        if (empty($report['id'])) {
            return self::getLegacyUrl($report);
        }

        return api_get_path(WEB_CODE_PATH).'admin/report.php?'.http_build_query(['id' => $report['id']]);
    }

    public static function getCanonicalUrl(string $reportId, array $extraQuery = []): string
    {
        $report = self::findReport($reportId);

        if (null === $report) {
            return api_get_path(WEB_CODE_PATH).'admin/reports_catalog.php';
        }

        $query = array_merge(['id' => $reportId], $extraQuery);

        return api_get_path(WEB_CODE_PATH).'admin/report.php?'.http_build_query($query);
    }

    public static function getCurrentUserRoles(): array
    {
        $roles = [];

        foreach (array_keys(self::getRoles()) as $role) {
            if (function_exists('api_user_has_role') && api_user_has_role($role)) {
                $roles[] = $role;
            }
        }

        if (function_exists('api_is_platform_admin') && api_is_platform_admin()) {
            $roles[] = 'ROLE_ADMIN';
        }

        if (function_exists('api_is_global_platform_admin') && api_is_global_platform_admin()) {
            $roles[] = 'ROLE_GLOBAL_ADMIN';
        }

        if (function_exists('api_is_session_admin') && api_is_session_admin()) {
            $roles[] = 'ROLE_SESSION_MANAGER';
        }

        if (function_exists('api_is_drh') && api_is_drh()) {
            $roles[] = 'ROLE_HR';
        }

        if (function_exists('api_is_student_boss') && api_is_student_boss()) {
            $roles[] = 'ROLE_STUDENT_BOSS';
        }

        if (function_exists('api_is_teacher') && api_is_teacher()) {
            $roles[] = 'ROLE_TEACHER';
        }

        if (function_exists('api_is_student') && api_is_student()) {
            $roles[] = 'ROLE_STUDENT';
        }

        if (function_exists('api_is_invitee') && api_is_invitee()) {
            $roles[] = 'ROLE_INVITEE';
        }

        return array_values(array_unique($roles));
    }

    public static function currentUserCanAccessReport(array $report): bool
    {
        if (function_exists('api_is_global_platform_admin') && api_is_global_platform_admin()) {
            return true;
        }

        if (function_exists('api_is_platform_admin') && api_is_platform_admin()) {
            return true;
        }

        $allowedRoles = $report['roles'] ?? [];
        if (empty($allowedRoles)) {
            return false;
        }

        foreach (self::getCurrentUserRoles() as $role) {
            if (in_array($role, $allowedRoles, true)) {
                return true;
            }
        }

        return false;
    }

    public static function assertCurrentUserCanAccessReport(string $reportId): array
    {
        $report = self::findReport($reportId);

        if (null === $report) {
            api_not_allowed(true);
        }

        if (!self::currentUserCanAccessReport($report)) {
            api_not_allowed(true);
        }

        return $report;
    }

    public static function getReportAccessPolicySummary(array $report): string
    {
        $permission = (string) ($report['permission'] ?? '');
        $permissionCategories = self::getPermissionCategories();

        if ($permission && isset($permissionCategories[$permission])) {
            return (string) $permissionCategories[$permission]['label'];
        }

        return get_lang('Not classified');
    }

    public static function renderReportActionBar(
        string $currentReportId = '',
        string $backUrl = '',
        array $extraButtons = []
    ): string {
        $buttons = [];

        /*
         * Keep regular report pages focused on the current report.
         * The role matrix and permission category views are administrative
         * views of the reports catalog itself, so they remain available from
         * /main/admin/reports_catalog.php instead of being repeated on every
         * report page.
         */
        $buttons[] = Display::toolbarButton(
            get_lang('Reports catalog'),
            api_get_path(WEB_CODE_PATH).'admin/reports_catalog.php',
            'format-list-bulleted',
            'secondary-outline'
        );

        foreach ($extraButtons as $button) {
            if (!empty($button)) {
                $buttons[] = $button;
            }
        }

        if ($backUrl) {
            $buttons[] = Display::toolbarButton(
                get_lang('Back'),
                $backUrl,
                'arrow-left',
                'secondary-outline'
            );
        }

        return Display::toolbarAction(
            'report-action-bar'.($currentReportId ? '-'.Security::remove_XSS($currentReportId) : ''),
            [implode('', $buttons)]
        );
    }

    public static function roleIsAllowed(array $report, string $role): bool
    {
        return in_array($role, $report['roles'] ?? [], true);
    }

    public static function getRoleMatrix(): array
    {
        $roles = array_keys(self::getRoles());
        $matrix = [];

        foreach (self::getReports() as $report) {
            $row = [
                'report' => $report,
                'roles' => [],
            ];

            foreach ($roles as $role) {
                $row['roles'][$role] = self::roleIsAllowed($report, $role);
            }

            $matrix[] = $row;
        }

        return $matrix;
    }
}
