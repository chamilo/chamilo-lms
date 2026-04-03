<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Bbb;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CoreBundle\Helpers\ThemeHelper;
use Chamilo\CoreBundle\Helpers\TicketProjectHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/platform-config')]
class PlatformConfigurationController extends AbstractController
{
    use ControllerTrait;

    public function __construct(
        private readonly TicketProjectHelper $ticketProjectHelper,
        private readonly UserHelper $userHelper,
        private readonly ThemeHelper $themeHelper,
        private readonly PluginHelper $pluginHelper,
    ) {}

    #[Route('/list', name: 'platform_config_list', methods: ['GET'])]
    public function list(
        SettingsManager $settingsManager,
        AuthenticationConfigHelper $authenticationConfigHelper,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $requestSession = $this->getRequest()->getSession();

        $enabledOAuthProviders = $authenticationConfigHelper->getEnabledOAuthProviders();
        $forcedLoginMethod = $authenticationConfigHelper->getForcedLoginMethod();

        if ($forcedLoginMethod) {
            if (\array_key_exists($forcedLoginMethod, $enabledOAuthProviders)) {
                $enabledOAuthProviders = [$forcedLoginMethod => $enabledOAuthProviders[$forcedLoginMethod]];
            } else {
                $enabledOAuthProviders = [];
            }
        }

        $oauth2Providers = [];
        foreach ($enabledOAuthProviders as $providerName => $providerParams) {
            $oauth2Providers[] = [
                'name' => $providerName,
                'title' => $providerParams['title'] ?? ucwords($providerName),
                'url' => $urlGenerator->generate(\sprintf('chamilo.oauth2_%s_start', $providerName)),
            ];
        }

        $configuration = [
            'settings' => [],
            'studentview' => $requestSession->get('studentview'),
            'plugins' => [],
            'visual_theme' => $this->themeHelper->getVisualTheme(),
            'oauth2_providers' => $oauth2Providers,
            'ldap_auth' => null,
            'forced_login_method' => $forcedLoginMethod,
        ];

        $ldapConfig = $authenticationConfigHelper->getLdapConfig();

        if ($ldapConfig['enabled'] && \in_array($forcedLoginMethod, ['ldap', null], true)) {
            $configuration['ldap_auth'] = [
                'enabled' => true,
                'title' => $ldapConfig['title'],
            ];
        }

        $configuration['settings']['registration.allow_registration'] = $settingsManager->getSetting('registration.allow_registration', true);
        $configuration['settings']['catalog.course_catalog_published'] = $settingsManager->getSetting('catalog.course_catalog_published', true);
        $configuration['settings']['catalog.hide_public_link'] = $settingsManager->getSetting('catalog.hide_public_link', true);
        $configuration['settings']['catalog.course_catalog_display_in_home'] = $settingsManager->getSetting('catalog.course_catalog_display_in_home', true);
        $configuration['settings']['catalog.only_show_course_from_selected_category'] = $settingsManager->getSetting('catalog.only_show_course_from_selected_category', true);
        $configuration['settings']['catalog.allow_students_to_browse_courses'] = $settingsManager->getSetting('catalog.allow_students_to_browse_courses', true);
        $configuration['settings']['catalog.allow_session_auto_subscription'] = $settingsManager->getSetting('catalog.allow_session_auto_subscription', true);
        $configuration['settings']['catalog.course_subscription_in_user_s_session'] = $settingsManager->getSetting('catalog.course_subscription_in_user_s_session', true);
        $configuration['settings']['catalog.course_catalog_settings'] = $this->decodeSetting($settingsManager->getSetting('catalog.course_catalog_settings', true));
        $configuration['settings']['catalog.session_catalog_settings'] = $this->decodeSetting($settingsManager->getSetting('catalog.session_catalog_settings', true));
        $configuration['settings']['admin.chamilo_latest_news'] = $settingsManager->getSetting('admin.chamilo_latest_news', true);
        $configuration['settings']['admin.chamilo_support'] = $settingsManager->getSetting('admin.chamilo_support', true);
        $configuration['settings']['platform.session_admin_access_to_all_users_on_all_urls'] = $settingsManager->getSetting('platform.session_admin_access_to_all_users_on_all_urls', true);
        $configuration['settings']['profile.login_is_email'] = $settingsManager->getSetting('profile.login_is_email', true);
        $configuration['settings']['platform.timepicker_increment'] = (int) $settingsManager->getSetting('platform.timepicker_increment', true);
        $configuration['settings']['course.course_student_info'] = $this->decodeSetting($settingsManager->getSetting('course.course_student_info', true));

        $configuration['plugins']['buycourses'] = $this->getBuyCoursesFrontendConfig();
        $configuration['plugins']['tour'] = $this->getTourFrontendConfig();

        if ($this->isGranted('ROLE_USER')) {
            $variables = [
                'platform.site_name',
                'platform.timezone',
                'platform.registered',
                'platform.donotlistcampus',
                'workflows.load_term_conditions_section',
                'platform.cookie_warning',
                'display.show_tabs',
                'catalog.show_courses_sessions',
                'admin.administrator_name',
                'admin.administrator_surname',
                'editor.enabled_mathjax',
                'editor.translate_html',
                'display.show_admin_toolbar',
                'registration.allow_terms_conditions',
                'agenda.allow_personal_agenda',
                'agenda.personal_calendar_show_sessions_occupation',
                'social.social_enable_messages_feedback',
                'social.disable_dislike_option',
                'skill.allow_skills_tool',
                'gradebook.gradebook_enable_grade_model',
                'gradebook.gradebook_dependency',
                'course.course_validation',
                'course.student_view_enabled',
                'session.allow_edit_tool_visibility_in_session',
                'session.limit_session_admin_role',
                'session.allow_session_admin_read_careers',
                'session.limit_session_admin_list_users',
                'workflows.redirect_index_to_url_for_logged_users',
                'language.platform_language',
                'language.language_priority_1',
                'language.language_priority_2',
                'language.language_priority_3',
                'language.language_priority_4',
                'profile.allow_social_map_fields',
                'forum.global_forums_course_id',
                'document.students_download_folders',
                'social.hide_social_groups_block',
                'course.show_course_duration',
                'attendance.attendance_allow_comments',
                'attendance.multilevel_grading',
                'attendance.enable_sign_attendance_sheet',
                'exercise.allow_exercise_auto_launch',
                'document.access_url_specific_files',
                'catalog.show_courses_descriptions_in_catalog',
                'session.session_automatic_creation_user_id',
                'session.session_list_view_remaining_days',
                'profile.use_users_timezone',
                'registration.redirect_after_login',
                'display.show_tabs_per_role',
                'workflows.session_admin_user_subscription_search_extra_field_to_search',
                'platform.push_notification_settings',
                'session.user_session_display_mode',
                'course.resource_sequence_show_dependency_in_course_intro',
                'message.allow_message_tool',
                'lp.hide_scorm_export_link',
                'ai_helpers.enable_ai_helpers',
                'lp.hide_scorm_pdf_link',
                'display.table_default_row',
                'display.table_row_list',
                'social.allow_social_tool',
                'chat.allow_global_chat',
                'survey.show_pending_survey_in_menu',
                'search.search_enabled',
                'search.search_prefilter_prefix',
                'search.search_show_unlinked_results',
                'certificate.allow_general_certificate',
                'language.show_different_course_language',
                'workflows.allow_users_to_create_courses',
                'work.allow_only_one_student_publication_per_user',
                'course.course_creation_form_hide_course_code',
                'course.course_creation_form_set_course_category_mandatory',
                'display.hide_logout_button',
            ];

            foreach ($variables as $variable) {
                $configuration['settings'][$variable] = $settingsManager->getSetting($variable, true);
            }

            $user = $this->userHelper->getCurrent();

            $configuration['settings']['ticket.show_link_ticket_notification'] = 'false';

            if (!empty($user)) {
                $userIsAllowedInProject = $this->ticketProjectHelper->userIsAllowInProject(1);

                if ($userIsAllowedInProject
                    && 'true' === $settingsManager->getSetting('ticket.show_link_ticket_notification')
                ) {
                    $configuration['settings']['ticket.show_link_ticket_notification'] = 'true';
                }
            }

            $configuration['plugins']['bbb'] = [
                'show_global_conference_link' => Bbb::showGlobalConferenceLink([
                    'username' => $user->getUserIdentifier(),
                    'status' => $user->getStatus(),
                ]),
                'listingURL' => (new Bbb('', '', true, $user->getId()))->getListingUrl(),
            ];

            $configuration['plugins']['onlyoffice'] = $this->getOnlyofficeFrontendConfig();
        }

        return new JsonResponse($configuration);
    }

