<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SESSION_MANAGER')")]
#[Route('/admin/index', name: 'admin_index_blocks')]
class IndexBlocksController extends BaseController
{
    private bool $isAdmin = false;
    private bool $isSessionAdmin = false;

    public function __construct(
        private TranslatorInterface $translator,
        private SettingsManager $settingsManager
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $this->isAdmin = $this->isGranted('ROLE_ADMIN');
        $this->isSessionAdmin = $this->isGranted('ROLE_SESSION_MANAGER');

        $json = [];
        $json['users'] = [
            'searchUrl' => $this->generateUrl('legacy_main', ['name' => 'admin/user_list.php']),
            'editable' => $this->isAdmin,
            'items' => $this->getItemsUsers(),
        ];

        if ($this->isAdmin) {
            $json['courses'] = [
                'searchUrl' => $this->generateUrl('legacy_main', ['name' => 'admin/course_list.php']),
                'editable' => true,
                'items' => $this->getItemsCourses(),
            ];

            $json['platform'] = [
                'searchUrl' => $this->generateUrl('chamilo_platform_settings_search'),
                'editable' => true,
                'items' => $this->getItemsPlatform(),
            ];

            /* Settings */
            $json['settings'] = [
                'editable' => false,
                'items' => $this->getItemsSettings(),
            ];

            // Skills
            if ('true' === $this->settingsManager->getSetting('skill.allow_skills_tool')) {
                $json['skills'] = [
                    'editable' => false,
                    'items' => $this->getItemsSkills(),
                ];
            }

            if ('true' === $this->settingsManager->getSetting('gradebook.gradebook_dependency')) {
                $json['gradebook'] = [
                    'editable' => false,
                    'items' => $this->getItemsGradebook(),
                ];
            }

            // Data protection
            if ('false' === $this->settingsManager->getSetting('profile.disable_gdpr')) {
                $json['data_privacy'] = [
                    'editable' => false,
                    'items' => $this->getItemsPrivacy(),
                ];
            }

            /* Chamilo.org */
            $json['chamilo'] = [
                'editable' => false,
                'items' => $this->getItemsChamilo(),
            ];
        }

        /* Sessions */
        $json['sessions'] = [
            'searchUrl' => $this->generateUrl('legacy_main', ['name' => 'session/session_list.php']),
            'editable' => $this->isAdmin,
            'items' => $this->getItemsSessions(),
        ];

        return $this->json($json);
    }

    private function getItemsUsers(): array
    {
        $items = [];
        $items[] = [
            'class' => 'item-user-list',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/user_list.php']),
            'label' => $this->translator->trans('User list'),
        ];
        $items[] = [
            'class' => 'item-user-add',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/user_add.php']),
            'label' => $this->translator->trans('Add a user'),
        ];

        if ($this->isAdmin) {
            $items[] = [
                'class' => 'item-user-export',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/user_export.php']),
                'label' => $this->translator->trans('Export users list'),
            ];
            $items[] = [
                'class' => 'item-user-import',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/user_import.php']),
                'label' => $this->translator->trans('Import users listImport users list'),
            ];
            $items[] = [
                'class' => 'item-user-import-update',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/user_update_import.php']),
                'label' => $this->translator->trans('Edit users list'),
            ];
            $items[] = [
                'class' => 'item-user-import-anonymize',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/user_anonymize_import.php']),
                'label' => $this->translator->trans('Anonymise users list'),
            ];

            if (isset($extAuthSource, $extAuthSource['extldap']) && \count($extAuthSource['extldap']) > 0) {
                $items[] = [
                    'class' => 'item-user-ldap-list',
                    'url' => $this->generateUrl('legacy_main', ['name' => 'admin/ldap_users_list.php']),
                    'label' => $this->translator->trans('Import LDAP users into the platform'),
                ];
            }

            $items[] = [
                'class' => 'item-user-field',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/extra_fields.php', 'type' => 'user']),
                'label' => $this->translator->trans('Profiling'),
            ];
            $items[] = [
                'class' => 'item-user-groups',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/usergroups.php']),
                'label' => $this->translator->trans('Classes'),
            ];

            if (api_get_configuration_value('show_link_request_hrm_user')) {
                $items[] = [
                    'class' => 'item-user-linking-requests',
                    'url' => $this->generateUrl('legacy_main', ['name' => 'admin/user_linking_requests.php']),
                    'label' => $this->translator->trans('Student linking requests'),
                ];
            }
        } else {
            $items[] = [
                'class' => 'item-user-import',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/user_import.php']),
                'label' => $this->translator->trans('Import users list'),
            ];
            $items[] = [
                'class' => 'item-user-groups',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/usergroups.php']),
                'label' => $this->translator->trans('Classes'),
            ];

            if ('true' === $this->settingsManager->getSetting('limit_session_admin_role')) {
                $items = array_filter($items, function (array $item) {
                    $urls = ['user_list.php', 'user_add.php'];

                    return \in_array($item['url'], $urls, true);
                });
            }

            if (true === api_get_configuration_value('limit_session_admin_list_users')) {
                $items = array_filter($items, function (array $item) {
                    $urls = ['user_list.php'];

                    return !\in_array($item['url'], $urls, true);
                });
            }

            if (api_get_configuration_value('allow_session_admin_extra_access')) {
                $items[] = [
                    'class' => 'item-user-import-update',
                    'url' => $this->generateUrl('legacy_main', ['name' => 'admin/user_update_import.php']),
                    'label' => $this->translator->trans('Edit users list'),
                ];
                $items[] = [
                    'class' => 'item-user-export',
                    'url' => $this->generateUrl('legacy_main', ['name' => 'admin/user_export.php']),
                    'label' => $this->translator->trans('Export users list'),
                ];
            }
        }

        return $items;
    }

