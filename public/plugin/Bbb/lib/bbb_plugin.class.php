<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CoreBundle\Entity\Course;

/**
 * BigBlueButton plugin configuration class.
 * Handles plugin options and course settings.
 */
class BbbPlugin extends Plugin
{
    const ROOM_OPEN = 0;
    const ROOM_CLOSE = 1;
    const ROOM_CHECK = 2;

    public $isCoursePlugin = true;

    // Default course settings when creating a new course
    public $course_settings = [
        ['name' => 'big_blue_button_record_and_store', 'type' => 'checkbox'],
        ['name' => 'bbb_enable_conference_in_groups', 'type' => 'checkbox'],
        ['name' => 'bbb_force_record_generation', 'type' => 'checkbox'],
        ['name' => 'big_blue_button_students_start_conference_in_groups', 'type' => 'checkbox'],
    ];

    /**
     * BBBPlugin constructor.
     * Defines all available plugin settings.
     */
    protected function __construct()
    {
        parent::__construct(
            '2.9',
            'Julio Montoya, Yannick Warnier, Angel Fernando Quiroz Campos, Jose Angel Ruiz',
            [
                'tool_enable' => 'boolean',
                'host' => 'text',
                'salt' => 'text',
                'enable_global_conference' => 'boolean',
                'enable_global_conference_per_user' => 'boolean',
                'enable_conference_in_course_groups' => 'boolean',
                'enable_global_conference_link' => 'boolean',
                'disable_download_conference_link' => 'boolean',
                'max_users_limit' => 'text',
                'global_conference_allow_roles' => [
                    'type' => 'select',
                    'options' => [
                        PLATFORM_ADMIN => get_lang('Administrator'),
                        COURSEMANAGER => get_lang('Teacher'),
                        STUDENT => get_lang('Student'),
                        STUDENT_BOSS => get_lang('StudentBoss'),
                    ],
                    'attributes' => ['multiple' => 'multiple'],
                ],
                'allow_regenerate_recording' => 'boolean',
                'big_blue_button_record_and_store' => 'checkbox',
                'bbb_enable_conference_in_groups' => 'checkbox',
                'bbb_force_record_generation' => 'checkbox',
                'disable_course_settings' => 'boolean',
                'meeting_duration' => 'text',
            ]
        );

        $this->isAdminPlugin = true;
    }

    /**
     * Returns a singleton instance of the plugin.
     */
    public static function create(): self
    {
        static $result = null;
        return $result ??= new self();
    }

    /**
     * Validates if a course setting is enabled depending on global plugin configuration.
     */
    public function validateCourseSetting($variable): bool
    {
        if ($this->get('disable_course_settings') === 'true') {
            return false;
        }

        switch ($variable) {
            case 'bbb_enable_conference_in_groups':
                return $this->get('enable_conference_in_course_groups') === 'true';
            case 'bbb_force_record_generation':
                return $this->get('allow_regenerate_recording') === 'true';
            default:
                return true;
        }
    }

    /**
     * Returns course-level plugin settings if not disabled globally.
     */
    public function getCourseSettings(): array
    {
        if ($this->get('disable_course_settings') === 'true') {
            return [];
        }

        return parent::getCourseSettings();
    }

    /**
     * Performs automatic updates to all course settings after configuration changes.
     */
    public function performActionsAfterConfigure(): self
    {
        if ($this->get('disable_course_settings') === 'true') {
            self::updateCourseFieldInAllCourses(
                'bbb_enable_conference_in_groups',
                $this->get('enable_conference_in_course_groups') === 'true' ? 1 : 0
            );
            self::updateCourseFieldInAllCourses(
                'bbb_force_record_generation',
                $this->get('allow_regenerate_recording') === 'true' ? 1 : 0
            );
            self::updateCourseFieldInAllCourses(
                'big_blue_button_record_and_store',
                $this->get('big_blue_button_record_and_store') === 'true' ? 1 : 0
            );
        }

        return $this;
    }

    /**
     * Updates a course setting value across all existing courses.
     */
    public static function updateCourseFieldInAllCourses(string $variable, string $value): void
    {
        $entityManager = Database::getManager();
        $courseRepo = $entityManager->getRepository(Course::class);
        $settingRepo = $entityManager->getRepository(CCourseSetting::class);

        $courses = $courseRepo->createQueryBuilder('c')
            ->select('c.id')
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getArrayResult();

        foreach ($courses as $course) {
            $setting = $settingRepo->findOneBy([
                'variable' => $variable,
                'cId' => $course['id'],
            ]);

            if ($setting) {
                $setting->setValue($value);
                $entityManager->persist($setting);
            }
        }

        $entityManager->flush();
    }

    public function canCurrentUserSeeGlobalConferenceLink(): bool
    {
        $allowedStatuses = $this->get('global_conference_allow_roles') ?? [];

        if (empty($allowedStatuses)) {
            return api_is_platform_admin();
        }

        foreach ($allowedStatuses as $status) {
            switch ((int) $status) {
                case PLATFORM_ADMIN:
                    if (api_is_platform_admin()) {
                        return true;
                    }
                    break;
                case COURSEMANAGER:
                    if (api_is_teacher()) {
                        return true;
                    }
                    break;
                case STUDENT:
                    if (api_is_student()) {
                        return true;
                    }
                    break;
                case STUDENT_BOSS:
                    if (api_is_student_boss()) {
                        return true;
                    }
                    break;
            }
        }

        return false;
    }

    public function get_name(): string
    {
        return 'Bbb';
    }
}
