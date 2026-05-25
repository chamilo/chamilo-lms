<?php

/* For licensing terms, see /license.txt */

/**
 * Class CourseBlockPlugin.
 */
class CourseBlockPlugin extends Plugin
{
    private const REGION_SETTING_MAP = [
        'footer_left' => 'course_block_footer_left',
        'footer_center' => 'course_block_footer_center',
        'footer_right' => 'course_block_footer_right',
        'pre_footer' => 'course_block_pre_footer',
    ];

    public $isCoursePlugin = true;
    public $addCourseTool = false;

    // When creating a new course these settings are added to the course.
    public $course_settings = [
        [
            'name' => 'course_block_pre_footer',
            'type' => 'textarea',
        ],
        [
            'name' => 'course_block_footer_left',
            'type' => 'textarea',
        ],
        [
            'name' => 'course_block_footer_center',
            'type' => 'textarea',
        ],
        [
            'name' => 'course_block_footer_right',
            'type' => 'textarea',
        ],
    ];

    protected function __construct()
    {
        parent::__construct(
            '0.2',
            'Julio Montoya',
            []
        );
    }

    public static function create(): self
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function get_info(): array
    {
        $info = parent::get_info();
        $info['supports_regions'] = true;

        return $info;
    }

    /**
     * CourseBlock has per-course settings only. Avoid showing unrelated global settings.
     */
    public function getSettingsForm()
    {
        return null;
    }

    public function install(): void
    {
        // Installing course settings.
        $this->install_course_fields_in_all_courses(false);
    }

    public function uninstall(): void
    {
        // Deleting course settings.
        $this->uninstall_course_fields_in_all_courses();
    }

    public function renderRegion($region): string
    {
        $region = (string) $region;

        if (!$this->isEnabled()) {
            return '';
        }

        if (!isset(self::REGION_SETTING_MAP[$region])) {
            return '';
        }

        $courseId = $this->getCurrentCourseId();

        if (0 >= $courseId) {
            return '';
        }

        $courseInfo = api_get_course_info_by_id($courseId);
        $content = api_get_course_setting(self::REGION_SETTING_MAP[$region], $courseInfo, true);

        if (-1 === $content || null === $content) {
            return '';
        }

        $content = trim((string) $content);

        if ('' === $content) {
            return '';
        }

        $content = Security::remove_XSS($content);

        return '<div class="course-block course-block--'.htmlspecialchars($region, ENT_QUOTES, 'UTF-8').'">'.$content.'</div>';
    }

    private function getCurrentCourseId(): int
    {
        if (function_exists('api_get_course_int_id')) {
            $courseId = (int) api_get_course_int_id();

            if (0 < $courseId) {
                return $courseId;
            }
        }

        if (isset($_GET['cid']) && is_numeric($_GET['cid'])) {
            return (int) $_GET['cid'];
        }

        $courseInfo = api_get_course_info();

        if (empty($courseInfo)) {
            return 0;
        }

        if (is_array($courseInfo) && isset($courseInfo['real_id'])) {
            return (int) $courseInfo['real_id'];
        }

        if ($courseInfo instanceof \Chamilo\CoreBundle\Entity\Course) {
            return (int) $courseInfo->getId();
        }

        return 0;
    }
}