    private function getItemsCourses(): array
    {
        $items = [];
        $items[] = [
            'class' => 'item-course-list',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/course_list.php']),
            'label' => $this->translator->trans('Course list'),
        ];
        $items[] = [
            'class' => 'item-course-add',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/course_add.php']),
            'label' => $this->translator->trans('Add course'),
        ];

        if ('true' === $this->settingsManager->getSetting('course.course_validation')) {
            $items[] = [
                'class' => 'item-course-request',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/course_request_review.php']),
                'label' => $this->translator->trans('Review incoming course requests'),
            ];
            $items[] = [
                'class' => 'item-course-request-accepted',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/course_request_accepted.php']),
                'label' => $this->translator->trans('Accepted course requests'),
            ];
            $items[] = [
                'class' => 'item-course-request-rejected',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/course_request_rejected.php']),
                'label' => $this->translator->trans('Rejected course requests'),
            ];
        }

        $items[] = [
            'class' => 'item-course-export',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/course_export.php']),
            'label' => $this->translator->trans('Export courses'),
        ];
        $items[] = [
            'class' => 'item-course-import',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/course_import.php']),
            'label' => $this->translator->trans('Import courses list'),
        ];
        $items[] = [
            'class' => 'item-course-category',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/course_category.php']),
            'label' => $this->translator->trans('Courses categories'),
        ];
        $items[] = [
            'class' => 'item-course-subscription',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/subscribe_user2course.php']),
            'label' => $this->translator->trans('Add a user to a course'),
        ];
        $items[] = [
            'class' => 'item-course-subscription-import',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/course_user_import.php']),
            'label' => $this->translator->trans('Import users list'),
        ];
        //$items[] = [
        //    'url'=>'course_intro_pdf_import.php',
        //    'label' => $this->translator->$this->trans('ImportPDFIntroToCourses'),
        //];

        if ('true' === $this->settingsManager->getSetting('gradebook.gradebook_enable_grade_model')) {
            $items[] = [
                'class' => 'item-grade-model',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/grade_models.php']),
                'label' => $this->translator->trans('Grading model'),
            ];
        }

        if (isset($extAuthSource, $extAuthSource['ldap']) && \count($extAuthSource['ldap']) > 0) {
            $items[] = [
                'class' => 'item-course-subscription-ldap',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/ldap_import_students.php']),
                'label' => $this->translator->trans('Import LDAP users into a course'),
            ];
        }

        $items[] = [
            'class' => 'item-course-field',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/extra_fields.php', 'type' => 'course']),
            'label' => $this->translator->trans('Manage extra fields for courses'),
        ];
        $items[] = [
            'class' => 'item-question-bank',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/questions.php']),
            'label' => $this->translator->trans('Questions'),
        ];

        return $items;
    }