    #[Route('/list/course_settings', name: 'course_settings_list', methods: ['GET'])]
    public function courseSettingsList(
        SettingsCourseManager $courseSettingsManager,
        CourseRepository $courseRepository,
        Request $request
    ): JsonResponse {
        $courseId = $request->query->get('cid');
        if (!$courseId) {
            return new JsonResponse(['error' => 'Course ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $course = $courseRepository->find($courseId);
        if (!$course) {
            return new JsonResponse(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }

        $courseSettingsManager->setCourse($course);
        $settings = [
            'show_course_in_user_language' => $courseSettingsManager->getCourseSettingValue('show_course_in_user_language'),
            'allow_user_edit_agenda' => $courseSettingsManager->getCourseSettingValue('allow_user_edit_agenda'),
            'enable_document_auto_launch' => $courseSettingsManager->getCourseSettingValue('enable_document_auto_launch'),
            'enable_exercise_auto_launch' => $courseSettingsManager->getCourseSettingValue('enable_exercise_auto_launch'),
            'enable_lp_auto_launch' => $courseSettingsManager->getCourseSettingValue('enable_lp_auto_launch'),
            'enable_forum_auto_launch' => $courseSettingsManager->getCourseSettingValue('enable_forum_auto_launch'),
            'learning_path_generator' => $courseSettingsManager->getCourseSettingValue('learning_path_generator'),
            'image_generator' => $courseSettingsManager->getCourseSettingValue('image_generator'),
            'video_generator' => $courseSettingsManager->getCourseSettingValue('video_generator'),
            'glossary_terms_generator' => $courseSettingsManager->getCourseSettingValue('glossary_terms_generator'),
            'task_grader' => $courseSettingsManager->getCourseSettingValue('task_grader'),
            'content_analyzer' => $courseSettingsManager->getCourseSettingValue('content_analyzer'),
            'display_info_advance_inside_homecourse' => $courseSettingsManager->getCourseSettingValue('display_info_advance_inside_homecourse'),
        ];

        return new JsonResponse(['settings' => $settings]);
    }

    /**
     * Decodes a setting stored as a JSON string or native array.
     * Returns the string 'false' unchanged (used as a sentinel by the settings system).
     */
    private function decodeSetting(mixed $setting): mixed
    {
        if ('false' === $setting) {
            return 'false';
        }

        if (\is_array($setting)) {
            return $setting;
        }

        if (\is_string($setting)) {
            $json = json_decode($setting, true);

            if (\is_array($json)) {
                return $json;
            }
        }

        return [];
    }

    private function getOnlyofficeFrontendConfig(): array
    {
        $enabled = $this->pluginHelper->isPluginEnabled('Onlyoffice');

        $documentServerUrl = (string) $this->pluginHelper->getPluginConfigValue(
            'Onlyoffice',
            'document_server_url',
            ''
        );

        $jwtSecret = (string) $this->pluginHelper->getPluginConfigValue(
            'Onlyoffice',
            'jwt_secret',
            ''
        );

        $demoData = $this->pluginHelper->getPluginConfigValue(
            'Onlyoffice',
            'onlyoffice_connect_demo_data',
            null
        );

        $demoEnabled = false;

        if (\is_string($demoData) && '' !== trim($demoData)) {
            $decodedDemo = json_decode($demoData, true);
            if (\is_array($decodedDemo)) {
                $demoEnabled = !empty($decodedDemo['enabled']);
            }
        } elseif (\is_array($demoData)) {
            $demoEnabled = !empty($demoData['enabled']);
        }

        $configured = $demoEnabled || (
                '' !== trim($documentServerUrl)
                && '' !== trim($jwtSecret)
            );

        return [
            'enabled' => $enabled,
            'configured' => $configured,
            'editorPath' => '/plugin/Onlyoffice/editor.php',
        ];
    }

    private function getTourFrontendConfig(): array
    {
        $enabled = $this->pluginHelper->isPluginEnabled('Tour');

        $showTour = $enabled && $this->normalizePluginBoolean(
                $this->pluginHelper->getPluginConfigValue('Tour', 'show_tour', true)
            );

        $theme = trim((string) $this->pluginHelper->getPluginConfigValue('Tour', 'theme', ''));
        $themeCssPath = null;

        if ('' !== $theme) {
            $themeCssPath = '/plugin/Tour/intro.js/introjs-'.$theme.'.css';
        }

        return [
            'enabled' => $enabled,
            'showTour' => $showTour,
            'theme' => $theme,
            'introCss' => '/plugin/Tour/intro.js/introjs.min.css',
            'introThemeCss' => $themeCssPath,
            'introJs' => '/plugin/Tour/intro.js/intro.min.js',
            'stepsAjax' => '/plugin/Tour/ajax/steps.ajax.php',
            'saveAjax' => '/plugin/Tour/ajax/save.ajax.php',
        ];
    }

    private function getBuyCoursesFrontendConfig(): array
    {
        $enabled = $this->pluginHelper->isPluginEnabled('BuyCourses');

        $showMainMenuTab = $enabled && $this->normalizePluginBoolean(
                $this->pluginHelper->getPluginConfigValue('BuyCourses', 'show_main_menu_tab', false)
            );

        $publicMainMenuTab = $enabled && $this->normalizePluginBoolean(
                $this->pluginHelper->getPluginConfigValue('BuyCourses', 'public_main_menu_tab', false)
            );

        $allowAnonymousUsers = $enabled && $this->normalizePluginBoolean(
                $this->pluginHelper->getPluginConfigValue('BuyCourses', 'unregistered_users_enable', false)
            );

        return [
            'enabled' => $enabled,
            'showMainMenuTab' => $showMainMenuTab,
            'publicMainMenuTab' => $publicMainMenuTab,
            'allowAnonymousUsers' => $allowAnonymousUsers,
            'visibleForAuthenticatedUsers' => $enabled && $showMainMenuTab,
            'visibleForAnonymousUsers' => $enabled && $showMainMenuTab && $publicMainMenuTab,
            'indexPath' => '/plugin/BuyCourses/index.php',
        ];
    }

    private function normalizePluginBoolean(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_int($value)) {
            return 1 === $value;
        }

        if (\is_string($value)) {
            $normalized = strtolower(trim($value));

            return \in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}
