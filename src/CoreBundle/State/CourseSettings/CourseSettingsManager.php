<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseSettings;

use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AiFeatureAccessHelper;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\CourseTimeSpentClockHelper;
use Chamilo\CoreBundle\Helpers\EventLoggerHelper;
use Chamilo\CoreBundle\Helpers\ForumParticipationSettingsHelper;
use Chamilo\CoreBundle\Helpers\ResourceDefaultVisibilityHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Settings\CourseSettingPolicyManager;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use LogicException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use const FILTER_VALIDATE_URL;

final readonly class CourseSettingsManager
{
    /**
     * The form value of "Use the platform setting", stored as an empty value. PrimeVue never shows
     * a radio button with an empty value as checked, so the form cannot use the stored value.
     */
    private const string PLATFORM_DEFAULT_CHOICE = 'platform';
    private const array PLATFORM_DEFAULT_YES_NO_SETTINGS = [
        ForumParticipationSettingsHelper::REPLIES_AFTER_PARTICIPATION,
        ForumParticipationSettingsHelper::CATEGORY_DESCRIPTION,
        CourseTimeSpentClockHelper::SETTING,
    ];
    private const array EXTRA_FIELD_VARIABLES = [
        'tags',
        'video_url',
        'course_hours_duration',
        'max_subscribed_students',
    ];
    public const string INTRODUCTION_POSITION_SETTING = 'introduction_position';
    public const string INTRODUCTION_POSITION_CATEGORY = 'course_homepage';
    public const string INTRODUCTION_POSITION_DEFAULT = 'above';
    public const array INTRODUCTION_POSITIONS = [
        'above',
        'below',
        'left',
        'right',
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
        'document_default_visibility_on_creation' => 'document',
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
        'exercise_default_visibility_on_creation' => 'exercise',
        'lp_default_visibility_on_creation' => 'learning_path',
        ForumParticipationSettingsHelper::REPLIES_AFTER_PARTICIPATION => 'forum',
        ForumParticipationSettingsHelper::CATEGORY_DESCRIPTION => 'forum',
        ForumParticipationSettingsHelper::PROGRESS_POSTS => 'forum',
        self::INTRODUCTION_POSITION_SETTING => self::INTRODUCTION_POSITION_CATEGORY,
        'display_info_advance_inside_homecourse' => 'thematic_advance',
        'allow_public_certificates' => 'certificates',
        'customcertificate_course_enable' => '',
        'use_certificate_default' => '',
        'hide_forum_notifications' => 'forum',
        'subscribe_users_to_forum_notifications' => 'forum',
        'student_delete_own_publication' => 'work',
        'student_validate_own_attendance' => 'attendance',
        CourseTimeSpentClockHelper::SETTING => CourseTimeSpentClockHelper::COURSE_SETTING_CATEGORY,
        'enable_document_auto_launch' => 'document',
        'enable_lp_auto_launch' => 'learning_path',
        'enable_exercise_auto_launch' => 'exercise',
        'enable_forum_auto_launch' => 'forum',
        'pdf_export_watermark_text' => 'learning_path',
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

    /**
     * The course setting holding the default visibility of each tool's new resources.
     */
    private const array DEFAULT_VISIBILITY_SETTINGS = [
        'document_default_visibility_on_creation' => ResourceDefaultVisibilityHelper::DOCUMENT,
        'exercise_default_visibility_on_creation' => ResourceDefaultVisibilityHelper::EXERCISE,
        'lp_default_visibility_on_creation' => ResourceDefaultVisibilityHelper::LEARNING_PATH,
    ];
    private const array DEFAULT_VALUES = [
        'show_course_in_user_language' => 2,
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
        self::INTRODUCTION_POSITION_SETTING => self::INTRODUCTION_POSITION_DEFAULT,
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
        self::INTRODUCTION_POSITION_SETTING,
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
        private Security $security,
        private SettingsManager $settingsManager,
        private CourseSettingPolicyManager $courseSettingPolicyManager,
        private AccessUrlHelper $accessUrlHelper,
        private CourseSettingsPluginRegistry $courseSettingsPluginRegistry,
        private LanguageRepository $languageRepository,
        private ExtraFieldRepository $extraFieldRepository,
        private ExtraFieldValuesRepository $extraFieldValuesRepository,
        private IllustrationRepository $illustrationRepository,
        private AiFeatureAccessHelper $aiFeatureAccessHelper,
        private AiProviderFactory $aiProviderFactory,
        private ParameterBagInterface $parameterBag,
        private EventLoggerHelper $eventLoggerHelper,
        private ResourceDefaultVisibilityHelper $resourceDefaultVisibilityHelper,
        private ForumParticipationSettingsHelper $forumParticipationSettingsHelper,
        private CourseTimeSpentClockHelper $courseTimeSpentClockHelper,
        #[Autowire(service: 'oneup_flysystem.themes_filesystem')]
        private FilesystemOperator $themesFilesystem,
    ) {}

    /**
     * Central catalog for global course-setting policies. Dynamic settings
     * already stored in c_course_setting are added by the admin page.
     *
     * @return array<string, string>
     */
    public static function getPolicyDefaults(): array
    {
        $defaults = [];
        foreach (self::COURSE_SETTING_CATEGORIES as $variable => $category) {
            if (\array_key_exists($variable, self::DEFAULT_VISIBILITY_SETTINGS)) {
                $tool = self::DEFAULT_VISIBILITY_SETTINGS[$variable];
                $defaults[$variable] = ResourceDefaultVisibilityHelper::getDefaults()[$tool];
            } elseif (isset(self::AI_FEATURES[$variable])) {
                $defaults[$variable] = 'false';
            } else {
                $defaults[$variable] = (string) (self::DEFAULT_VALUES[$variable] ?? '');
            }
        }

        return $defaults;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function getPolicyAllowedValues(): array
    {
        $values = [];
        foreach (self::ALLOWED_INTEGER_VALUES as $variable => $options) {
            $values[$variable] = array_map('strval', $options);
        }
        foreach (array_keys(self::AI_FEATURES) as $variable) {
            $values[$variable] = ['true', 'false'];
        }
        foreach (array_keys(self::DEFAULT_VISIBILITY_SETTINGS) as $variable) {
            $values[$variable] = [ResourceDefaultVisibilityHelper::VISIBLE, ResourceDefaultVisibilityHelper::INVISIBLE];
        }
        foreach (ForumParticipationSettingsHelper::SETTINGS as $variable) {
            if (ForumParticipationSettingsHelper::PROGRESS_POSTS !== $variable) {
                $values[$variable] = ['true', 'false', ''];
            }
        }
        $values[CourseTimeSpentClockHelper::SETTING] = ['true', 'false', ''];
        $values[self::INTRODUCTION_POSITION_SETTING] = self::INTRODUCTION_POSITIONS;

        return $values;
    }

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

    /**
     * Course automation must follow the same edit/visibility policy as Course Settings.
     */
    public function canChangeVisibilityForAutomation(Course $course): bool
    {
        try {
            $this->assertCanEdit($course);
        } catch (AccessDeniedHttpException) {
            return false;
        }

        return (bool) $this->getPermissions($course)['canChangeVisibility'];
    }

    /**
     * Change only course visibility, retaining the regular hosting limit and
     * administrator-only hidden-course rules without saving unrelated settings.
     */
    public function changeVisibilityForAutomation(Course $course, int $visibility): void
    {
        $this->assertCanEdit($course);
        $permissions = $this->getPermissions($course);
        if (!\in_array($visibility, [Course::CLOSED, Course::REGISTERED, Course::OPEN_PLATFORM, Course::OPEN_WORLD, Course::HIDDEN], true)) {
            throw new BadRequestHttpException('Invalid course visibility.');
        }
        if (Course::HIDDEN === $visibility && empty($permissions['canUseHiddenVisibility'])) {
            throw new AccessDeniedHttpException('Only platform administrators can hide a course.');
        }
        if ($visibility !== $course->getVisibility() && empty($permissions['canChangeVisibility'])) {
            throw new AccessDeniedHttpException('You are not allowed to change course visibility.');
        }
        $this->validateActiveCourseLimit($course, $visibility);

        if ($visibility !== $course->getVisibility()) {
            $course->setVisibility($visibility);
            $this->entityManager->flush();
        }
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

    public function saveIntroductionPosition(string $position): string
    {
        [$course, $session] = $this->resolveContext();
        // Authorize against the Course entity so CourseVoter applies (course
        // teachers and admins). Do not use EDIT on the course resource node.
        if (!$this->security->isGranted('ROLE_ADMIN') && !$this->security->isGranted('EDIT', $course)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this course.');
        }

        $position = trim($position);
        if (!\in_array($position, self::INTRODUCTION_POSITIONS, true)) {
            throw new BadRequestHttpException('Invalid introduction position.');
        }

        if (!$this->courseSettingPolicyManager->isEditable(
            self::INTRODUCTION_POSITION_SETTING,
            self::INTRODUCTION_POSITION_CATEGORY,
        ) || !$this->canSaveCourseSetting(self::INTRODUCTION_POSITION_SETTING, $course)) {
            throw new AccessDeniedHttpException('You are not allowed to change the introduction position.');
        }

        $this->saveCourseSettingValue(
            $course,
            self::INTRODUCTION_POSITION_SETTING,
            $position,
            self::INTRODUCTION_POSITION_CATEGORY,
        );
        $this->entityManager->flush();
        $this->eventLoggerHelper->addEvent(
            'course_settings_updated',
            self::INTRODUCTION_POSITION_SETTING,
            $position,
            null,
            $this->security->getUser() instanceof User ? (int) $this->security->getUser()->getId() : null,
            (int) $course->getId(),
            (int) ($session?->getId() ?? 0),
        );

        return $position;
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
            'sections' => $this->buildSections($course, $session, $values, $permissions, $integrations),
            'permissions' => $permissions,
            'media' => $this->getMedia($course, $session, $values),
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

        $savedPluginValues = [];
        $this->entityManager->wrapInTransaction(function () use ($course, $session, $values, $permissions, $submittedValues, $currentValues, &$savedPluginValues): void {
            $this->saveCourseEntity($course, $values, $permissions);
            $this->saveCourseSettings($course, $values);
            $this->saveExtraFields($course, $values);
            $savedPluginValues = $this->courseSettingsPluginRegistry->save($course, $session, $submittedValues, $currentValues);
            $this->entityManager->flush();
        });

        $this->courseSettingsPluginRegistry->afterSave($course, $session, $savedPluginValues);
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

        // Without a choice of the course, the form shows the platform default it applies.
        foreach (self::DEFAULT_VISIBILITY_SETTINGS as $variable => $tool) {
            $values[$variable] = $this->resourceDefaultVisibilityHelper->getVisibility($tool, $course);
        }

        // An empty value means the course follows the platform setting.
        foreach (ForumParticipationSettingsHelper::SETTINGS as $variable) {
            $values[$variable] = $this->forumParticipationSettingsHelper->getCourseValue($variable, $course);
        }
        $values[CourseTimeSpentClockHelper::SETTING] = $this->courseTimeSpentClockHelper->getCourseValue($course);
        // Saving turns the choice back into an empty value: both helpers only keep "true" and "false".
        foreach (self::PLATFORM_DEFAULT_YES_NO_SETTINGS as $variable) {
            if ('' === $values[$variable]) {
                $values[$variable] = self::PLATFORM_DEFAULT_CHOICE;
            }
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

        $pluginValues = $this->courseSettingsPluginRegistry->getValues($course, $session);
        if ([] !== array_intersect_key($pluginValues, $values)) {
            throw new LogicException('A plugin course setting conflicts with a core course setting.');
        }
        $values = array_replace($values, $pluginValues);

        return $this->normalizeLoadedSelectableValues($values);
    }

    /**
     * @return array<string, mixed>
     */
    private function getPermissions(Course $course): array
    {
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        $visibilityAdminsOnly = $this->settingEnabled('workflows.course_visibility_change_only_admin');
        $paidCourse = $this->courseSettingsPluginRegistry->hasActivePaidCourse($course);

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
        $aiEnabled = $this->settingEnabled('ai_helpers.enable_ai_helpers');
        $canGeneratePicture = $aiEnabled
            && $this->aiProviderFactory->hasProvidersForType('image')
            && $this->aiFeatureAccessHelper->isFeatureEnabledForCourse('image_generator', $courseId);

        return $this->courseSettingsPluginRegistry->getIntegrations($course, $session) + [
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
    private function getMedia(Course $course, ?Session $session, array $values): array
    {
        $accessUrlId = (int) ($this->accessUrlHelper->getCurrent()?->getId() ?? 0);
        $watermarkPath = $this->getWatermarkPath($course, $accessUrlId);

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
            'courseLegalFileExists' => '' !== (string) ($values['course_legal_filename'] ?? ''),
            'courseLegalFileName' => (string) ($values['course_legal_filename'] ?? ''),
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
    private function buildSections(Course $course, ?Session $session, array $values, array $permissions, array $integrations): array
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

        $sections[] = $this->section('course_homepage', 'Course homepage', 'courses', [
            $this->field('introduction_position', 'Course introduction position', 'radio', options: [
                ['label' => 'Above the tools', 'value' => 'above'],
                ['label' => 'Below the tools', 'value' => 'below'],
                ['label' => 'Left of the tools', 'value' => 'left'],
                ['label' => 'Right of the tools', 'value' => 'right'],
            ], help: 'On smaller screens, left and right are shown above the tools. If the course sequence column is also shown, a right-hand introduction is displayed above the tools so the two columns do not overlap.'),
        ]);

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
        if ($this->resourceDefaultVisibilityHelper->canBeModifiedInCourse(ResourceDefaultVisibilityHelper::DOCUMENT)) {
            $documentFields[] = $this->defaultVisibilityField('document_default_visibility_on_creation', 'Default visibility for new documents');
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
        if ($this->resourceDefaultVisibilityHelper->canBeModifiedInCourse(ResourceDefaultVisibilityHelper::LEARNING_PATH)) {
            $learningPathFields[] = $this->defaultVisibilityField('lp_default_visibility_on_creation', 'Default visibility for new learning paths');
        }
        $sections[] = $this->section('learning_path', 'Learning path settings', 'learning-paths', $learningPathFields, collapsed: true);

        if ($this->resourceDefaultVisibilityHelper->canBeModifiedInCourse(ResourceDefaultVisibilityHelper::EXERCISE)) {
            $sections[] = $this->section('tests', 'Tests', 'order-bool-ascending-variant', [
                $this->defaultVisibilityField('exercise_default_visibility_on_creation', 'Default visibility for new tests'),
            ], collapsed: true);
        }

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

        $forumFields = [
            $this->field('hide_forum_notifications', 'Hide forum notifications', 'radio', options: $this->yesNoOptions(1, 2)),
            $this->field('subscribe_users_to_forum_notifications', 'Automatically subscribe users to forum notifications', 'radio', options: $this->yesNoOptions(1, 2)),
        ];
        if ($this->forumParticipationSettingsHelper->canBeModifiedInCourse()) {
            $forumFields[] = $this->platformDefaultYesNoField(ForumParticipationSettingsHelper::REPLIES_AFTER_PARTICIPATION, 'Show thread replies only after participating');
            $forumFields[] = $this->platformDefaultYesNoField(ForumParticipationSettingsHelper::CATEGORY_DESCRIPTION, 'Show the forum category description in threads');
            $forumFields[] = $this->field(
                ForumParticipationSettingsHelper::PROGRESS_POSTS,
                'Posts required to complete a forum thread',
                'text',
                help: 'Leave empty to use the platform setting, or set 0 to hide the participation progress bar.',
            );
        }
        $sections[] = $this->section('forum', 'Forum', 'add-topic', $forumFields, collapsed: true);

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

        if ($this->courseTimeSpentClockHelper->canBeModifiedInCourse()) {
            $sections[] = $this->section('time_spent_clock', 'Time spent in the course', 'tracking', [
                $this->platformDefaultYesNoField(CourseTimeSpentClockHelper::SETTING, 'Show learners the time spent in the course'),
            ], collapsed: true);
        }

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

        foreach ($this->courseSettingsPluginRegistry->getSections($course, $session, $values) as $section) {
            $sections[] = $section;
        }

        foreach ($sections as &$section) {
            $section['fields'] = array_values(array_filter(
                $section['fields'],
                function (array $field): bool {
                    if (!$this->courseSettingPolicyManager->isEditable(
                        (string) $field['key'],
                        self::COURSE_SETTING_CATEGORIES[$field['key']] ?? null,
                    )) {
                        return false;
                    }
                    if ('auto_launch_option' === $field['key']) {
                        foreach (['enable_document_auto_launch', 'enable_lp_auto_launch', 'enable_exercise_auto_launch', 'enable_forum_auto_launch'] as $variable) {
                            if (!$this->courseSettingPolicyManager->isEditable($variable, self::COURSE_SETTING_CATEGORIES[$variable])) {
                                return false;
                            }
                        }
                    }

                    return true;
                },
            ));
        }
        unset($section);

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

        foreach (self::DEFAULT_VISIBILITY_SETTINGS as $variable => $tool) {
            $values[$variable] = $this->normalizeDefaultVisibility($values[$variable] ?? '', $tool);
        }

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

        $introductionPosition = (string) ($values[self::INTRODUCTION_POSITION_SETTING] ?? '');
        $values[self::INTRODUCTION_POSITION_SETTING] = \in_array(
            $introductionPosition,
            self::INTRODUCTION_POSITIONS,
            true,
        ) ? $introductionPosition : self::INTRODUCTION_POSITION_DEFAULT;

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

        foreach (self::DEFAULT_VISIBILITY_SETTINGS as $variable => $tool) {
            $normalized[$variable] = $this->normalizeDefaultVisibility($values[$variable] ?? '', $tool);
        }
        $progressPosts = trim((string) ($values[ForumParticipationSettingsHelper::PROGRESS_POSTS] ?? ''));
        if ('' !== $progressPosts && !ctype_digit($progressPosts)) {
            throw new BadRequestHttpException('The number of posts required to complete a forum thread must be a whole number.');
        }
        foreach (ForumParticipationSettingsHelper::SETTINGS as $variable) {
            $normalized[$variable] = ForumParticipationSettingsHelper::normalizeCourseValue($variable, $values[$variable] ?? '');
        }
        $normalized[CourseTimeSpentClockHelper::SETTING] = CourseTimeSpentClockHelper::normalizeCourseValue($values[CourseTimeSpentClockHelper::SETTING] ?? '');
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
        if (\array_key_exists(self::INTRODUCTION_POSITION_SETTING, $values)
            && !\in_array((string) $values[self::INTRODUCTION_POSITION_SETTING], self::INTRODUCTION_POSITIONS, true)) {
            throw new BadRequestHttpException('Invalid introduction position.');
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
        $language = $this->languageRepository->findByIsoCode((string) $values['course_language']);
        if (!$language instanceof Language) {
            throw new BadRequestHttpException('Invalid course language.');
        }

        $course
            ->setTitle((string) $values['title'])
            ->setCourseLanguageEntity($language)
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
            if (!$this->courseSettingPolicyManager->isEditable($variable, $category)
                || !$this->canSaveCourseSetting($variable, $course)) {
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

    private function getCourseSettingValue(Course $course, string $variable, mixed $defaultValue): mixed
    {
        $items = $this->entityManager->getRepository(CCourseSetting::class)->findBy([
            'cId' => (int) $course->getId(),
            'variable' => $variable,
        ], ['iid' => 'ASC']);
        $courseValue = null;
        foreach ($items as $item) {
            if (!$item instanceof CCourseSetting || null === $item->getValue()) {
                continue;
            }
            $value = $item->getValue();
            if ('-1' !== $value) {
                $courseValue = $value;

                break;
            }
        }

        return $this->courseSettingPolicyManager->effectiveValue(
            $variable,
            $courseValue,
            $defaultValue,
            self::COURSE_SETTING_CATEGORIES[$variable] ?? null,
        );
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

    private function canSaveCourseSetting(string $variable, Course $course): bool
    {
        if (isset(self::AI_FEATURES[$variable])) {
            return $this->isAiFeatureConfigurable($variable, (int) $course->getId());
        }

        return match ($variable) {
            'document_default_visibility_on_creation' => $this->resourceDefaultVisibilityHelper->canBeModifiedInCourse(ResourceDefaultVisibilityHelper::DOCUMENT),
            'exercise_default_visibility_on_creation' => $this->resourceDefaultVisibilityHelper->canBeModifiedInCourse(ResourceDefaultVisibilityHelper::EXERCISE),
            'lp_default_visibility_on_creation' => $this->resourceDefaultVisibilityHelper->canBeModifiedInCourse(ResourceDefaultVisibilityHelper::LEARNING_PATH),
            ForumParticipationSettingsHelper::REPLIES_AFTER_PARTICIPATION,
            ForumParticipationSettingsHelper::CATEGORY_DESCRIPTION,
            ForumParticipationSettingsHelper::PROGRESS_POSTS => $this->forumParticipationSettingsHelper->canBeModifiedInCourse(),
            CourseTimeSpentClockHelper::SETTING => $this->courseTimeSpentClockHelper->canBeModifiedInCourse(),
            'show_system_folders' => $this->settingEnabled('show_default_folders'),
            'course_theme', 'allow_learning_path_theme' => $this->settingEnabled('allow_course_theme'),
            'lp_return_link' => $this->settingEnabled('lp.allow_lp_return_link'),
            'exercise_invisible_in_session' => $this->settingEnabled('exercise_invisible_in_session')
                && $this->settingEnabled('configure_exercise_visibility_in_course'),
            'allow_public_certificates' => $this->settingEnabled('certificate.allow_public_certificates'),
            'pdf_export_watermark_text' => $this->settingEnabled('pdf_export_watermark_by_course'),
            'customcertificate_course_enable', 'use_certificate_default' => $this->courseSettingsPluginRegistry->managesCourseSetting($variable, $course, $this->cidReqHelper->getDoctrineSessionEntity()),
            default => true,
        };
    }

    private function isAiFeatureConfigurable(string $feature, int $courseId): bool
    {
        return $this->settingEnabled('ai_helpers.enable_ai_helpers')
            && $this->aiFeatureAccessHelper->isFeatureConfigurableForCourse($feature, $courseId);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultVisibilityField(string $name, string $label): array
    {
        return $this->field($name, $label, 'radio', options: [
            ['label' => 'Visible', 'value' => ResourceDefaultVisibilityHelper::VISIBLE],
            ['label' => 'Invisible', 'value' => ResourceDefaultVisibilityHelper::INVISIBLE],
        ]);
    }

    /**
     * A yes/no choice whose empty value keeps the platform setting.
     *
     * @return array<string, mixed>
     */
    private function platformDefaultYesNoField(string $name, string $label): array
    {
        return $this->field($name, $label, 'radio', options: [
            ['label' => 'Use the platform setting', 'value' => self::PLATFORM_DEFAULT_CHOICE],
            ['label' => 'Yes', 'value' => 'true'],
            ['label' => 'No', 'value' => 'false'],
        ]);
    }

    private function normalizeDefaultVisibility(mixed $value, string $tool): string
    {
        return \in_array($value, [ResourceDefaultVisibilityHelper::VISIBLE, ResourceDefaultVisibilityHelper::INVISIBLE], true)
            ? $value
            : $this->resourceDefaultVisibilityHelper->getPlatformVisibility($tool);
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

    private function getWatermarkPath(Course $course, int $accessUrlId): string
    {
        $projectDir = (string) $this->parameterBag->get('kernel.project_dir');
        $directory = trim((string) $course->getDirectory(), '/');

        return $projectDir.'/public/courses/'.$directory.'/'.$accessUrlId.'_pdf_watermark.png';
    }
}