    private function getItemsPlatform(): array
    {
        $items = [];
        $items[] = [
            'class' => 'item-setting-list',
            'url' => $this->generateUrl('admin_settings'),
            'label' => $this->translator->trans('Configuration settings'),
        ];
        $items[] = [
            'class' => 'item-language-list',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/languages.php']),
            'label' => $this->translator->trans('Languages'),
        ];
        $items[] = [
            'class' => 'item-plugin-list',
            'url' => $this->generateUrl(
                'legacy_main',
                ['name' => 'admin/settings.php', 'category' => 'Plugins']
            ),
            'label' => $this->translator->trans('Plugins'),
        ];
        $items[] = [
            'class' => 'item-region-list',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/settings.php', 'category' => 'Regions']),
            'label' => $this->translator->trans('Regions'),
        ];
        $items[] = [
            'class' => 'item-global-announcement',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/system_announcements.php']),
            'label' => $this->translator->trans('Portal news'),
        ];
        $items[] = [
            'class' => 'item-global-agenda',
            'url' => $this->generateUrl('legacy_main', ['name' => 'calendar/agenda_js.php', 'type' => 'admin']),
            'label' => $this->translator->trans('Global agenda'),
        ];

        if (true === api_get_configuration_value('agenda_reminders')) {
            $items[] = [
                'class' => 'item-agenda-reminders',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/import_course_agenda_reminders.php']),
                'label' => $this->translator->trans('Import course events'),
            ];
        }

        $items[] = [
            'class' => 'item-pages-list',
            'url' => '/resources/pages',
            'label' => $this->translator->trans('Pages'),
        ];
        $items[] = [
            'class' => 'item-registration-page',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/configure_inscription.php']),
            'label' => $this->translator->trans('Setting the registration page'),
        ];
        $items[] = [
            'class' => 'item-stats',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/statistics/index.php']),
            'label' => $this->translator->trans('Statistics'),
        ];
        $items[] = [
            'class' => 'item-stats-report',
            'url' => $this->generateUrl('legacy_main', ['name' => 'mySpace/company_reports.php']),
            'label' => $this->translator->trans('Reports'),
        ];
        $items[] = [
            'class' => 'item-teacher-time-report',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/teacher_time_report.php']),
            'label' => $this->translator->trans('Teachers time report'),
        ];

        if (api_get_configuration_value('chamilo_cms')) {
            $items[] = [
                'class' => 'item-cms',
                'url' => api_get_path(WEB_PATH).'web/app_dev.php/administration/dashboard',
                'label' => $this->translator->trans('CMS'),
            ];
        }

        /* Event settings */

        $items[] = [
            'class' => 'item-field',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/extra_field_list.php']),
            'label' => $this->translator->trans('Extra fields'),
        ];

        if (!empty(api_get_configuration_value('multiple_access_urls'))) {
            if (api_is_global_platform_admin()) {
                $items[] = [
                    'class' => 'item-access-url',
                    'url' => $this->generateUrl('legacy_main', ['name' => 'admin/access_urls.php']),
                    'label' => $this->translator->trans('Configure multiple access URL'),
                ];
            }
        }

        if ('true' === api_get_plugin_setting('dictionary', 'enable_plugin_dictionary')) {
            $items[] = [
                'class' => 'item-dictionary',
                'url' => api_get_path(WEB_PLUGIN_PATH).'dictionary/terms.php',
                'label' => $this->translator->trans('Dictionary'),
            ];
        }

        if ('true' === $this->settingsManager->getSetting('registration.allow_terms_conditions')) {
            $items[] = [
                'class' => 'item-terms-and-conditions',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/legal_add.php']),
                'label' => $this->translator->trans('Terms and Conditions'),
            ];
        }

        if ('true' === $this->settingsManager->getSetting('mail.mail_template_system')) {
            $items[] = [
                'class' => 'item-mail-template',
                'url' => $this->generateUrl('legacy_main', ['name' => 'mail_template/list.php']),
                'label' => $this->translator->trans('Mail templates'),
            ];
        }

        if (api_get_configuration_value('notification_event')) {
            $items[] = [
                'class' => 'item-notification-list',
                'url' => $this->generateUrl('legacy_main', ['name' => 'notification_event/list.php']),
                'label' => $this->translator->trans('Notifications'),
            ];
        }

        $allowJustification = 'true' === api_get_plugin_setting('justification', 'tool_enable');

        if ($allowJustification) {
            $items[] = [
                'class' => 'item-justification-list',
                'url' => api_get_path(WEB_PLUGIN_PATH).'justification/list.php',
                'label' => $this->translator->trans('Justification'),
            ];
        }

        $items[] = [
            'class' => 'item-lti-admin',
            'url' => $this->generateUrl('chamilo_lti_admin'),
            'label' => $this->translator->trans('External tools'),
        ];

        return $items;
    }

