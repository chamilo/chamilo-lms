<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Admin;

use AppPlugin;
use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\Page;
use Chamilo\CoreBundle\Entity\PageCategory;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\AdminBlockDisplayedEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\PageCategoryRepository;
use Chamilo\CoreBundle\Repository\PageRepository;
use Chamilo\CoreBundle\Repository\PluginRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Plugin;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_SESSION_MANAGER")'))]
#[Route('/admin/index', name: 'admin_index_blocks')]
class IndexBlocksController extends BaseController
{
    private bool $isAdmin = false;
    private bool $isSessionAdmin = false;
    private bool $isLdapActive;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly SettingsManager $settingsManager,
        private readonly PageRepository $pageRepository,
        private readonly PageCategoryRepository $pageCategoryRepository,
        private readonly SerializerInterface $serializer,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly PluginRepository $pluginRepository,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly AccessUrlRepository $accessUrlRepository,
        AuthenticationConfigHelper $authConfigHelper,
    ) {
        $this->isLdapActive = $authConfigHelper->getLdapConfig()['enabled'];
    }

    public function __invoke(): JsonResponse
    {
        $this->isAdmin = $this->isGranted('ROLE_ADMIN');
        $this->isSessionAdmin = $this->isGranted('ROLE_SESSION_MANAGER');

        $json = [];

        $adminBlockEvent = new AdminBlockDisplayedEvent($json, AbstractEvent::TYPE_PRE);

        $this->eventDispatcher->dispatch($adminBlockEvent, Events::ADMIN_BLOCK_DISPLAYED);

        $json = $adminBlockEvent->getData();

        $json['users'] = [
            'id' => 'block-admin-users',
            'searchUrl' => '/main/admin/user_list.php',
            'editable' => $this->isAdmin,
            'items' => $this->getItemsUsers(),
            'extraContent' => $this->getExtraContent('block-admin-users'),
        ];

        if ($this->isAdmin) {
            $json['courses'] = [
                'id' => 'block-admin-courses',
                'searchUrl' => '/main/admin/course_list.php',
                'editable' => true,
                'items' => $this->getItemsCourses(),
                'extraContent' => $this->getExtraContent('block-admin-courses'),
            ];

            $json['platform'] = [
                'id' => 'block-admin-platform',
                'searchUrl' => $this->generateUrl('chamilo_platform_settings_search'),
                'editable' => true,
                'items' => $this->getItemsPlatform(),
                'extraContent' => $this->getExtraContent('block-admin-platform'),
            ];

            /* Settings */
            $json['settings'] = [
                'id' => 'block-admin-settings',
                'editable' => false,
                'items' => $this->getItemsSettings(),
                'extraContent' => $this->getExtraContent('block-admin-settings'),
            ];

            // Skills
            if ('true' === $this->settingsManager->getSetting('skill.allow_skills_tool')) {
                $json['skills'] = [
                    'id' => 'block-admin-skills',
                    'editable' => false,
                    'items' => $this->getItemsSkills(),
                    'extraContent' => $this->getExtraContent('block-admin-skills'),
                ];
            }

            if ('true' === $this->settingsManager->getSetting('gradebook.gradebook_dependency')) {
                $json['gradebook'] = [
                    'id' => 'block-admin-gradebook',
                    'editable' => false,
                    'items' => $this->getItemsGradebook(),
                    'extraContent' => $this->getExtraContent('block-admin-gradebook'),
                ];
            }

            // Data protection
            if ('true' !== $this->settingsManager->getSetting('privacy.disable_gdpr')) {
                $json['data_privacy'] = [
                    'id' => 'block-admin-privacy',
                    'editable' => false,
                    'items' => $this->getItemsPrivacy(),
                    'extraContent' => $this->getExtraContent('block-admin-privacy'),
                ];
            }

            $json['security'] = [
                'id' => 'block-admin-security',
                'editable' => false,
                'items' => $this->getItemsSecurity(),
                'extraContent' => $this->getExtraContent('block-admin-security'),
            ];

            /* Chamilo.org */
            $json['chamilo'] = [
                'id' => 'block-admin-chamilo',
                'editable' => false,
                'items' => $this->getItemsChamilo(),
                'extraContent' => $this->getExtraContent('block-admin-chamilo'),
            ];

            $json['plugins'] = [
                'id' => 'block-admin-plugins',
                'editable' => false,
                'items' => $this->getItemsPlugins(),
            ];

            /* Health check */
            $json['health_check'] = [
                'id' => 'block-admin-health-check',
                'editable' => false,
                'items' => $this->getItemsHealthCheck(),
            ];
        }

        /* Sessions */
        $json['sessions'] = [
            'id' => 'block-admin-sessions',
            'searchUrl' => '/main/session/session_list.php',
            'editable' => $this->isAdmin,
            'items' => $this->getItemsSessions(),
            'extraContent' => $this->getExtraContent('block-admin-sessions'),
        ];

        $adminBlockEvent = new AdminBlockDisplayedEvent($json, AbstractEvent::TYPE_POST);

        $this->eventDispatcher->dispatch($adminBlockEvent, Events::ADMIN_BLOCK_DISPLAYED);

        $json = $adminBlockEvent->getData();

        return $this->json($json);
    }

    private function getItemsSecurity(): array
    {
        return [
            [
                'class' => 'item-security-login-attempts',
                'url' => $this->generateUrl('admin_security_login_attempts'),
                'label' => $this->translator->trans('Login attempts'),
            ],
        ];
    }

    private function getItemsUsers(): array
    {
        $items = [];
        $items[] = [
            'class' => 'item-user-list',
            'url' => '/main/admin/user_list.php',
            'label' => $this->translator->trans('User list'),
        ];
        $items[] = [
            'class' => 'item-user-add',
            'url' => '/main/admin/user_add.php',
            'label' => $this->translator->trans('Add a user'),
        ];

        if ($this->isAdmin) {
            $items[] = [
                'class' => 'item-user-export',
                'url' => '/main/admin/user_export.php',
                'label' => $this->translator->trans('Export users list'),
            ];
            $items[] = [
                'class' => 'item-user-import',
                'url' => '/main/admin/user_import.php',
                'label' => $this->translator->trans('Import users list'),
            ];
            $items[] = [
                'class' => 'item-user-import-update',
                'url' => '/main/admin/user_update_import.php',
                'label' => $this->translator->trans('Edit users list'),
            ];
            $items[] = [
                'class' => 'item-user-import-anonymize',
                'url' => '/main/admin/user_anonymize_import.php',
                'label' => $this->translator->trans('Anonymise users list'),
            ];

            if ($this->isLdapActive) {
                $items[] = [
                    'class' => 'item-user-ldap-list',
                    'url' => '/main/admin/ldap_users_list.php',
                    'label' => $this->translator->trans('Import LDAP users into the platform'),
                ];
            }

            $items[] = [
                'class' => 'item-user-field',
                'url' => '/main/admin/extra_fields.php?'.http_build_query(['type' => 'user']),
                'label' => $this->translator->trans('Profiling'),
            ];
            $items[] = [
                'class' => 'item-user-groups',
                'url' => '/main/admin/usergroups.php',
                'label' => $this->translator->trans('Classes'),
            ];

            if ('true' === $this->settingsManager->getSetting('admin.show_link_request_hrm_user')) {
                $items[] = [
                    'class' => 'item-user-linking-requests',
                    'url' => '/main/admin/user_linking_requests.php',
                    'label' => $this->translator->trans('Student linking requests'),
                ];
            }
        } else {
            $items[] = [
                'class' => 'item-user-import',
                'url' => '/main/admin/user_import.php',
                'label' => $this->translator->trans('Import users list'),
            ];
            $items[] = [
                'class' => 'item-user-groups',
                'url' => '/main/admin/usergroups.php',
                'label' => $this->translator->trans('Classes'),
            ];

            if ('true' === $this->settingsManager->getSetting('session.limit_session_admin_role')) {
                $items = array_filter($items, function (array $item) {
                    $urls = [
                        '/main/admin/user_list.php',
                        '/main/admin/user_add.php',
                    ];

                    return \in_array($item['url'], $urls, true);
                });
            }

            if ('true' === $this->settingsManager->getSetting('session.limit_session_admin_list_users')) {
                $items = array_filter($items, function (array $item): bool {
                    $urls = [
                        '/main/admin/user_list.php',
                    ];

                    return !\in_array($item['url'], $urls, true);
                });
            }

            if ('true' === $this->settingsManager->getSetting('session.allow_session_admin_extra_access')) {
                $items[] = [
                    'class' => 'item-user-import-update',
                    'url' => '/main/admin/user_update_import.php',
                    'label' => $this->translator->trans('Edit users list'),
                ];
                $items[] = [
                    'class' => 'item-user-export',
                    'url' => '/main/admin/user_export.php',
                    'label' => $this->translator->trans('Export users list'),
                ];
            }
        }

        return array_values($items);
    }

    private function getExtraContent(string $title): ?array
    {
        /** @var Page|null $page */
        $page = $this->pageRepository->findOneBy(['title' => $title]);

        $pageJsonld = $this->serializer->serialize($page, 'jsonld', ['groups' => ['adminblock:read']]);
        $pageArray = json_decode($pageJsonld, true);

        if ($page) {
            return $pageArray;
        }

        /** @var PageCategory $category */
        $category = $this->pageCategoryRepository->findOneBy(['title' => $title]);
        $categoryJsonld = $this->serializer->serialize($category, 'jsonld', ['groups' => ['page:read']]);
        $categoryArray = json_decode($categoryJsonld, true);

        if (empty($categoryArray)) {
            return [];
        }

        return [
            'category' => $categoryArray['@id'],
        ];
    }

    private function getItemsCourses(): array
    {
        $items = [];
        $items[] = [
            'class' => 'item-course-list',
            'url' => '/main/admin/course_list.php',
            'label' => $this->translator->trans('Course list'),
        ];
        $items[] = [
            'class' => 'item-course-add',
            'url' => '/main/admin/course_add.php',
            'label' => $this->translator->trans('Add course'),
        ];

        if ('true' === $this->settingsManager->getSetting('course.course_validation')) {
            $items[] = [
                'class' => 'item-course-request',
                'url' => '/main/admin/course_request_review.php',
                'label' => $this->translator->trans('Review incoming course requests'),
            ];
            $items[] = [
                'class' => 'item-course-request-accepted',
                'url' => '/main/admin/course_request_accepted.php',
                'label' => $this->translator->trans('Accepted course requests'),
            ];
            $items[] = [
                'class' => 'item-course-request-rejected',
                'url' => '/main/admin/course_request_rejected.php',
                'label' => $this->translator->trans('Rejected course requests'),
            ];
        }

        $items[] = [
            'class' => 'item-course-export',
            'url' => '/main/admin/course_export.php',
            'label' => $this->translator->trans('Export courses'),
        ];
        $items[] = [
            'class' => 'item-course-import',
            'url' => '/main/admin/course_import.php',
            'label' => $this->translator->trans('Import courses list'),
        ];
        $items[] = [
            'class' => 'item-course-category',
            'url' => '/main/admin/course_category.php',
            'label' => $this->translator->trans('Course categories'),
        ];
        $items[] = [
            'class' => 'item-course-subscription',
            'url' => '/main/admin/subscribe_user2course.php',
            'label' => $this->translator->trans('Add a user to a course'),
        ];
        $items[] = [
            'class' => 'item-course-subscription-import',
            'url' => '/main/admin/course_user_import.php',
            'label' => $this->translator->trans('Import users list'),
        ];
        // $items[] = [
        //    'url'=>'course_intro_pdf_import.php',
        //    'label' => $this->translator->$this->trans('Import PDF introductions into courses'),
        // ];

        if ('true' === $this->settingsManager->getSetting('gradebook.gradebook_enable_grade_model')) {
            $items[] = [
                'class' => 'item-grade-model',
                'url' => '/main/admin/grade_models.php',
                'label' => $this->translator->trans('Grading model'),
            ];
        }

        if ($this->isLdapActive) {
            $items[] = [
                'class' => 'item-course-subscription-ldap',
                'url' => '/main/admin/ldap_import_students.php',
                'label' => $this->translator->trans('Import LDAP users into a course'),
            ];
        }

        $items[] = [
            'class' => 'item-course-field',
            'url' => '/main/admin/extra_fields.php?'.http_build_query(['type' => 'course']),
            'label' => $this->translator->trans('Manage extra fields for courses'),
        ];
        $items[] = [
            'class' => 'item-question-bank',
            'url' => '/main/admin/questions.php',
            'label' => $this->translator->trans('Questions'),
        ];
        $items[] = [
            'class' => 'item-resource-sequence',
            'url' => '/main/admin/resource_sequence.php?'.http_build_query(['type' => SequenceResource::COURSE_TYPE]),
            'label' => $this->translator->trans('Resources sequencing'),
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
            'url' => '/main/admin/languages.php',
            'label' => $this->translator->trans('Languages'),
        ];
        $items[] = [
            'class' => 'item-plugin-list',
            'url' => '/main/admin/settings.php?'.http_build_query(['category' => 'Plugins']),
            'label' => $this->translator->trans('Plugins'),
        ];
        $items[] = [
            'class' => 'item-region-list',
            'url' => '/main/admin/settings.php?'.http_build_query(['category' => 'Regions']),
            'label' => $this->translator->trans('Regions'),
        ];
        $items[] = [
            'class' => 'item-global-announcement',
            'url' => '/main/admin/system_announcements.php',
            'label' => $this->translator->trans('Portal news'),
        ];
        $items[] = [
            'class' => 'item-global-agenda',
            'route' => ['name' => 'CCalendarEventList', 'query' => ['type' => 'global']],
            'label' => $this->translator->trans('Global agenda'),
        ];
        // Disabled until it is reemplemented to work with Chamilo 2
        /*
        $items[] = [
            'class' => 'item-agenda-reminders',
            'url' => '/main/admin/import_course_agenda_reminders.php',
            'label' => $this->translator->trans('Import course events'),
        ];
        */
        $items[] = [
            'class' => 'item-pages-list',
            'route' => ['name' => 'PageList'],
            'label' => $this->translator->trans('Pages'),
        ];
        /*
         * Disabled until differentiated with Pages, and reviewed - see GH#6404
        $items[] = [
            'class' => 'item-page-layouts',
            'route' => ['name' => 'PageLayoutList'],
            'label' => $this->translator->trans('Page layouts'),
        ];
        */
        $items[] = [
            'class' => 'item-registration-page',
            'url' => '/main/auth/registration.php?'.http_build_query(['create_intro_page' => 1]),
            'label' => $this->translator->trans('Setting the registration page'),
        ];
        $items[] = [
            'class' => 'item-stats',
            'url' => '/main/admin/statistics/index.php',
            'label' => $this->translator->trans('Statistics'),
        ];
        $items[] = [
            'class' => 'item-stats-report',
            'url' => '/main/my_space/company_reports.php',
            'label' => $this->translator->trans('Reports'),
        ];
        $items[] = [
            'class' => 'item-teacher-time-report',
            'url' => '/main/admin/teacher_time_report.php',
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
            'url' => '/main/admin/extra_field_list.php',
            'label' => $this->translator->trans('Extra fields'),
        ];

        if (api_is_global_platform_admin()) {
            $items[] = [
                'class' => 'item-access-url',
                'url' => '/main/admin/access_urls.php',
                'label' => $this->translator->trans('Configure multiple access URL'),
            ];
        }

        if ('true' === api_get_plugin_setting('dictionary', 'enable_plugin_dictionary')) {
            $items[] = [
                'class' => 'item-dictionary',
                'url' => api_get_path(WEB_PLUGIN_PATH).'Dictionary/terms.php',
                'label' => $this->translator->trans('Dictionary'),
            ];
        }

        if ('true' === $this->settingsManager->getSetting('registration.allow_terms_conditions', true)) {
            $items[] = [
                'class' => 'item-terms-and-conditions',
                'route' => ['name' => 'TermsConditionsList'],
                'label' => $this->translator->trans('Terms and Conditions'),
            ];
        }

        $items[] = [
            'class' => 'item-mail-template',
            'url' => '/main/mail_template/list.php',
            'label' => $this->translator->trans('Mail templates'),
        ];

        if ('true' === api_get_setting('platform.notification_event')) {
            $items[] = [
                'class' => 'item-notification-list',
                'url' => '/main/notification_event/list.php',
                'label' => $this->translator->trans('Notifications'),
            ];
        }

        $allowJustification = 'true' === api_get_plugin_setting('justification', 'tool_enable');

        if ($allowJustification) {
            $items[] = [
                'class' => 'item-justification-list',
                'url' => api_get_path(WEB_PLUGIN_PATH).'Justification/list.php',
                'label' => $this->translator->trans('Justification'),
            ];
        }

        $items[] = [
            'class' => 'item-lti-admin',
            'url' => $this->generateUrl('chamilo_lti_admin'),
            'label' => $this->translator->trans('External tools (LTI)'),
        ];

        $items[] = [
            'class' => 'item-contact-category-admin',
            'url' => $this->generateUrl('chamilo_contact_category_index'),
            'label' => $this->translator->trans('Contact form categories'),
        ];

        $items[] = [
            'class' => 'item-system-template-admin',
            'url' => '/main/admin/settings.php?'.http_build_query(['category' => 'Templates']),
            'label' => $this->translator->trans('System templates'),
        ];

        return $items;
    }

    private function getItemsSettings(): array
    {
        $items = [];
        $items[] = [
            'class' => 'item-cleanup-temp-uploads',
            'url' => '/admin/cleanup-temp-uploads',
            'label' => $this->translator->trans('Clean temporary files'),
        ];

        $items[] = [
            'class' => 'item-special-export',
            'url' => '/main/admin/special_exports.php',
            'label' => $this->translator->trans('Special exports'),
        ];
        /*$items[] = [
            'url' => '/main/admin/periodic_export.php',
            'label' => $this->translator->$this->trans('Periodic export'),
        ];*/
        $items[] = [
            'class' => 'item-system-status',
            'url' => '/main/admin/system_status.php',
            'label' => $this->translator->trans('System status'),
        ];
        if (is_dir(api_get_path(SYS_TEST_PATH).'datafiller/')) {
            $items[] = [
                'class' => 'item-data-filler',
                'url' => '/main/admin/filler.php',
                'label' => $this->translator->trans('Data filler'),
            ];
        }

        if (is_dir(api_get_path(SYS_TEST_PATH))) {
            $items[] = [
                'class' => 'item-email-tester',
                'url' => '/admin/email_tester',
                'label' => $this->translator->trans('E-mail tester'),
            ];
        }

        $items[] = [
            'class' => 'item-ticket-system',
            'url' => '/main/ticket/tickets.php',
            'label' => $this->translator->trans('Tickets'),
        ];

        // Disabled until it is reemplemented to work with Chamilo 2
        /*
        $items[] = [
            'url' => '/main/session/cron_status.php',
            'label' => $this->translator->trans('Update session status'),
        ];
        */
        $items[] = [
            'class' => 'item-colors',
            'route' => ['name' => 'AdminConfigurationColors'],
            'label' => $this->translator->trans('Colors'),
        ];

        $items[] = [
            'class' => 'item-file-info',
            'url' => '/admin/files_info',
            'label' => $this->translator->trans('File info'),
        ];

        $items[] = [
            'class' => 'item-resources-info',
            'url' => '/admin/resources_info',
            'label' => $this->translator->trans('Resources by type'),
        ];

        return $items;
    }

    private function getItemsSkills(): array
    {
        $items = [];
        $items[] = [
            'class' => 'item-skill-wheel',
            'route' => ['name' => 'SkillWheel'],
            'label' => $this->translator->trans('Skills wheel'),
        ];
        $items[] = [
            'class' => 'item-skill-import',
            'url' => '/main/skills/skills_import.php',
            'label' => $this->translator->trans('Skills import'),
        ];
        $items[] = [
            'class' => 'item-skill-list',
            'url' => '/main/skills/skill_list.php',
            'label' => $this->translator->trans('Manage skills'),
        ];
        $items[] = [
            'class' => 'item-skill-level',
            'url' => '/main/skills/skill.php',
            'label' => $this->translator->trans('Manage skills levels'),
        ];

        $items[] = [
            'class' => 'item-skill-ranking',
            'url' => '/main/social/skills_ranking.php?origin=admin',
            'label' => $this->translator->trans('Skills ranking'),
        ];
        $items[] = [
            'class' => 'item-skill-gradebook',
            'url' => '/main/skills/skills_gradebook.php',
            'label' => $this->translator->trans('Skills and assessments'),
        ];

        /*$items[] = [
            'url' => '/main/admin/skill_badge.php',
            'label' => $this->translator->trans('Badges'),
        ];*/

        return $items;
    }

    private function getItemsGradebook(): array
    {
        $items = [];
        $items[] = [
            'class' => 'item-gradebook-list',
            'url' => '/main/admin/gradebook_list.php',
            'label' => $this->translator->trans('List'),
        ];

        return $items;
    }

    private function getItemsPrivacy(): array
    {
        $items = [];
        $items[] = [
            'class' => 'item-privacy-consent',
            'url' => '/main/admin/user_list_consent.php',
            'label' => $this->translator->trans('User list'),
        ];
        $items[] = [
            'class' => 'item-gdpr-parties',
            'route' => ['name' => 'ThirdPartyManager'],
            'label' => $this->translator->trans('Third parties (GDPR)'),
        ];

        return $items;
    }

    private function getItemsChamilo(): array
    {
        $languageInterface = api_get_language_isocode();

        $items = [];
        $items[] = [
            'class' => 'item-software-homepage',
            'url' => 'https://chamilo.org/',
            'label' => $this->translator->trans('Chamilo homepage'),
        ];

        // Custom linking to user guides in the existing languages
        /*$guideLinks = [
            'french' => 'v/1.11.x-fr/',
            'spanish' => 'v/1.11.x-es/',
            'dutch' => 'v/1.11.x-nl/',
            'galician' => 'v/1.11.x-ga/',
        ];*/

        $guideLink = 'https://docs.chamilo.org/';

        /*if (!empty($guideLinks[$languageInterface])) {
            $guideLink .= $guideLinks[$languageInterface];
        }*/

        $items[] = [
            'class' => 'item-user-guides',
            'url' => $guideLink,
            'label' => $this->translator->trans('User guides'),
        ];
        $items[] = [
            'class' => 'item-forum',
            'url' => 'https://github.com/chamilo/chamilo-lms/discussions/',
            'label' => $this->translator->trans('Chamilo forum'),
        ];
        $items[] = [
            'class' => 'item-installation-guide',
            'url' => '/documentation/installation_guide.html',
            'label' => $this->translator->trans('Installation guide'),
        ];
        $items[] = [
            'class' => 'item-changelog',
            'url' => '/documentation/changelog.html',
            'label' => $this->translator->trans('Changes in last version'),
        ];
        $items[] = [
            'class' => 'item-credits',
            'url' => '/documentation/credits.html',
            'label' => $this->translator->trans('Contributors list'),
        ];
        $items[] = [
            'class' => 'item-security',
            'url' => '/documentation/security.html',
            'label' => $this->translator->trans('Security guide'),
        ];
        $items[] = [
            'class' => 'item-optimization',
            'url' => '/documentation/optimization.html',
            'label' => $this->translator->trans('Optimization guide'),
        ];
        /*
        $items[] = [
            'class' => 'item-extensions',
            'url' => 'https://chamilo.org/extensions',
            'label' => $this->translator->trans('Chamilo extensions'),
        ];
        */
        $items[] = [
            'class' => 'item-providers',
            'url' => 'https://chamilo.org/providers',
            'label' => $this->translator->trans('Chamilo official services providers'),
        ];

        return $items;
    }

    private function getItemsSessions(): array
    {
        $items = [];
        $items[] = [
            'class' => 'item-session-list',
            'url' => '/main/session/session_list.php',
            'label' => $this->translator->trans('Training sessions list'),
        ];
        $items[] = [
            'class' => 'item-session-add',
            'url' => '/main/session/session_add.php',
            'label' => $this->translator->trans('Add a training session'),
        ];
        $items[] = [
            'class' => 'item-session-category',
            'url' => '/main/session/session_category_list.php',
            'label' => $this->translator->trans('Sessions categories list'),
        ];
        $items[] = [
            'class' => 'item-session-import',
            'url' => '/main/session/session_import.php',
            'label' => $this->translator->trans('Import sessions list'),
        ];
        $items[] = [
            'class' => 'item-session-import-hr',
            'url' => '/main/session/session_import_drh.php',
            'label' => $this->translator->trans('Import list of HR directors into sessions'),
        ];

        if ($this->isLdapActive) {
            $items[] = [
                'class' => 'item-session-subscription-ldap-import',
                'url' => '/main/admin/ldap_import_students_to_session.php',
                'label' => $this->translator->trans('Import LDAP users into a session'),
            ];
        }

        $items[] = [
            'class' => 'item-session-export',
            'url' => '/main/session/session_export.php',
            'label' => $this->translator->trans('Export sessions list'),
        ];

        $items[] = [
            'class' => 'item-session-course-copy',
            'url' => '/main/course_copy/copy_course_session.php',
            'label' => $this->translator->trans('Copy from course in session to another session'),
        ];

        $allowCareer = $this->settingsManager->getSetting('session.allow_session_admin_read_careers');

        if ($this->isAdmin || ('true' === $allowCareer && $this->isSessionAdmin)) {
            // Disabled until it is reemplemented to work with Chamilo 2
            /*                $items[] = [
                                'class' => 'item-session-user-move-stats',
                                'url' => '/main/admin/user_move_stats.php',
                                'label' => $this->translator->trans('Move users results from/to a session'),
                            ];
            $items[] = [
                'class' => 'item-session-user-move',
                'url' => '/main/coursecopy/move_users_from_course_to_session.php',
                'label' => $this->translator->trans('Move users results from base course to a session'),
            ];
             */

            $items[] = [
                'class' => 'item-career-dashboard',
                'url' => '/main/admin/career_dashboard.php',
                'label' => $this->translator->trans('Careers and promotions'),
            ];
            $items[] = [
                'class' => 'item-session-field',
                'url' => '/main/admin/extra_fields.php?'.http_build_query(['type' => 'session']),
                'label' => $this->translator->trans('Manage session fields'),
            ];
            $items[] = [
                'class' => 'item-resource-sequence',
                'url' => '/main/admin/resource_sequence.php?'.http_build_query(['type' => SequenceResource::SESSION_TYPE]),
                'label' => $this->translator->trans('Resources sequencing'),
            ];
            $items[] = [
                'class' => 'item-export-exercise-results',
                'url' => '/main/admin/export_exercise_results.php',
                'label' => $this->translator->trans('Export all results from an exercise'),
            ];
        }

        return $items;
    }

    private function getItemsPlugins(): array
    {
        $items = [];

        $accessUrl = $this->accessUrlHelper->getCurrent();
        $appPlugin = new AppPlugin();
        $plugins = $this->pluginRepository->getInstalledPlugins();

        foreach ($plugins as $plugin) {
            // getPluginInfo() might fail to build the plugin object; never assume 'obj' exists
            $pluginInfo = $appPlugin->getPluginInfo($plugin->getTitle());

            // Normalize/fallbacks
            if (!\is_array($pluginInfo)) {
                // Defensive: unexpected structure → skip
                error_log(\sprintf('[admin:index] Plugin "%s" has no pluginInfo array, skipping.', $plugin->getTitle()));

                continue;
            }

            /** @var Plugin|null $objPlugin */
            $objPlugin = $pluginInfo['obj'] ?? null;

            if (!$objPlugin instanceof Plugin) {
                // Defensive: plugin could not be instantiated (e.g. throws in constructor)
                error_log(\sprintf('[admin:index] Plugin "%s" has no valid "obj" (instance of Plugin), skipping.', $plugin->getTitle()));

                continue;
            }

            // Per-URL configuration
            $pluginInUrl = $plugin->getOrCreatePluginConfiguration($accessUrl);
            $configuration = $pluginInUrl->getConfiguration() ?: [];

            if (!$configuration || !isset($configuration['regions'])) {
                continue;
            }

            // Only show plugins that declare the admin menu region
            if (!\in_array('menu_administrator', $configuration['regions'], true)) {
                continue;
            }

            // Build admin URL defensively (some plugins may throw when building URLs)
            try {
                $adminUrl = $objPlugin->getAdminUrl();
            } catch (Throwable $e) {
                error_log(\sprintf('[admin:index] Plugin "%s" getAdminUrl() failed: %s', $plugin->getTitle(), $e->getMessage()));

                continue;
            }

            // Label fallback to DB title if pluginInfo misses 'title'
            $label = (string) ($pluginInfo['title'] ?? $plugin->getTitle());

            $items[] = [
                'class' => 'item-plugin-'.strtolower($plugin->getTitle()),
                'url' => $adminUrl,
                'label' => $label,
            ];
        }

        return $items;
    }

    private function getItemsHealthCheck(): array
    {
        $items = [];

        // Check if dsn or email is defined :
        $mailDsn = $this->settingsManager->getSetting('mail.mailer_dsn', true);
        $mailSender = $this->settingsManager->getSetting('mail.mailer_from_email', true);
        $nameSender = $this->settingsManager->getSetting('mail.mailer_from_name', true);
        if ((empty($mailDsn) || 'null://null' == $mailDsn) || empty($mailSender) || empty($nameSender)) {
            $items[] = [
                'className' => 'item-health-check-mail-settings text-error',
                'url' => '/admin/settings/mail',
                'label' => $this->translator->trans('E-mail settings need to be configured'),
            ];
        } else {
            $items[] = [
                'className' => 'item-health-check-mail-settings text-success',
                'url' => '/admin/settings/mail',
                'label' => $this->translator->trans('E-mail settings are OK'),
            ];
        }

        // Check if the admin user has access to all URLs
        if (api_is_admin_in_all_active_urls()) {
            $items[] = [
                'className' => 'item-health-check-admin-urls text-success',
                'url' => '/main/admin/access_urls.php',
                'label' => $this->translator->trans('All URLs have at least one admin assigned'),
            ];
        } else {
            $items[] = [
                'className' => 'item-health-check-admin-urls text-error',
                'url' => '/main/admin/access_url_edit_users_to_url.php',
                'label' => $this->translator->trans('At least one URL has no admin assigned'),
            ];
        }

        // ---------------------------------------------------------------------
        // File permissions checks
        // ---------------------------------------------------------------------
        $projectDir = (string) $this->getParameter('kernel.project_dir');

        // Help links (optional but avoids null URLs)
        $securityGuideUrl = '/documentation/security.html';
        $optimizationGuideUrl = '/documentation/optimization.html';

        // .env should NOT be writable by the web server user
        $envPath = $projectDir.'/.env';
        $envIsWritable = is_file($envPath) && is_writable($envPath);

        $items[] = [
            'className' => 'item-health-check-env-perms '.($envIsWritable ? 'text-error' : 'text-success'),
            'url' => $securityGuideUrl,
            'label' => \sprintf(
                $this->translator->trans($envIsWritable ? '%s is writeable' : '%s is not writeable'),
                '.env'
            ),
        ];

        // config/ should NOT be writable by the web server user
        $configPath = $projectDir.'/config';
        $configIsWritable = is_dir($configPath) && is_writable($configPath);

        $items[] = [
            'className' => 'item-health-check-config-perms '.($configIsWritable ? 'text-error' : 'text-success'),
            'url' => $securityGuideUrl,
            'label' => \sprintf(
                $this->translator->trans($configIsWritable ? '%s is writeable' : '%s is not writeable'),
                'config/'
            ),
        ];

        // var/cache MUST be writable (Symfony cache)
        $cachePath = $projectDir.'/var/cache';
        $cacheIsWritable = is_dir($cachePath) && is_writable($cachePath);

        $items[] = [
            'className' => 'item-health-check-cache-perms '.($cacheIsWritable ? 'text-success' : 'text-error'),
            'url' => $optimizationGuideUrl,
            'label' => \sprintf(
                $this->translator->trans($cacheIsWritable ? '%s is writeable' : '%s is not writeable'),
                'var/cache'
            ),
        ];

        // public/main/install existence -> orange if present
        $installPath = $projectDir.'/public/main/install';
        $installExists = is_dir($installPath);

        $items[] = [
            'className' => 'item-health-check-install-folder '.($installExists ? 'text-warning' : 'text-success'),
            'url' => $securityGuideUrl,
            'label' => $this->translator->trans($installExists ? 'Install folder is still present' : 'Install folder is not present'),
        ];

        return $items;
    }
}
