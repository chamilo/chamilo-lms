<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseSettings;

use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AiFeatureAccessHelper;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\EventLoggerHelper;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

use const ENT_QUOTES;
use const FILTER_VALIDATE_URL;

final readonly class CourseSettingsManager
{
    private const array EXTRA_FIELD_VARIABLES = [
        'tags',
        'video_url',
        'course_hours_duration',
        'max_subscribed_students',
    ];
    private const array COURSE_BLOCK_SETTINGS = [
        'course_block_pre_footer',
        'course_block_footer_left',
        'course_block_footer_center',
        'course_block_footer_right',
    ];
    private const array AI_FEATURES = [
        'learning_path_generator' => 'Enable learning path generator',
        'exercise_generator' => 'Enable exercise generator',
        'open_answers_grader' => 'Enable open answers grader',
        'tutor_chatbot' => 'Enable tutor chatbot',
        'task_grader' => 'Enable task grader',
        'content_analyser' => 'Enable content analyser',
        'image_generator' => 'Enable image generator',
        'glossary_terms_generator' => 'Enable glossary terms generator',
        'video_generator' => 'Enable video generator',
        'course_analyser' => 'Enable course analyser',
    ];
    private const array COURSE_SETTING_CATEGORIES = [
        'show_course_in_user_language' => '',
        'documents_default_visibility' => 'document',
        'show_system_folders' => 'document',
        'email_alert_to_teacher_on_new_user_in_course' => 'registration',
        'email_alert_student_on_manual_subscription' => 'registration',
        'email_alert_students_on_new_homework' => 'work',
        'email_alert_manager_on_new_doc' => 'work',
        'email_alert_on_new_doc_dropbox' => 'dropbox',
        'email_alert_manager_on_new_quiz' => 'quiz',
        'email_to_teachers_on_new_work_feedback' => '',
        'allow_user_edit_agenda' => 'agenda',
        'allow_user_edit_announcement' => 'announcement',
        'allow_user_image_forum' => 'forum',
        'allow_user_view_user_list' => 'user',
        'allow_open_chat_window' => 'chat',
        'course_theme' => 'theme',
        'allow_learning_path_theme' => 'theme',
        'lp_return_link' => 'learning_path',
        'exercise_invisible_in_session' => 'exercise',
        'display_info_advance_inside_homecourse' => 'thematic_advance',
        'allow_public_certificates' => 'certificates',
        'customcertificate_course_enable' => '',
        'use_certificate_default' => '',
        'hide_forum_notifications' => 'forum',
        'subscribe_users_to_forum_notifications' => 'forum',
        'student_delete_own_publication' => 'work',
        'student_validate_own_attendance' => 'attendance',
        'enable_document_auto_launch' => 'document',
        'enable_lp_auto_launch' => 'learning_path',
        'enable_exercise_auto_launch' => 'exercise',
        'enable_forum_auto_launch' => 'forum',
        'pdf_export_watermark_text' => 'learning_path',
        'course_block_pre_footer' => 'plugin',
        'course_block_footer_left' => 'plugin',
        'course_block_footer_center' => 'plugin',
        'course_block_footer_right' => 'plugin',
        'learning_path_generator' => 'ai_helpers',
        'exercise_generator' => 'ai_helpers',
        'open_answers_grader' => 'ai_helpers',
        'tutor_chatbot' => 'ai_helpers',
        'task_grader' => 'ai_helpers',
        'content_analyser' => 'ai_helpers',
        'image_generator' => 'ai_helpers',
        'glossary_terms_generator' => 'ai_helpers',
        'video_generator' => 'ai_helpers',
        'course_analyser' => 'ai_helpers',
    ];
    private const array DEFAULT_VALUES = [
        'show_course_in_user_language' => 2,
        'documents_default_visibility' => 'visible',
        'show_system_folders' => 1,
        'email_alert_to_teacher_on_new_user_in_course' => 0,
        'email_alert_student_on_manual_subscription' => 0,
        'email_alert_students_on_new_homework' => 0,
        'email_alert_manager_on_new_doc' => 0,
        'email_alert_on_new_doc_dropbox' => 0,
        'email_alert_manager_on_new_quiz' => '',
        'email_to_teachers_on_new_work_feedback' => 2,
        'allow_user_edit_agenda' => 0,
        'allow_user_edit_announcement' => 0,
        'allow_user_image_forum' => 0,
        'allow_user_view_user_list' => 0,
        'allow_open_chat_window' => 0,
        'course_theme' => '',
        'allow_learning_path_theme' => 0,
        'lp_return_link' => 0,
        'exercise_invisible_in_session' => 0,
        'display_info_advance_inside_homecourse' => 0,
        'allow_public_certificates' => 0,
        'customcertificate_course_enable' => 0,
        'use_certificate_default' => 0,
        'hide_forum_notifications' => 2,
        'subscribe_users_to_forum_notifications' => 2,
        'student_delete_own_publication' => 0,
        'student_validate_own_attendance' => 0,
        'enable_document_auto_launch' => 0,
        'enable_lp_auto_launch' => 0,
        'enable_exercise_auto_launch' => 0,
        'enable_forum_auto_launch' => 0,
        'pdf_export_watermark_text' => '',
    ];

    private const array STRING_VALUE_KEYS = [
        'title',
        'course_language',
        'department_name',
        'department_url',
        'course_registration_password',
        'legal',
        'tags',
        'video_url',
        'course_theme',
        'pdf_export_watermark_text',
        'course_home_notify_content',
        'course_home_notify_expiration_link',
        'course_legal_content',
        'customcertificate_mode',
        'customcertificate_content_course',
        'customcertificate_contents',
        'customcertificate_date_start',
        'customcertificate_date_end',
        'customcertificate_place',
        'customcertificate_day',
        'customcertificate_month',
        'customcertificate_year',
        'customcertificate_signature_text1',
        'customcertificate_signature_text2',
        'customcertificate_signature_text3',
        'customcertificate_signature_text4',
        'auto_launch_option',
        'course_block_pre_footer',
        'course_block_footer_left',
        'course_block_footer_center',
        'course_block_footer_right',
    ];

    private const array INTEGER_VALUE_KEYS = [
        'visibility',
        'activate_legal',
        'show_score',
        'room_id',
        'course_hours_duration',
        'max_subscribed_students',
        'show_course_in_user_language',
        'show_system_folders',
        'email_alert_to_teacher_on_new_user_in_course',
        'email_alert_student_on_manual_subscription',
        'email_alert_students_on_new_homework',
        'email_alert_manager_on_new_doc',
        'email_alert_on_new_doc_dropbox',
        'email_to_teachers_on_new_work_feedback',
        'allow_user_edit_agenda',
        'allow_user_edit_announcement',
        'allow_user_image_forum',
        'allow_user_view_user_list',
        'allow_open_chat_window',
        'allow_learning_path_theme',
        'lp_return_link',
        'exercise_invisible_in_session',
        'display_info_advance_inside_homecourse',
        'allow_public_certificates',
        'hide_forum_notifications',
        'subscribe_users_to_forum_notifications',
        'student_delete_own_publication',
        'student_validate_own_attendance',
        'course_legal_warn_users',
        'customcertificate_contents_type',
        'customcertificate_date_change',
        'customcertificate_type_date_expediction',
        'customcertificate_margin_left',
        'customcertificate_margin_right',
    ];

    private const array BOOLEAN_VALUE_KEYS = [
        'subscribe',
        'unsubscribe',
        'course_home_notify_enabled',
        'course_legal_remove_previous_agreements',
    ];

    private const array ALLOWED_AUTO_LAUNCH_OPTIONS = [
        'enable_document_auto_launch',
        'enable_lp_auto_launch',
        'enable_lp_auto_launch_list',
        'enable_exercise_auto_launch',
        'enable_exercise_auto_launch_list',
        'enable_forum_auto_launch',
        'disable_auto_launch',
    ];

    private const array ALLOWED_CUSTOM_CERTIFICATE_MODES = [
        'disabled',
        'course',
        'default',
    ];

    private const array ALLOWED_INTEGER_VALUES = [
        'activate_legal' => [0, 1],
        'show_score' => [0, 1],
        'show_course_in_user_language' => [1, 2],
        'show_system_folders' => [1, 2],
        'email_alert_to_teacher_on_new_user_in_course' => [0, 1, 2],
        'email_alert_student_on_manual_subscription' => [0, 1],
        'email_alert_students_on_new_homework' => [0, 1, 2],
        'email_alert_manager_on_new_doc' => [0, 1, 2, 3],
        'email_alert_on_new_doc_dropbox' => [0, 1],
        'email_to_teachers_on_new_work_feedback' => [1, 2],
        'allow_user_edit_agenda' => [0, 1],
        'allow_user_edit_announcement' => [0, 1],
        'allow_user_image_forum' => [0, 1],
        'allow_user_view_user_list' => [0, 1],
        'allow_open_chat_window' => [0, 1],
        'allow_learning_path_theme' => [0, 1],
        'lp_return_link' => [0, 1, 2, 3, 4],
        'exercise_invisible_in_session' => [0, 1],
        'display_info_advance_inside_homecourse' => [0, 1, 2, 3],
        'allow_public_certificates' => [0, 1],
        'hide_forum_notifications' => [1, 2],
        'subscribe_users_to_forum_notifications' => [1, 2],
        'student_delete_own_publication' => [0, 1],
        'student_validate_own_attendance' => [0, 1],
        'course_legal_warn_users' => [1, 2, 3],
        'customcertificate_contents_type' => [0, 1, 2, 3],
        'customcertificate_date_change' => [0, 1, 2],
        'customcertificate_type_date_expediction' => [0, 1, 2, 3, 4],
    ];

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private EntityManagerInterface $entityManager,
        private Connection $connection,
        private Security $security,
        private SettingsManager $settingsManager,
        private AccessUrlHelper $accessUrlHelper,
        private PluginHelper $pluginHelper,
        private CourseRepository $courseRepository,
        private LanguageRepository $languageRepository,
        private ExtraFieldRepository $extraFieldRepository,
        private ExtraFieldValuesRepository $extraFieldValuesRepository,
        private IllustrationRepository $illustrationRepository,
        private AiFeatureAccessHelper $aiFeatureAccessHelper,
        private AiProviderFactory $aiProviderFactory,
        private UrlGeneratorInterface $router,
        private ParameterBagInterface $parameterBag,
        private MailerInterface $mailer,
        private EventLoggerHelper $eventLoggerHelper,
        private LoggerInterface $logger,
        #[Autowire(service: 'oneup_flysystem.themes_filesystem')]
        private FilesystemOperator $themesFilesystem,
    ) {}

    public function isWatermarkEnabled(): bool
    {
        return $this->settingEnabled('pdf_export_watermark_by_course');
    }

    public function logMediaUpdate(Course $course, ?Session $session, string $valueType, mixed $value): void
    {
        $user = $this->security->getUser();
        $userId = $user instanceof User ? (int) $user->getId() : null;

        $this->eventLoggerHelper->addEvent(
            'course_settings_updated',
            $valueType,
            $value,
            null,
            $userId,
            (int) $course->getId(),
            (int) ($session?->getId() ?? 0),
        );
    }

    /**
     * @return array{0: Course, 1: Session|null}
     */
    public function resolveContext(): array
    {
        $course = $this->cidReqHelper->requireDoctrineCourseEntity();

        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        if ($session instanceof Session && !$session->hasCourse($course)) {
            throw new AccessDeniedHttpException('The requested session does not contain the current course.');
        }

        return [$course, $session];
    }

    public function assertCanEdit(Course $course): void
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $user = $this->security->getUser();
        if ($user instanceof User && $course->hasUserAsTeacher($user)) {
            return;
        }

        $resourceNode = $course->getResourceNode();
        if (null !== $resourceNode && $this->security->isGranted('EDIT', $resourceNode)) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to edit this course.');
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanEdit($course);

        $values = $this->getCourseValues($course, $session);
        $permissions = $this->getPermissions($course);
        $integrations = $this->getIntegrations($course, $session);

        return [
            'courseId' => (int) $course->getId(),
            'sessionId' => $session?->getId(),
            'resourceNodeId' => (int) ($course->getResourceNode()?->getId() ?? 0),
            'values' => $values,
            'sections' => $this->buildSections($values, $permissions, $integrations),
            'permissions' => $permissions,
            'media' => $this->getMedia($course, $session),
            'integrations' => $integrations,
        ];
    }

    /**
     * @param array<string, mixed> $submittedValues
     */
    public function saveConfiguration(array $submittedValues): void
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanEdit($course);
        $permissions = $this->getPermissions($course);
        $currentValues = $this->getCourseValues($course, $session);
        $values = $this->normalizeValues(array_replace($currentValues, $submittedValues));

        $this->validateMainValues($values, $course, $permissions);
        $this->validateActiveCourseLimit($course, (int) $values['visibility']);

        $this->entityManager->wrapInTransaction(function () use ($course, $session, $values, $permissions): void {
            $this->saveCourseEntity($course, $values, $permissions);
            $this->saveCourseSettings($course, $values);
            $this->saveExtraFields($course, $values);
            $this->saveCourseHomeNotification($course, $values);
            $this->saveCourseLegal($course, $session, $values);
            $this->saveCustomCertificate($course, $session, $values);
            $this->entityManager->flush();
        });

        $this->sendCourseLegalNotifications($course, $session, $values);
        $this->logCourseSettingsUpdate($course, $session);
    }

    /**
     * @return array<string, mixed>
     */
    private function getCourseValues(Course $course, ?Session $session): array
    {
        $values = [
            'course_id' => (int) $course->getId(),
            'session_id' => (int) ($session?->getId() ?? 0),
            'title' => $course->getTitle(),
            'course_language' => $course->getCourseLanguage(),
            'department_name' => $course->getDepartmentName() ?? '',
            'department_url' => $course->getDepartmentUrl() ?? '',
            'room_id' => (int) ($course->getRoom()?->getId() ?? 0),
            'visibility' => $course->getVisibility(),
            'subscribe' => $course->getSubscribe(),
            'unsubscribe' => $course->getUnsubscribe(),
            'course_registration_password' => $course->getRegistrationCode() ?? '',
            'legal' => $course->getLegal() ?? '',
            'activate_legal' => (int) ($course->getActivateLegal() ?? 0),
            'show_score' => (int) ($course->getShowScore() ?? 0),
            'video_url' => $course->getVideoUrl(),
            'disk_quota_display' => number_format((int) ($course->getDiskQuota() ?? 0), 2).' MB',
        ];

        foreach (self::DEFAULT_VALUES as $variable => $defaultValue) {
            $values[$variable] = $this->getCourseSettingValue($course, $variable, $defaultValue);
        }

        foreach (self::COURSE_BLOCK_SETTINGS as $variable) {
            $values[$variable] = (string) $this->getCourseSettingValue($course, $variable, '');
        }

        foreach (self::AI_FEATURES as $variable => $_label) {
            if ($this->isAiFeatureConfigurable($variable, (int) $course->getId())) {
                $values[$variable] = (string) $this->getCourseSettingValue($course, $variable, 'false');
            }
        }

        $values['email_alert_manager_on_new_quiz'] = $this->normalizeStringList(
            $values['email_alert_manager_on_new_quiz'] ?? '',
            ['1', '2', '3', '4'],
        );
        $values['auto_launch_option'] = $this->resolveAutoLaunchOption($values);
        $values['customcertificate_mode'] = $this->resolveCustomCertificateMode($values);

        foreach (self::EXTRA_FIELD_VARIABLES as $variable) {
            $extraFieldValue = $this->extraFieldValuesRepository->getValueByVariableAndItem(
                $variable,
                (int) $course->getId(),
                ExtraField::COURSE_FIELD_TYPE,
            );
            if (null !== $extraFieldValue) {
                $values[$variable] = $extraFieldValue->getFieldValue() ?? '';
            } elseif (!\array_key_exists($variable, $values)) {
                $values[$variable] = '';
            }
        }

        $notification = $this->getCourseHomeNotification($course);
        $values['course_home_notify_content'] = $notification['content'];
        $values['course_home_notify_expiration_link'] = $notification['expirationLink'];
        $values['course_home_notify_enabled'] = $notification['exists'];

        $legal = $this->getCourseLegalData((int) $course->getId(), (int) ($session?->getId() ?? 0));
        $values['course_legal_content'] = (string) ($legal['content'] ?? '');
        $values['course_legal_filename'] = (string) ($legal['filename'] ?? '');
        $values['course_legal_remove_previous_agreements'] = false;
        $values['course_legal_warn_users'] = 1;
        $values['course_legal_agreements'] = $this->getCourseLegalAgreements(
            (int) $course->getId(),
            (int) ($session?->getId() ?? 0),
        );

        $certificate = $this->getCustomCertificateData(
            (int) $course->getId(),
            (int) ($session?->getId() ?? 0),
            (int) ($this->accessUrlHelper->getCurrent()?->getId() ?? 0),
        );
        foreach ($certificate as $key => $value) {
            $values['customcertificate_'.$key] = $value;
        }

        return $this->normalizeLoadedSelectableValues($values);
    }

    /**
     * @return array<string, mixed>
     */
    private function getPermissions(Course $course): array
    {
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        $visibilityAdminsOnly = $this->settingEnabled('workflows.course_visibility_change_only_admin');
        $paidCourse = $this->hasActiveBuyCoursesService((int) $course->getId());

        return [
            'isAdmin' => $isAdmin,
            'canChangeVisibility' => !$visibilityAdminsOnly || $isAdmin || $paidCourse,
            'canChangeSubscription' => !$visibilityAdminsOnly || $isAdmin,
            'canUseHiddenVisibility' => $isAdmin,
            'paidCourseVisibilityException' => $paidCourse,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getIntegrations(Course $course, ?Session $session): array
    {
        $courseId = (int) $course->getId();
        $sessionId = (int) ($session?->getId() ?? 0);
        $aiEnabled = $this->settingEnabled('ai_helpers.enable_ai_helpers');
        $canGeneratePicture = $aiEnabled
            && $this->aiProviderFactory->hasProvidersForType('image')
            && $this->aiFeatureAccessHelper->isFeatureEnabledForCourse('image_generator', $courseId);
        $ltiEnabled = $this->pluginHelper->isPluginEnabled('ImsLti');
        $ltiParameters = ['cid' => $courseId];
        if ($sessionId > 0) {
            $ltiParameters['sid'] = $sessionId;
        }

        return [
            'lti' => [
                'enabled' => $ltiEnabled,
                'url' => $ltiEnabled ? $this->router->generate('chamilo_lti_configure', $ltiParameters) : '',
            ],
            'courseBlock' => ['enabled' => $this->pluginHelper->isPluginEnabled('CourseBlock')],
            'courseHomeNotify' => [
                'enabled' => $this->pluginHelper->isPluginEnabled('CourseHomeNotify')
                    && $this->tableExists('course_home_notify_notification'),
            ],
            'courseLegal' => [
                'enabled' => $this->pluginHelper->isPluginEnabled('CourseLegal')
                    && $this->tableExists('session_rel_course_legal'),
            ],
            'customCertificate' => [
                'enabled' => $this->pluginHelper->isPluginEnabled('CustomCertificate')
                    && $this->tableExists('plugin_customcertificate'),
            ],
            'buyCourses' => [
                'enabled' => $this->pluginHelper->isPluginEnabled('BuyCourses'),
                'activePaidCourse' => $this->hasActiveBuyCoursesService($courseId),
            ],
            'ai' => [
                'enabled' => $aiEnabled,
                'canGeneratePicture' => $canGeneratePicture,
                'generatePictureUrl' => '/ai/generate_course_picture',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getMedia(Course $course, ?Session $session): array
    {
        $accessUrlId = (int) ($this->accessUrlHelper->getCurrent()?->getId() ?? 0);
        $watermarkPath = $this->getWatermarkPath($course, $accessUrlId);
        $courseLegal = $this->getCourseLegalData((int) $course->getId(), (int) ($session?->getId() ?? 0));

        return [
            'pictureUrl' => $this->illustrationRepository->getIllustrationUrl($course, 'course_picture_medium'),
            'hasCustomPicture' => $this->illustrationRepository->hasIllustration($course),
            'pictureUploadUrl' => '/api/course-settings/picture',
            'pictureDeleteUrl' => '/api/course-settings/picture',
            'watermarkEnabled' => $this->settingEnabled('pdf_export_watermark_by_course'),
            'watermarkExists' => is_file($watermarkPath),
            'watermarkUrl' => is_file($watermarkPath)
                ? '/courses/'.rawurlencode((string) $course->getDirectory()).'/'.$accessUrlId.'_pdf_watermark.png'
                : '',
            'watermarkUploadUrl' => '/api/course-settings/watermark',
            'watermarkDeleteUrl' => '/api/course-settings/watermark',
            'courseLegalFileExists' => '' !== (string) ($courseLegal['filename'] ?? ''),
            'courseLegalFileName' => (string) ($courseLegal['filename'] ?? ''),
            'courseLegalFileUrl' => '/api/course-settings/course-legal-file',
            'customCertificateMediaUrl' => '/api/course-settings/custom-certificate-media',
        ];
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, mixed> $permissions
     * @param array<string, mixed> $integrations
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildSections(array $values, array $permissions, array $integrations): array
    {
        $sections = [];
        $mainFields = [
            $this->field('title', 'Title', 'text', required: true),
            $this->field('course_language', 'Language', 'select', options: $this->getLanguageOptions()),
            $this->field('show_course_in_user_language', "Show course in user's language", 'radio', options: $this->yesNoOptions(1, 2)),
            $this->field('department_name', 'Department', 'text'),
            $this->field('department_url', 'Department URL', 'url'),
        ];
        if ($this->entityManager->getRepository(Room::class)->count([]) > 0) {
            $mainFields[] = $this->field('room_id', 'Default room', 'select', options: $this->getRoomOptions());
        }
        if ($this->hasCourseExtraField('tags')) {
            $mainFields[] = $this->field('tags', 'Tags', 'tags');
        }
        if ($this->hasCourseExtraField('video_url')) {
            $mainFields[] = $this->field('video_url', 'Video URL', 'url');
        }
        if ($this->hasCourseExtraField('course_hours_duration')) {
            $mainFields[] = $this->field('course_hours_duration', 'Course duration in hours', 'number', min: 0);
        }
        if ($this->hasCourseExtraField('max_subscribed_students')) {
            $mainFields[] = $this->field('max_subscribed_students', 'Maximum subscribed students', 'number', min: 0);
        }
        if ($this->settingEnabled('pdf_export_watermark_by_course')) {
            $mainFields[] = $this->field('pdf_export_watermark_text', 'PDF watermark text', 'text');
        }
        if ($this->settingEnabled('allow_course_theme')) {
            $mainFields[] = $this->field('course_theme', 'Style sheets', 'select', options: $this->getThemeOptions());
        }
        $mainFields[] = $this->field('disk_quota_display', 'Space Available', 'readonly');
        $sections[] = $this->section('course_main', 'Course settings', 'information', $mainFields);

        $visibilityOptions = [
            ['label' => 'Closed', 'value' => Course::CLOSED],
            ['label' => 'Private', 'value' => Course::REGISTERED],
            ['label' => 'Open to the platform', 'value' => Course::OPEN_PLATFORM],
            ['label' => 'Public', 'value' => Course::OPEN_WORLD],
        ];
        if (!empty($permissions['canUseHiddenVisibility'])) {
            $visibilityOptions[] = ['label' => 'Hidden', 'value' => Course::HIDDEN];
        }

        $sections[] = $this->section('course_access', 'Course access', 'lock', [
            $this->field('visibility', 'Course access', 'select', options: $visibilityOptions, disabled: empty($permissions['canChangeVisibility'])),
            $this->field('subscribe', 'Subscription allowed', 'checkbox', disabled: empty($permissions['canChangeSubscription'])),
            $this->field('unsubscribe', 'Unsubscription allowed', 'checkbox', disabled: empty($permissions['canChangeSubscription'])),
            $this->field('course_registration_password', 'Registration password', 'text'),
            $this->field('activate_legal', 'Activate legal notice', 'checkbox'),
            $this->field('legal', 'Legal notice', 'textarea'),
            $this->field('direct_invitation_url', 'Direct invitation link', 'readonly-link', value: '/registration?normal=1&c='.(int) ($values['course_id'] ?? 0).'&e=1'),
        ]);

        $documentFields = [];
        if ($this->settingEnabled('documents_default_visibility_defined_in_course')) {
            $documentFields[] = $this->field('documents_default_visibility', 'Default visibility for new documents', 'radio', options: [
                ['label' => 'Visible', 'value' => 'visible'],
                ['label' => 'Invisible', 'value' => 'invisible'],
            ]);
        }
        if ($this->settingEnabled('show_default_folders')) {
            $documentFields[] = $this->field('show_system_folders', 'Show system folders', 'radio', options: $this->yesNoOptions(1, 2));
        }
        $sections[] = $this->section('documents', 'Documents', 'folder-generic', $documentFields, collapsed: true);

        $sections[] = $this->section('notifications', 'E-mail notifications', 'email-outline', [
            $this->field('email_alert_to_teacher_on_new_user_in_course', 'E-mail teacher when a new user auto-subscribes', 'radio', options: [
                ['label' => 'Disable', 'value' => 0],
                ['label' => 'Enable', 'value' => 1],
                ['label' => 'To teacher and tutor', 'value' => 2],
            ]),
            $this->field('email_alert_student_on_manual_subscription', 'E-mail learner on manual subscription', 'radio', options: $this->yesNoOptions(1, 0)),
            $this->field('email_alert_students_on_new_homework', 'E-mail students on assignment creation', 'radio', options: [
                ['label' => 'Disable', 'value' => 0],
                ['label' => 'Enable', 'value' => 1],
                ['label' => 'To HR only', 'value' => 2],
            ]),
            $this->field('email_alert_manager_on_new_doc', 'E-mail on assignments submission by students', 'radio', options: [
                ['label' => 'Disable', 'value' => 0],
                ['label' => 'Enable', 'value' => 1],
                ['label' => 'Only for students', 'value' => 2],
                ['label' => 'Only for teachers', 'value' => 3],
            ]),
            $this->field('email_alert_on_new_doc_dropbox', 'E-mail users on Dropbox file reception', 'radio', options: $this->yesNoOptions(1, 0)),
            $this->field('email_alert_manager_on_new_quiz', 'Tests', 'checkbox-list', options: [
                ['label' => 'Aware: E-mail teacher when a student ends an exercise', 'value' => '1'],
                ['label' => 'Paranoid: E-mail teacher when a student starts an exercise', 'value' => '2'],
                ['label' => 'Relaxed open: E-mail teacher when a student ends an exercise, only if an open question is answered', 'value' => '3'],
                ['label' => 'Relaxed audio: E-mail teacher when a student ends an exercise, only if an oral question is answered', 'value' => '4'],
            ]),
            $this->field('email_to_teachers_on_new_work_feedback', "E-mail to teachers on new user's student publication feedback", 'radio', options: $this->yesNoOptions(1, 2)),
        ]);

        $sections[] = $this->section('user_rights', 'User rights', 'join-group', [
            $this->field('allow_user_edit_agenda', 'Allow learners to edit agenda', 'radio', options: $this->yesNoOptions(1, 0)),
            $this->field('allow_user_edit_announcement', 'Allow learners to edit announcements', 'radio', options: $this->yesNoOptions(1, 0)),
            $this->field('allow_user_image_forum', 'Show learner picture in forums', 'radio', options: $this->yesNoOptions(1, 0)),
            $this->field('allow_user_view_user_list', 'Allow learners to view users list', 'radio', options: $this->yesNoOptions(1, 0)),
        ]);

        $sections[] = $this->section('chat', 'Chat settings', 'comment', [
            $this->field('allow_open_chat_window', 'Open chat in a new window', 'radio', options: $this->yesNoOptions(1, 0)),
        ], collapsed: true);

        $learningPathFields = [];
        if ($this->settingEnabled('allow_course_theme')) {
            $learningPathFields[] = $this->field('allow_learning_path_theme', 'Enable course themes in learning paths', 'radio', options: $this->yesNoOptions(1, 0));
        }
        if ($this->settingEnabled('lp.allow_lp_return_link')) {
            $learningPathFields[] = $this->field('lp_return_link', 'Learning path return link', 'radio', options: [
                ['label' => 'Redirect to Course home', 'value' => 0],
                ['label' => 'Redirect to the learning paths list', 'value' => 1],
                ['label' => 'My courses', 'value' => 2],
                ['label' => 'Redirect to portal home', 'value' => 3],
                ['label' => 'My sessions', 'value' => 4],
            ]);
        }
        if ($this->settingEnabled('exercise_invisible_in_session') && $this->settingEnabled('configure_exercise_visibility_in_course')) {
            $learningPathFields[] = $this->field('exercise_invisible_in_session', 'Exercises invisible in session', 'radio', options: $this->yesNoOptions(1, 0));
        }
        $sections[] = $this->section('learning_path', 'Learning path settings', 'learning-paths', $learningPathFields, collapsed: true);

        $sections[] = $this->section('thematic', 'Thematic advance configuration', 'tracking', [
            $this->field('display_info_advance_inside_homecourse', 'Information on thematic advance on course homepage', 'radio', options: [
                ['label' => 'Display information about the last completed topic', 'value' => 1],
                ['label' => 'Display information about the next uncompleted topic', 'value' => 2],
                ['label' => 'Display information about the next incomplete and the last completed topic', 'value' => 3],
                ['label' => 'Do not display progress', 'value' => 0],
            ]),
        ], collapsed: true);

        if ($this->settingEnabled('certificate.allow_public_certificates')) {
            $sections[] = $this->section('certificates', 'Certificates', 'gradebook', [
                $this->field('allow_public_certificates', 'Allow public certificates', 'radio', options: $this->yesNoOptions(1, 0)),
            ], collapsed: true);
        }

        if (!empty($integrations['customCertificate']['enabled'])) {
            $certificateDateOptions = [
                ['label' => 'Custom', 'value' => 1],
                ['label' => 'None', 'value' => 2],
            ];
            $certificateIssueDateOptions = [
                ['label' => 'Use certificate download date', 'value' => 1],
                ['label' => 'Use custom date', 'value' => 2],
                ['label' => 'None', 'value' => 3],
                ['label' => 'Use certificate generation date', 'value' => 4],
            ];
            if ((int) ($values['session_id'] ?? 0) > 0) {
                array_unshift($certificateDateOptions, ['label' => 'Use session access dates', 'value' => 0]);
                array_unshift($certificateIssueDateOptions, ['label' => 'Use session access end date', 'value' => 0]);
            }

            $sections[] = $this->section('custom_certificate', 'Custom certificate', 'gradebook', [
                $this->field('customcertificate_mode', 'Certificate mode', 'radio', options: [
                    ['label' => 'Disabled', 'value' => 'disabled'],
                    ['label' => 'Course certificate', 'value' => 'course'],
                    ['label' => 'Default certificate', 'value' => 'default'],
                ]),
                $this->field('customcertificate_content_course', 'Student and course information', 'editor'),
                $this->field('customcertificate_contents_type', 'Contents to show', 'select', options: [
                    ['label' => 'Course description', 'value' => 0],
                    ['label' => 'Learning path index', 'value' => 1],
                    ['label' => 'Custom', 'value' => 2],
                    ['label' => 'Hide', 'value' => 3],
                ]),
                $this->field('customcertificate_contents', 'Contents', 'editor'),
                $this->field('customcertificate_date_change', 'Course delivery dates', 'select', options: $certificateDateOptions),
                $this->field('customcertificate_date_start', 'From', 'date'),
                $this->field('customcertificate_date_end', 'Until', 'date'),
                $this->field('customcertificate_place', 'Place', 'text'),
                $this->field('customcertificate_type_date_expediction', 'Issue date type', 'radio', options: $certificateIssueDateOptions),
                $this->field('customcertificate_day', 'Day', 'text'),
                $this->field('customcertificate_month', 'Month', 'text'),
                $this->field('customcertificate_year', 'Year', 'text'),
                $this->field('customcertificate_signature_text1', 'Signature text 1', 'text'),
                $this->field('customcertificate_signature_text2', 'Signature text 2', 'text'),
                $this->field('customcertificate_signature_text3', 'Signature text 3', 'text'),
                $this->field('customcertificate_signature_text4', 'Signature text 4', 'text'),
                $this->field('customcertificate_margin_left', 'Left margin', 'number', min: 0),
                $this->field('customcertificate_margin_right', 'Right margin', 'number', min: 0),
            ], collapsed: true);
        }

        $sections[] = $this->section('forum', 'Forum', 'add-topic', [
            $this->field('hide_forum_notifications', 'Hide forum notifications', 'radio', options: $this->yesNoOptions(1, 2)),
            $this->field('subscribe_users_to_forum_notifications', 'Automatically subscribe users to forum notifications', 'radio', options: $this->yesNoOptions(1, 2)),
        ], collapsed: true);

        $sections[] = $this->section('assignments', 'Assignments', 'file-text', [
            $this->field('show_score', 'Default setting for the visibility of newly posted files', 'radio', options: [
                ['label' => 'New documents are visible for all users', 'value' => 0],
                ['label' => 'New documents are only visible for the teacher(s)', 'value' => 1],
            ]),
            $this->field('student_delete_own_publication', 'Allow learners to delete their own publication', 'radio', options: $this->yesNoOptions(1, 0)),
        ], collapsed: true);

        $sections[] = $this->section('attendance', 'Attendance', 'account-check', [
            $this->field('student_validate_own_attendance', 'Allow learners to validate their own attendance', 'radio', options: $this->yesNoOptions(1, 0)),
        ], collapsed: true);

        $sections[] = $this->section('autolaunch', 'Autolaunch settings', 'rocket-launch', [
            $this->field('auto_launch_option', 'Auto-launch', 'radio', options: [
                ['label' => 'Documents list', 'value' => 'enable_document_auto_launch'],
                ['label' => 'Selected learning path', 'value' => 'enable_lp_auto_launch'],
                ['label' => 'Learning paths list', 'value' => 'enable_lp_auto_launch_list'],
                ['label' => 'Selected test', 'value' => 'enable_exercise_auto_launch'],
                ['label' => 'Tests list', 'value' => 'enable_exercise_auto_launch_list'],
                ['label' => 'Forums list', 'value' => 'enable_forum_auto_launch'],
                ['label' => 'Disable', 'value' => 'disable_auto_launch'],
            ]),
        ], collapsed: true);

        if (!empty($integrations['ai']['enabled'])) {
            $aiFields = [];
            foreach (self::AI_FEATURES as $key => $label) {
                if (\array_key_exists($key, $values)) {
                    $aiFields[] = $this->field($key, $label, 'radio', options: [
                        ['label' => 'Yes', 'value' => 'true'],
                        ['label' => 'No', 'value' => 'false'],
                    ]);
                }
            }
            if ([] !== $aiFields) {
                $sections[] = $this->section('ai_helpers', 'AI helpers', 'robot', $aiFields, collapsed: true);
            }
        }

        if (!empty($integrations['lti']['enabled'])) {
            $sections[] = $this->section('external_tools_lti', 'External tools (LTI)', 'link', [
                $this->field('lti_configuration_url', 'Configure external tools', 'external-link', value: $integrations['lti']['url']),
            ], collapsed: true);
        }

        if (!empty($integrations['courseBlock']['enabled'])) {
            $sections[] = $this->section('course_block', 'Course block', 'admin-settings', [
                $this->field('course_block_pre_footer', 'Before footer', 'editor'),
                $this->field('course_block_footer_left', 'Footer left', 'editor'),
                $this->field('course_block_footer_center', 'Footer center', 'editor'),
                $this->field('course_block_footer_right', 'Footer right', 'editor'),
            ], collapsed: true);
        }

        if (!empty($integrations['courseHomeNotify']['enabled'])) {
            $sections[] = $this->section('course_home_notify', 'Notify in course home', 'bell', [
                $this->field('course_home_notify_enabled', 'Enable course home notification', 'checkbox'),
                $this->field('course_home_notify_content', 'Content', 'editor'),
                $this->field('course_home_notify_expiration_link', 'Expiration link', 'url'),
            ], collapsed: true);
        }

        if (!empty($integrations['courseLegal']['enabled'])) {
            $sections[] = $this->section('course_legal', 'Course legal agreement', 'onlyoffice', [
                $this->field('course_legal_content', 'Agreement content', 'editor'),
                $this->field('course_legal_remove_previous_agreements', 'Remove previous agreements', 'checkbox'),
                $this->field('course_legal_warn_users', 'Notify learners', 'select', options: [
                    ['label' => 'Do not notify', 'value' => 1],
                    ['label' => 'Notify without attachment', 'value' => 2],
                    ['label' => 'Notify with agreement file', 'value' => 3],
                ]),
            ], collapsed: true);
        }

        return $sections;
    }

    /**
     * Applies the same UI defaults as the legacy course settings form without
     * writing them to the database. It also converts stored numeric strings to
     * integers so PrimeVue radio buttons can match their numeric option values.
     *
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function normalizeLoadedSelectableValues(array $values): array
    {
        foreach (self::ALLOWED_INTEGER_VALUES as $key => $allowedValues) {
            $hasLegacyDefault = \array_key_exists($key, self::DEFAULT_VALUES);
            if (!$hasLegacyDefault && 'show_score' !== $key) {
                continue;
            }

            $defaultValue = 'show_score' === $key ? 0 : (int) self::DEFAULT_VALUES[$key];
            $rawValue = $values[$key] ?? null;
            if (!\is_scalar($rawValue)
                || '' === trim((string) $rawValue)
                || !is_numeric((string) $rawValue)) {
                $values[$key] = $defaultValue;

                continue;
            }

            $normalizedValue = (int) $rawValue;
            $values[$key] = \in_array($normalizedValue, $allowedValues, true)
                ? $normalizedValue
                : $defaultValue;
        }

        $documentVisibility = (string) ($values['documents_default_visibility'] ?? '');
        $values['documents_default_visibility'] = \in_array(
            $documentVisibility,
            ['visible', 'invisible'],
            true,
        ) ? $documentVisibility : 'visible';

        foreach (self::AI_FEATURES as $key => $_label) {
            if (!\array_key_exists($key, $values)) {
                continue;
            }

            $values[$key] = $this->toBool($values[$key]) ? 'true' : 'false';
        }

        $autoLaunchOption = (string) ($values['auto_launch_option'] ?? '');
        $values['auto_launch_option'] = \in_array(
            $autoLaunchOption,
            self::ALLOWED_AUTO_LAUNCH_OPTIONS,
            true,
        ) ? $autoLaunchOption : 'disable_auto_launch';

        $certificateMode = (string) ($values['customcertificate_mode'] ?? '');
        $values['customcertificate_mode'] = \in_array(
            $certificateMode,
            self::ALLOWED_CUSTOM_CERTIFICATE_MODES,
            true,
        ) ? $certificateMode : 'disabled';

        if ('course' === $values['customcertificate_mode']) {
            $customCertificateDefaults = [
                'customcertificate_contents_type' => 0,
                'customcertificate_date_change' => 2,
                'customcertificate_type_date_expediction' => 3,
            ];
            foreach ($customCertificateDefaults as $key => $defaultValue) {
                $allowedValues = self::ALLOWED_INTEGER_VALUES[$key];
                $rawValue = $values[$key] ?? null;
                if (!\is_scalar($rawValue)
                    || '' === trim((string) $rawValue)
                    || !is_numeric((string) $rawValue)) {
                    $values[$key] = $defaultValue;

                    continue;
                }

                $normalizedValue = (int) $rawValue;
                $values[$key] = \in_array($normalizedValue, $allowedValues, true)
                    ? $normalizedValue
                    : $defaultValue;
            }
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function normalizeValues(array $values): array
    {
        $normalized = [];

        foreach (self::STRING_VALUE_KEYS as $key) {
            if (\array_key_exists($key, $values)) {
                $normalized[$key] = trim((string) $values[$key]);
            }
        }
        foreach (self::INTEGER_VALUE_KEYS as $key) {
            if (\array_key_exists($key, $values)) {
                $normalized[$key] = (int) $values[$key];
            }
        }
        foreach (self::BOOLEAN_VALUE_KEYS as $key) {
            if (\array_key_exists($key, $values)) {
                $normalized[$key] = $this->toBool($values[$key]);
            }
        }
        foreach (self::AI_FEATURES as $key => $_label) {
            if (\array_key_exists($key, $values)) {
                $normalized[$key] = $this->toBool($values[$key]) ? 'true' : 'false';
            }
        }

        $normalized['documents_default_visibility'] = 'invisible' === ($values['documents_default_visibility'] ?? '')
            ? 'invisible'
            : 'visible';
        $normalized['email_alert_manager_on_new_quiz'] = $this->normalizeStringList(
            $values['email_alert_manager_on_new_quiz'] ?? [],
            ['1', '2', '3', '4'],
        );

        return $normalized;
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, mixed> $permissions
     */
    private function validateMainValues(array $values, Course $course, array $permissions): void
    {
        if ('' === (string) $values['title']) {
            throw new BadRequestHttpException('The course title is required.');
        }
        if ('' === (string) $values['course_language']) {
            throw new BadRequestHttpException('The course language is required.');
        }
        if (null === $this->languageRepository->findOneBy([
            'isocode' => (string) $values['course_language'],
        ])) {
            throw new BadRequestHttpException('Invalid course language.');
        }
        if (mb_strlen((string) $values['department_name']) > 30) {
            throw new BadRequestHttpException('The department name is too long.');
        }
        if (mb_strlen((string) $values['department_url']) > 180
            || ('' !== (string) $values['department_url'] && false === filter_var($values['department_url'], FILTER_VALIDATE_URL))) {
            throw new BadRequestHttpException('Invalid department URL.');
        }
        if (mb_strlen((string) ($values['video_url'] ?? '')) > 255
            || ('' !== (string) ($values['video_url'] ?? '') && false === filter_var($values['video_url'], FILTER_VALIDATE_URL))) {
            throw new BadRequestHttpException('Invalid video URL.');
        }
        if ((int) ($values['course_hours_duration'] ?? 0) < 0 || (int) ($values['max_subscribed_students'] ?? 0) < 0) {
            throw new BadRequestHttpException('Course numeric limits cannot be negative.');
        }
        foreach (self::ALLOWED_INTEGER_VALUES as $key => $allowedValues) {
            if (\array_key_exists($key, $values) && !\in_array((int) $values[$key], $allowedValues, true)) {
                throw new BadRequestHttpException('Invalid value for course setting "'.$key.'".');
            }
        }
        $notificationExpirationLink = (string) ($values['course_home_notify_expiration_link'] ?? '');
        if ('' !== $notificationExpirationLink && false === filter_var($notificationExpirationLink, FILTER_VALIDATE_URL)) {
            throw new BadRequestHttpException('Invalid course home notification expiration link.');
        }
        if (!\in_array((string) ($values['auto_launch_option'] ?? ''), self::ALLOWED_AUTO_LAUNCH_OPTIONS, true)) {
            throw new BadRequestHttpException('Invalid auto-launch option.');
        }
        if (!\in_array((string) ($values['customcertificate_mode'] ?? ''), self::ALLOWED_CUSTOM_CERTIFICATE_MODES, true)) {
            throw new BadRequestHttpException('Invalid certificate mode.');
        }
        $theme = (string) ($values['course_theme'] ?? '');
        $currentTheme = (string) $this->getCourseSettingValue($course, 'course_theme', '');
        if ($this->settingEnabled('allow_course_theme')
            && $theme !== $currentTheme
            && '' !== $theme
            && !\in_array($theme, array_column($this->getThemeOptions(), 'value'), true)) {
            throw new BadRequestHttpException('Invalid course theme.');
        }
        if (!\in_array((int) ($values['course_legal_warn_users'] ?? 1), [1, 2, 3], true)) {
            throw new BadRequestHttpException('Invalid course legal notification option.');
        }

        $roomId = (int) ($values['room_id'] ?? 0);
        if ($roomId > 0 && !$this->entityManager->getRepository(Room::class)->find($roomId) instanceof Room) {
            throw new BadRequestHttpException('Invalid room.');
        }

        $visibility = (int) $values['visibility'];
        if (!\in_array($visibility, [Course::CLOSED, Course::REGISTERED, Course::OPEN_PLATFORM, Course::OPEN_WORLD, Course::HIDDEN], true)) {
            throw new BadRequestHttpException('Invalid course visibility.');
        }
        if (Course::HIDDEN === $visibility && empty($permissions['canUseHiddenVisibility'])) {
            throw new AccessDeniedHttpException('Only platform administrators can hide a course.');
        }
        if ($visibility !== $course->getVisibility() && empty($permissions['canChangeVisibility'])) {
            throw new AccessDeniedHttpException('You are not allowed to change course visibility.');
        }
        if (($this->toBool($values['subscribe']) !== $course->getSubscribe()
                || $this->toBool($values['unsubscribe']) !== $course->getUnsubscribe())
            && empty($permissions['canChangeSubscription'])) {
            throw new AccessDeniedHttpException('You are not allowed to change course subscription settings.');
        }
    }

    private function validateActiveCourseLimit(Course $course, int $newVisibility): void
    {
        if (Course::HIDDEN !== $course->getVisibility() || Course::HIDDEN === $newVisibility) {
            return;
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl) {
            return;
        }
        $limit = (int) ($accessUrl->getLimitActiveCourses() ?? 0);
        if ($limit <= 0) {
            return;
        }

        $count = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT c.id)')
            ->from(Course::class, 'c')
            ->innerJoin('c.urls', 'rel')
            ->innerJoin('rel.url', 'accessUrl')
            ->andWhere('accessUrl.id = :accessUrlId')
            ->andWhere('c.visibility != :hidden')
            ->setParameter('accessUrlId', (int) $accessUrl->getId(), Types::INTEGER)
            ->setParameter('hidden', Course::HIDDEN, Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        if ($count >= $limit) {
            throw new BadRequestHttpException('The active courses hosting limit has been reached.');
        }
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, mixed> $permissions
     */
    private function saveCourseEntity(Course $course, array $values, array $permissions): void
    {
        $course
            ->setTitle((string) $values['title'])
            ->setCourseLanguage((string) $values['course_language'])
            ->setDepartmentName((string) $values['department_name'])
            ->setDepartmentUrl((string) $values['department_url'])
            ->setRegistrationCode((string) $values['course_registration_password'])
            ->setLegal((string) $values['legal'])
            ->setActivateLegal((int) $values['activate_legal'])
            ->setShowScore((int) $values['show_score'])
            ->setVideoUrl(trim((string) ($values['video_url'] ?? '')))
        ;

        if (!empty($permissions['canChangeVisibility'])) {
            $course->setVisibility((int) $values['visibility']);
        }
        if (!empty($permissions['canChangeSubscription'])) {
            $course
                ->setSubscribe($this->toBool($values['subscribe']))
                ->setUnsubscribe($this->toBool($values['unsubscribe']))
            ;
        }

        $roomId = (int) ($values['room_id'] ?? 0);
        $room = $roomId > 0 ? $this->entityManager->getRepository(Room::class)->find($roomId) : null;
        $course->setRoom($room instanceof Room ? $room : null);
        $this->entityManager->persist($course);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function saveCourseSettings(Course $course, array $values): void
    {
        $autoLaunchOption = (string) ($values['auto_launch_option'] ?? 'disable_auto_launch');
        $values['enable_document_auto_launch'] = 'enable_document_auto_launch' === $autoLaunchOption ? 1 : 0;
        $values['enable_lp_auto_launch'] = match ($autoLaunchOption) {
            'enable_lp_auto_launch' => 1,
            'enable_lp_auto_launch_list' => 2,
            default => 0,
        };
        $values['enable_exercise_auto_launch'] = match ($autoLaunchOption) {
            'enable_exercise_auto_launch' => 1,
            'enable_exercise_auto_launch_list' => 2,
            default => 0,
        };
        $values['enable_forum_auto_launch'] = 'enable_forum_auto_launch' === $autoLaunchOption ? 1 : 0;

        $certificateMode = (string) ($values['customcertificate_mode'] ?? 'disabled');
        $values['customcertificate_course_enable'] = 'course' === $certificateMode ? 1 : 0;
        $values['use_certificate_default'] = 'default' === $certificateMode ? 1 : 0;
        $values['email_alert_manager_on_new_quiz'] = implode(',', (array) ($values['email_alert_manager_on_new_quiz'] ?? []));

        foreach (self::COURSE_SETTING_CATEGORIES as $variable => $category) {
            if (!\array_key_exists($variable, $values)) {
                continue;
            }
            if (!$this->canSaveCourseSetting($variable, (int) $course->getId())) {
                continue;
            }
            $this->saveCourseSettingValue($course, $variable, $values[$variable], $category);
        }
    }

    /**
     * @param array<string, mixed> $values
     */
    private function saveExtraFields(Course $course, array $values): void
    {
        foreach (self::EXTRA_FIELD_VARIABLES as $variable) {
            if (!\array_key_exists($variable, $values)) {
                continue;
            }
            $field = $this->extraFieldRepository->findByVariable(ExtraField::COURSE_FIELD_TYPE, $variable);
            if (!$field instanceof ExtraField) {
                continue;
            }
            $value = $values[$variable];
            if (\is_array($value)) {
                $value = implode(',', array_map('strval', $value));
            }
            $this->extraFieldValuesRepository->updateItemData($field, $course, trim((string) $value));
        }
    }

    /**
     * @param array<string, mixed> $values
     */
    private function saveCourseHomeNotification(Course $course, array $values): void
    {
        if (!$this->pluginHelper->isPluginEnabled('CourseHomeNotify')
            || !$this->tableExists('course_home_notify_notification')) {
            return;
        }

        $courseId = (int) $course->getId();
        $notification = $this->connection->fetchAssociative(
            'SELECT id FROM course_home_notify_notification WHERE c_id = :courseId LIMIT 1',
            ['courseId' => $courseId],
            ['courseId' => Types::INTEGER],
        );
        $enabled = $this->toBool($values['course_home_notify_enabled'] ?? false);
        if (!$enabled) {
            if (false !== $notification) {
                $this->connection->delete(
                    'course_home_notify_notification',
                    ['id' => (int) $notification['id']],
                    ['id' => Types::INTEGER],
                );
            }

            return;
        }

        $payload = [
            'content' => (string) ($values['course_home_notify_content'] ?? ''),
            'expiration_link' => trim((string) ($values['course_home_notify_expiration_link'] ?? '')),
        ];
        $types = [
            'content' => Types::TEXT,
            'expiration_link' => Types::STRING,
        ];

        if (false === $notification) {
            $payload['hash'] = bin2hex(random_bytes(16));
            $payload['c_id'] = $courseId;
            $types['hash'] = Types::STRING;
            $types['c_id'] = Types::INTEGER;
            $this->connection->insert('course_home_notify_notification', $payload, $types);

            return;
        }

        $this->connection->update(
            'course_home_notify_notification',
            $payload,
            ['id' => (int) $notification['id']],
            $types,
            ['id' => Types::INTEGER],
        );
    }

    /**
     * @param array<string, mixed> $values
     */
    private function saveCourseLegal(Course $course, ?Session $session, array $values): void
    {
        if (!$this->pluginHelper->isPluginEnabled('CourseLegal') || !$this->tableExists('session_rel_course_legal')) {
            return;
        }
        $courseId = (int) $course->getId();
        $sessionId = (int) ($session?->getId() ?? 0);
        $existing = $this->getCourseLegalData($courseId, $sessionId);
        $content = (string) ($values['course_legal_content'] ?? '');
        if ([] === $existing) {
            $this->connection->insert('session_rel_course_legal', [
                'c_id' => $courseId,
                'session_id' => $sessionId,
                'content' => $content,
                'filename' => '',
            ], [
                'c_id' => Types::INTEGER,
                'session_id' => Types::INTEGER,
                'content' => Types::TEXT,
                'filename' => Types::STRING,
            ]);
        } else {
            $this->connection->update(
                'session_rel_course_legal',
                ['content' => $content],
                ['id' => (int) $existing['id']],
                ['content' => Types::TEXT],
                ['id' => Types::INTEGER],
            );
        }

        if ($this->toBool($values['course_legal_remove_previous_agreements'] ?? false)
            && $this->tableExists('session_rel_course_rel_user_legal')) {
            $this->connection->delete('session_rel_course_rel_user_legal', [
                'c_id' => $courseId,
                'session_id' => $sessionId,
            ], [
                'c_id' => Types::INTEGER,
                'session_id' => Types::INTEGER,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $values
     */
    private function saveCustomCertificate(Course $course, ?Session $session, array $values): void
    {
        if (!$this->pluginHelper->isPluginEnabled('CustomCertificate') || !$this->tableExists('plugin_customcertificate')) {
            return;
        }
        $courseId = (int) $course->getId();
        $sessionId = (int) ($session?->getId() ?? 0);
        $accessUrlId = (int) ($this->accessUrlHelper->getCurrent()?->getId() ?? 0);
        $existing = $this->getCustomCertificateData($courseId, $sessionId, $accessUrlId);
        $certificateMode = (string) ($values['customcertificate_mode'] ?? 'disabled');
        if ('course' !== $certificateMode && empty($existing['id'])) {
            return;
        }

        $payload = [
            'access_url_id' => $accessUrlId,
            'c_id' => $courseId,
            'session_id' => $sessionId,
            'content_course' => (string) ($values['customcertificate_content_course'] ?? ''),
            'contents_type' => (int) ($values['customcertificate_contents_type'] ?? 0),
            'contents' => (string) ($values['customcertificate_contents'] ?? ''),
            'date_change' => (int) ($values['customcertificate_date_change'] ?? 0),
            'date_start' => $this->normalizeDate((string) ($values['customcertificate_date_start'] ?? '')),
            'date_end' => $this->normalizeDate((string) ($values['customcertificate_date_end'] ?? '')),
            'type_date_expediction' => (int) ($values['customcertificate_type_date_expediction'] ?? 0),
            'place' => trim((string) ($values['customcertificate_place'] ?? '')),
            'day' => trim((string) ($values['customcertificate_day'] ?? '')),
            'month' => trim((string) ($values['customcertificate_month'] ?? '')),
            'year' => trim((string) ($values['customcertificate_year'] ?? '')),
            'signature_text1' => trim((string) ($values['customcertificate_signature_text1'] ?? '')),
            'signature_text2' => trim((string) ($values['customcertificate_signature_text2'] ?? '')),
            'signature_text3' => trim((string) ($values['customcertificate_signature_text3'] ?? '')),
            'signature_text4' => trim((string) ($values['customcertificate_signature_text4'] ?? '')),
            'margin_left' => max(0, (int) ($values['customcertificate_margin_left'] ?? 0)),
            'margin_right' => max(0, (int) ($values['customcertificate_margin_right'] ?? 0)),
            'certificate_default' => 0,
        ];

        foreach (['logo_left', 'logo_center', 'logo_right', 'seal', 'signature1', 'signature2', 'signature3', 'signature4', 'background'] as $field) {
            $payload[$field] = (string) ($existing[$field] ?? '');
        }

        $types = [
            'access_url_id' => Types::INTEGER,
            'c_id' => Types::INTEGER,
            'session_id' => Types::INTEGER,
            'content_course' => Types::TEXT,
            'contents_type' => Types::INTEGER,
            'contents' => Types::TEXT,
            'date_change' => Types::INTEGER,
            'date_start' => Types::STRING,
            'date_end' => Types::STRING,
            'type_date_expediction' => Types::INTEGER,
            'place' => Types::STRING,
            'day' => Types::STRING,
            'month' => Types::STRING,
            'year' => Types::STRING,
            'signature_text1' => Types::STRING,
            'signature_text2' => Types::STRING,
            'signature_text3' => Types::STRING,
            'signature_text4' => Types::STRING,
            'margin_left' => Types::INTEGER,
            'margin_right' => Types::INTEGER,
            'certificate_default' => Types::INTEGER,
            'logo_left' => Types::STRING,
            'logo_center' => Types::STRING,
            'logo_right' => Types::STRING,
            'seal' => Types::STRING,
            'signature1' => Types::STRING,
            'signature2' => Types::STRING,
            'signature3' => Types::STRING,
            'signature4' => Types::STRING,
            'background' => Types::STRING,
        ];

        if (!empty($existing['id'])) {
            $this->connection->update(
                'plugin_customcertificate',
                $payload,
                ['id' => (int) $existing['id']],
                $types,
                ['id' => Types::INTEGER],
            );

            return;
        }
        $this->connection->insert('plugin_customcertificate', $payload, $types);
    }

    private function getCourseSettingValue(Course $course, string $variable, mixed $defaultValue): mixed
    {
        $items = $this->entityManager->getRepository(CCourseSetting::class)->findBy([
            'cId' => (int) $course->getId(),
            'variable' => $variable,
        ], ['iid' => 'ASC']);
        foreach ($items as $item) {
            if (!$item instanceof CCourseSetting || null === $item->getValue()) {
                continue;
            }
            $value = $item->getValue();
            if ('-1' !== $value) {
                return $value;
            }
        }

        return $defaultValue;
    }

    private function saveCourseSettingValue(Course $course, string $variable, mixed $value, string $category): void
    {
        if (\is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (\is_array($value)) {
            $value = implode(',', array_map('strval', $value));
        } else {
            $value = (string) $value;
        }

        $items = $this->entityManager->getRepository(CCourseSetting::class)->findBy([
            'cId' => (int) $course->getId(),
            'variable' => $variable,
        ], ['iid' => 'ASC']);
        if ([] === $items) {
            $item = (new CCourseSetting())
                ->setCId((int) $course->getId())
                ->setVariable($variable)
                ->setTitle($variable)
                ->setCategory($category)
                ->setValue($value)
            ;
            $this->entityManager->persist($item);

            return;
        }
        foreach ($items as $item) {
            if (!$item instanceof CCourseSetting) {
                continue;
            }
            $item->setValue($value);
            if (null === $item->getCategory() || '' === $item->getCategory()) {
                $item->setCategory($category);
            }
            $this->entityManager->persist($item);
        }
    }

    /**
     * @return array{content: string, expirationLink: string, exists: bool}
     */
    private function getCourseHomeNotification(Course $course): array
    {
        $empty = ['content' => '', 'expirationLink' => '', 'exists' => false];
        if (!$this->pluginHelper->isPluginEnabled('CourseHomeNotify')
            || !$this->tableExists('course_home_notify_notification')) {
            return $empty;
        }

        $notification = $this->connection->fetchAssociative(
            'SELECT content, expiration_link FROM course_home_notify_notification WHERE c_id = :courseId LIMIT 1',
            ['courseId' => (int) $course->getId()],
            ['courseId' => Types::INTEGER],
        );
        if (false === $notification) {
            return $empty;
        }

        return [
            'content' => (string) ($notification['content'] ?? ''),
            'expirationLink' => (string) ($notification['expiration_link'] ?? ''),
            'exists' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getCourseLegalData(int $courseId, int $sessionId): array
    {
        if (!$this->tableExists('session_rel_course_legal')) {
            return [];
        }
        $result = $this->connection->fetchAssociative(
            'SELECT id, content, filename FROM session_rel_course_legal WHERE c_id = :courseId AND session_id = :sessionId LIMIT 1',
            ['courseId' => $courseId, 'sessionId' => $sessionId],
            ['courseId' => Types::INTEGER, 'sessionId' => Types::INTEGER],
        );

        return false === $result ? [] : $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function getCustomCertificateData(int $courseId, int $sessionId, int $accessUrlId): array
    {
        $defaults = [
            'id' => 0,
            'content_course' => '',
            'contents_type' => 0,
            'contents' => '',
            'date_change' => 0,
            'date_start' => '',
            'date_end' => '',
            'type_date_expediction' => 0,
            'place' => '',
            'day' => '',
            'month' => '',
            'year' => '',
            'logo_left' => '',
            'logo_center' => '',
            'logo_right' => '',
            'seal' => '',
            'signature1' => '',
            'signature2' => '',
            'signature3' => '',
            'signature4' => '',
            'signature_text1' => '',
            'signature_text2' => '',
            'signature_text3' => '',
            'signature_text4' => '',
            'background' => '',
            'margin_left' => 0,
            'margin_right' => 0,
        ];
        if (!$this->tableExists('plugin_customcertificate')) {
            return $defaults;
        }
        $result = $this->connection->fetchAssociative(
            'SELECT * FROM plugin_customcertificate WHERE c_id = :courseId AND session_id = :sessionId AND access_url_id = :accessUrlId LIMIT 1',
            ['courseId' => $courseId, 'sessionId' => $sessionId, 'accessUrlId' => $accessUrlId],
            ['courseId' => Types::INTEGER, 'sessionId' => Types::INTEGER, 'accessUrlId' => Types::INTEGER],
        );

        return false === $result ? $defaults : array_replace($defaults, $result);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCourseLegalAgreements(int $courseId, int $sessionId): array
    {
        if (!$this->tableExists('session_rel_course_rel_user_legal')) {
            return [];
        }

        return $this->connection->fetchAllAssociative(
            'SELECT
                legal.id,
                legal.user_id,
                legal.web_agreement,
                legal.web_agreement_date,
                legal.mail_agreement,
                legal.mail_agreement_date,
                user.firstname,
                user.lastname,
                user.username,
                user.email
             FROM session_rel_course_rel_user_legal legal
             INNER JOIN `user` user ON user.id = legal.user_id
             WHERE legal.c_id = :courseId
               AND legal.session_id = :sessionId
             ORDER BY user.lastname, user.firstname, user.username',
            ['courseId' => $courseId, 'sessionId' => $sessionId],
            ['courseId' => Types::INTEGER, 'sessionId' => Types::INTEGER],
        );
    }

    /**
     * @param array<string, mixed> $values
     */
    private function sendCourseLegalNotifications(Course $course, ?Session $session, array $values): void
    {
        if (!$this->pluginHelper->isPluginEnabled('CourseLegal')) {
            return;
        }

        $notificationMode = (int) ($values['course_legal_warn_users'] ?? 1);
        if ($notificationMode < 2) {
            return;
        }

        $editor = $this->security->getUser();
        if (!$editor instanceof User || '' === trim($editor->getEmail())) {
            $this->logger->warning('Course legal notification skipped because the editor has no e-mail address.', [
                'courseId' => $course->getId(),
            ]);

            return;
        }

        $users = $this->getCourseLegalNotificationUsers($course, $session);
        if ([] === $users) {
            return;
        }

        $sessionId = (int) ($session?->getId() ?? 0);
        $courseParameters = ['cid' => (int) $course->getId()];
        if ($sessionId > 0) {
            $courseParameters['sid'] = $sessionId;
        }
        $courseUrl = $this->router->generate(
            'chamilo_core_course_home',
            $courseParameters,
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $subject = 'Course agreement updated';
        $message = '<p>The course agreement for <strong>'.htmlspecialchars($course->getTitle(), ENT_QUOTES).'</strong> was updated.</p>'
            .'<p><a href="'.htmlspecialchars($courseUrl, ENT_QUOTES).'">Open the course to review the agreement</a></p>';
        $attachmentPath = null;
        if (3 === $notificationMode) {
            $legal = $this->getCourseLegalData((int) $course->getId(), $sessionId);
            $filename = basename((string) ($legal['filename'] ?? ''));
            if ('' !== $filename) {
                $candidate = $this->parameterBag->get('kernel.project_dir')
                    .'/var/upload/course_legal/course_'.(int) $course->getId()
                    .'/session_'.$sessionId.'/'.$filename;
                if (is_file($candidate)) {
                    $attachmentPath = $candidate;
                }
            }
        }

        foreach ($users as $user) {
            if (!$user instanceof User || '' === trim($user->getEmail())) {
                continue;
            }

            $email = (new Email())
                ->from($editor->getEmail())
                ->to($user->getEmail())
                ->subject($subject)
                ->html($message)
            ;

            if (null !== $attachmentPath) {
                $email->attachFromPath($attachmentPath, basename($attachmentPath));
            }

            try {
                $this->mailer->send($email);
            } catch (Throwable $exception) {
                $this->logger->error('Course legal notification could not be sent.', [
                    'courseId' => $course->getId(),
                    'sessionId' => $sessionId,
                    'userId' => $user->getId(),
                    'exception' => $exception,
                ]);
            }
        }
    }

    /**
     * @return array<int, User>
     */
    private function getCourseLegalNotificationUsers(Course $course, ?Session $session): array
    {
        if (null === $session) {
            $result = $this->courseRepository->getSubscribedStudents($course)->getQuery()->getResult();

            return array_values(array_filter($result, static fn (mixed $item): bool => $item instanceof User));
        }

        $relations = $this->entityManager->getRepository(SessionRelCourseRelUser::class)->findBy([
            'course' => $course,
            'session' => $session,
            'status' => Session::STUDENT,
        ]);
        $users = [];
        foreach ($relations as $relation) {
            if (!$relation instanceof SessionRelCourseRelUser) {
                continue;
            }
            $user = $relation->getUser();
            if (!$user instanceof User) {
                continue;
            }
            $users[(int) $user->getId()] = $user;
        }

        return array_values($users);
    }

    private function logCourseSettingsUpdate(Course $course, ?Session $session): void
    {
        $user = $this->security->getUser();
        $userId = $user instanceof User ? (int) $user->getId() : null;

        $this->eventLoggerHelper->addEvent(
            'course_settings_updated',
            'course_id',
            (int) $course->getId(),
            null,
            $userId,
            (int) $course->getId(),
            (int) ($session?->getId() ?? 0),
        );
    }

    private function hasActiveBuyCoursesService(int $courseId): bool
    {
        if (!$this->pluginHelper->isPluginEnabled('BuyCourses')
            || !$this->tableExists('plugin_buycourses_subscription_course')
            || !$this->tableExists('plugin_buycourses_service_sale')) {
            return false;
        }

        return false !== $this->connection->fetchOne(
            "SELECT 1
             FROM plugin_buycourses_subscription_course sc
             INNER JOIN plugin_buycourses_service_sale ss ON ss.id = sc.service_sale_id
             WHERE sc.course_id = :courseId
               AND sc.status = 'active'
               AND sc.deleted_at IS NULL
               AND ss.status = 1
               AND (ss.date_start IS NULL OR ss.date_start <= UTC_TIMESTAMP())
               AND ss.date_end IS NOT NULL
               AND ss.date_end >= UTC_TIMESTAMP()
             LIMIT 1",
            ['courseId' => $courseId],
            ['courseId' => Types::INTEGER],
        );
    }

    private function tableExists(string $tableName): bool
    {
        return $this->connection->createSchemaManager()->tablesExist([$tableName]);
    }

    private function hasCourseExtraField(string $variable): bool
    {
        return $this->extraFieldRepository->findByVariable(ExtraField::COURSE_FIELD_TYPE, $variable) instanceof ExtraField;
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function getLanguageOptions(): array
    {
        $options = [];
        foreach ($this->languageRepository->findBy(['available' => true], ['englishName' => 'ASC']) as $language) {
            $options[] = [
                'label' => $language->getOriginalName() ?: $language->getEnglishName(),
                'value' => $language->getIsocode(),
            ];
        }

        return $options;
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    private function getRoomOptions(): array
    {
        $options = [['label' => 'None', 'value' => 0]];
        foreach ($this->entityManager->getRepository(Room::class)->findBy([], ['title' => 'ASC']) as $room) {
            if (!$room instanceof Room) {
                continue;
            }
            $label = $room->getTitle();
            $branch = $room->getBranch();
            if (null !== $branch) {
                $label = $branch->getTitle().' - '.$label;
            }
            $options[] = ['label' => $label, 'value' => (int) $room->getId()];
        }

        return $options;
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function getThemeOptions(): array
    {
        $options = [['label' => 'Platform default', 'value' => '']];
        foreach ($this->themesFilesystem->listContents('', false) as $item) {
            if (!$item->isDir()) {
                continue;
            }
            $theme = basename($item->path());
            $options[] = ['label' => ucfirst(str_replace(['-', '_'], ' ', $theme)), 'value' => $theme];
        }
        usort($options, static fn (array $left, array $right): int => strnatcasecmp($left['label'], $right['label']));

        return $options;
    }

    private function canSaveCourseSetting(string $variable, int $courseId): bool
    {
        if (isset(self::AI_FEATURES[$variable])) {
            return $this->isAiFeatureConfigurable($variable, $courseId);
        }
        if (\in_array($variable, self::COURSE_BLOCK_SETTINGS, true)) {
            return $this->pluginHelper->isPluginEnabled('CourseBlock');
        }

        return match ($variable) {
            'documents_default_visibility' => $this->settingEnabled('documents_default_visibility_defined_in_course'),
            'show_system_folders' => $this->settingEnabled('show_default_folders'),
            'course_theme', 'allow_learning_path_theme' => $this->settingEnabled('allow_course_theme'),
            'lp_return_link' => $this->settingEnabled('lp.allow_lp_return_link'),
            'exercise_invisible_in_session' => $this->settingEnabled('exercise_invisible_in_session')
                && $this->settingEnabled('configure_exercise_visibility_in_course'),
            'allow_public_certificates' => $this->settingEnabled('certificate.allow_public_certificates'),
            'pdf_export_watermark_text' => $this->settingEnabled('pdf_export_watermark_by_course'),
            'customcertificate_course_enable', 'use_certificate_default' => $this->pluginHelper->isPluginEnabled('CustomCertificate'),
            default => true,
        };
    }

    private function isAiFeatureConfigurable(string $feature, int $courseId): bool
    {
        return $this->settingEnabled('ai_helpers.enable_ai_helpers')
            && $this->aiFeatureAccessHelper->isFeatureConfigurableForCourse($feature, $courseId);
    }

    private function settingEnabled(string $key): bool
    {
        return $this->toBool($this->settingsManager->getSetting($key, true));
    }

    private function toBool(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param array<int, string> $allowedValues
     *
     * @return array<int, string>
     */
    private function normalizeStringList(mixed $value, array $allowedValues): array
    {
        $items = \is_array($value) ? $value : explode(',', (string) $value);
        $normalized = [];
        foreach ($items as $item) {
            $item = trim((string) $item);
            if ('' !== $item && \in_array($item, $allowedValues, true)) {
                $normalized[] = $item;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param array<string, mixed> $values
     */
    private function resolveAutoLaunchOption(array $values): string
    {
        if (1 === (int) ($values['enable_document_auto_launch'] ?? 0)) {
            return 'enable_document_auto_launch';
        }
        if (1 === (int) ($values['enable_lp_auto_launch'] ?? 0)) {
            return 'enable_lp_auto_launch';
        }
        if (2 === (int) ($values['enable_lp_auto_launch'] ?? 0)) {
            return 'enable_lp_auto_launch_list';
        }
        if (1 === (int) ($values['enable_exercise_auto_launch'] ?? 0)) {
            return 'enable_exercise_auto_launch';
        }
        if (2 === (int) ($values['enable_exercise_auto_launch'] ?? 0)) {
            return 'enable_exercise_auto_launch_list';
        }
        if (1 === (int) ($values['enable_forum_auto_launch'] ?? 0)) {
            return 'enable_forum_auto_launch';
        }

        return 'disable_auto_launch';
    }

    /**
     * @param array<string, mixed> $values
     */
    private function resolveCustomCertificateMode(array $values): string
    {
        if (1 === (int) ($values['use_certificate_default'] ?? 0)) {
            return 'default';
        }
        if (1 === (int) ($values['customcertificate_course_enable'] ?? 0)) {
            return 'course';
        }

        return 'disabled';
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    private function yesNoOptions(int $yesValue, int $noValue): array
    {
        return [
            ['label' => 'Yes', 'value' => $yesValue],
            ['label' => 'No', 'value' => $noValue],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     *
     * @return array<string, mixed>
     */
    private function section(string $key, string $title, string $icon, array $fields, bool $collapsed = false): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'icon' => $icon,
            'collapsed' => $collapsed,
            'fields' => $fields,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $options
     *
     * @return array<string, mixed>
     */
    private function field(
        string $key,
        string $label,
        string $type,
        array $options = [],
        bool $required = false,
        bool $disabled = false,
        ?int $min = null,
        ?int $max = null,
        string $help = '',
        mixed $value = null,
    ): array {
        return array_filter([
            'key' => $key,
            'label' => $label,
            'type' => $type,
            'options' => $options,
            'required' => $required,
            'disabled' => $disabled,
            'min' => $min,
            'max' => $max,
            'help' => $help,
            'value' => $value,
        ], static fn (mixed $item): bool => null !== $item && [] !== $item && '' !== $item && false !== $item);
    }

    private function normalizeDate(string $value): string
    {
        $value = trim($value);
        if ('' === $value) {
            return '1970-01-01 00:00:00';
        }

        return date('Y-m-d 00:00:00', strtotime($value) ?: 0);
    }

    private function getWatermarkPath(Course $course, int $accessUrlId): string
    {
        $projectDir = (string) $this->parameterBag->get('kernel.project_dir');
        $directory = trim((string) $course->getDirectory(), '/');

        return $projectDir.'/public/courses/'.$directory.'/'.$accessUrlId.'_pdf_watermark.png';
    }
}