    private function getItemsSessions(): array
    {
        $items = [];
        $items[] = [
            'class' => 'item-session-list',
            'url' => $this->generateUrl('legacy_main', ['name' => 'session/session_list.php']),
            'label' => $this->translator->trans('Training sessions list'),
        ];
        $items[] = [
            'class' => 'item-session-add',
            'url' => $this->generateUrl('legacy_main', ['name' => 'session/session_add.php']),
            'label' => $this->translator->trans('Add a training session'),
        ];
        $items[] = [
            'class' => 'item-session-category',
            'url' => $this->generateUrl('legacy_main', ['name' => 'session/session_category_list.php']),
            'label' => $this->translator->trans('Sessions categories list'),
        ];
        $items[] = [
            'class' => 'item-session-import',
            'url' => $this->generateUrl('legacy_main', ['name' => 'session/session_import.php']),
            'label' => $this->translator->trans('Import sessions list'),
        ];
        $items[] = [
            'class' => 'item-session-import-hr',
            'url' => $this->generateUrl('legacy_main', ['name' => 'session/session_import_drh.php']),
            'label' => $this->translator->trans('Import list of HR directors into sessions'),
        ];
        if (isset($extAuthSource, $extAuthSource['ldap']) && \count($extAuthSource['ldap']) > 0) {
            $items[] = [
                'class' => 'item-session-subscription-ldap-import',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/ldap_import_students_to_session.php']),
                'label' => $this->translator->trans('Import LDAP users into a session'),
            ];
        }
        $items[] = [
            'class' => 'item-session-export',
            'url' => $this->generateUrl('legacy_main', ['name' => 'session/session_export.php']),
            'label' => $this->translator->trans('Export sessions list'),
        ];

        $items[] = [
            'class' => 'item-session-course-copy',
            'url' => $this->generateUrl('legacy_main', ['name' => 'coursecopy/copy_course_session.php']),
            'label' => $this->translator->trans('Copy from course in session to another session'),
        ];

        $allowCareer = $this->settingsManager->getSetting('session.allow_session_admin_read_careers');

        if ($this->isAdmin || ($allowCareer && $this->isSessionAdmin)) {
            // option only visible in development mode. Enable through code if required
            if (is_dir(api_get_path(SYS_TEST_PATH).'datafiller/')) {
                $items[] = [
                    'class' => 'item-session-user-move-stats',
                    'url' => $this->generateUrl('legacy_main', ['name' => 'admin/user_move_stats.php']),
                    'label' => $this->translator->trans('Move users results from/to a session'),
                ];
            }

            $items[] = [
                'class' => 'item-session-user-move',
                'url' => $this->generateUrl('legacy_main', ['name' => 'coursecopy/move_users_from_course_to_session.php']),
                'label' => $this->translator->trans('Move users results from base course to a session'),
            ];

            $items[] = [
                'class' => 'item-career-dashboard',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/career_dashboard.php']),
                'label' => $this->translator->trans('Careers and promotions'),
            ];
            $items[] = [
                'class' => 'item-session-field',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/extra_fields.php', 'type' => 'session']),
                'label' => $this->translator->trans('Manage session fields'),
            ];
            $items[] = [
                'class' => 'item-resource-sequence',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/resource_sequence.php']),
                'label' => $this->translator->trans('Resources sequencing'),
            ];
            $items[] = [
                'class' => 'item-export-exercise-results',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/export_exercise_results.php']),
                'label' => $this->translator->trans('Export all results from an exercise'),
            ];
        }

        return $items;
    }

    private function getItemsSettings(): array
    {
        $items = [];
        $items[] = [
            'class' => 'item-clean-cache',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/archive_cleanup.php']),
            'label' => $this->translator->trans('Cleanup of cache and temporary files'),
        ];

        $items[] = [
            'class' => 'item-special-export',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/special_exports.php']),
            'label' => $this->translator->trans('Special exports'),
        ];
        /*$items[] = [
            'url' => $this->>$this->generateUrl('legacy_main), ['name' => 'admin/periodic_export.php',
            'label' => $this->translator->$this->trans('Periodic export'),
        ];*/
        $items[] = [
            'class' => 'item-system-status',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/system_status.php']),
            'label' => $this->translator->trans('System status'),
        ];
        if (is_dir(api_get_path(SYS_TEST_PATH).'datafiller/')) {
            $items[] = [
                'class' => 'item-data-filler',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/filler.php']),
                'label' => $this->translator->trans('Data filler'),
            ];
        }

        if (is_dir(api_get_path(SYS_TEST_PATH))) {
            $items[] = [
                'class' => 'item-email-tester',
                'url' => $this->generateUrl('legacy_main', ['name' => 'admin/email_tester.php']),
                'label' => $this->translator->trans('E-mail tester'),
            ];
        }

        $items[] = [
            'class' => 'item-ticket-system',
            'url' => $this->generateUrl('legacy_main', ['name' => 'ticket/tickets.php']),
            'label' => $this->translator->trans('Tickets'),
        ];

        if (api_get_configuration_value('allow_session_status')) {
            $items[] = [
                'url' => $this->generateUrl('legacy_main', ['name' => 'session/cron_status.php']),
                'label' => $this->translator->trans('Update session status'),
            ];
        }

        return $items;
    }

    private function getItemsSkills(): array
    {
        $items = [];
        $items[] = [
            'class' => 'item-skill-wheel',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/skills_wheel.php']),
            'label' => $this->translator->trans('Skills wheel'),
        ];
        $items[] = [
            'class' => 'item-skill-import',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/skills_import.php']),
            'label' => $this->translator->trans('Skills import'),
        ];
        $items[] = [
            'class' => 'item-skill-list',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/skill_list.php']),
            'label' => $this->translator->trans('Manage skills'),
        ];
        $items[] = [
            'class' => 'item-skill-level',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/skill.php']),
            'label' => $this->translator->trans('Manage skills levels'),
        ];

        $items[] = [
            'class' => 'item-skill-ranking',
            'url' => $this->generateUrl('legacy_main', ['name' => 'social/skills_ranking.php']),
            'label' => $this->translator->trans('Skills ranking'),
        ];
        $items[] = [
            'class' => 'item-skill-gradebook',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/skills_gradebook.php']),
            'label' => $this->translator->trans('Skills and assessments'),
        ];
        /*$items[] = [
            'url' => $this->$this->generateUrl('legacy_main', ['name' => 'admin/skill_badge.php'),
            'label' => $this->translator->trans('Badges'),
        ];*/

        return $items;
    }

    private function getItemsGradebook(): array
    {
        $items = [];
        $items[] = [
            'class' => 'item-gradebook-list',
            'url' => $this->generateUrl('legacy_main', ['name' => 'gradebook_list.php']),
            'label' => $this->translator->trans('List'),
        ];

        return $items;
    }

    private function getItemsPrivacy(): array
    {
        $items = [];
        $items[] = [
            'class' => 'item-privacy-consent',
            'url' => $this->generateUrl('legacy_main', ['name' => 'admin/user_list_consent.php']),
            'label' => $this->translator->trans('User list'),
        ];

        return $items;
    }

    private function getItemsChamilo(): array
    {
        $languageInterface = 'english';

        $items = [];
        $items[] = [
            'class' => 'item-software-homepage',
            'url' => 'https://chamilo.org/',
            'label' => $this->translator->trans('Chamilo homepage'),
        ];

        // Custom linking to user guides in the existing languages
        $guideLinks = [
            'french' => 'v/1.11.x-fr/',
            'spanish' => 'v/1.11.x-es/',
            'dutch' => 'v/1.11.x-nl/',
            'galician' => 'v/1.11.x-ga/',
        ];

        $guideLink = 'https://docs.chamilo.org/';

        if (!empty($guideLinks[$languageInterface])) {
            $guideLink .= $guideLinks[$languageInterface];
        }

        $items[] = [
            'class' => 'item-user-guides',
            'url' => $guideLink,
            'label' => $this->translator->trans('User guides'),
        ];
        $items[] = [
            'class' => 'item-forum',
            'url' => $this->generateUrl('legacy_main', ['name' => 'https://forum.chamilo.org/']),
            'label' => $this->translator->trans('Chamilo forum'),
        ];
        $items[] = [
            'class' => 'item-installation-guide',
            'url' => $this->generateUrl('legacy_main', ['name' => 'documentation/installation_guide.html']),
            'label' => $this->translator->trans('Installation guide'),
        ];
        $items[] = [
            'class' => 'item-changelog',
            'url' => $this->generateUrl('legacy_main', ['name' => 'documentation/changelog.html']),
            'label' => $this->translator->trans('Changes in last version'),
        ];
        $items[] = [
            'class' => 'item-credits',
            'url' => $this->generateUrl('legacy_main', ['name' => 'documentation/credits.html']),
            'label' => $this->translator->trans('Contributors list'),
        ];
        $items[] = [
            'class' => 'item-security',
            'url' => $this->generateUrl('legacy_main', ['name' => 'documentation/security.html']),
            'label' => $this->translator->trans('Security guide'),
        ];
        $items[] = [
            'class' => 'item-optimization',
            'url' => $this->generateUrl('legacy_main', ['name' => 'documentation/optimization.html']),
            'label' => $this->translator->trans('Optimization guide'),
        ];
        $items[] = [
            'class' => 'item-extensions',
            'url' => 'https://chamilo.org/extensions',
            'label' => $this->translator->trans('Chamilo extensions'),
        ];
        $items[] = [
            'class' => 'item-providers',
            'url' => 'https://chamilo.org/providers',
            'label' => $this->translator->trans('Chamilo official services providers'),
        ];

        return $items;
    }
}
