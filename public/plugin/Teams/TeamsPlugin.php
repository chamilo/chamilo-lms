<?php

/* For licensing terms, see /license.txt */

class TeamsPlugin extends Plugin
{
    public $isCoursePlugin = true;
    public $isAdminPlugin = true;
    public $addCourseTool = true;

    protected function __construct()
    {
        parent::__construct(
            '0.1',
            'Chamilo',
            [
                'tenantId' => 'text',
                'clientId' => 'text',
                'clientSecret' => 'text',
                'enablePersonalConference' => 'boolean',
                'enableGlobalConference' => 'boolean',
            ]
        );
    }

    public static function create(): self
    {
        static $instance = null;

        return $instance ?: $instance = new self();
    }

    public function install(): void
    {
        $this->syncCourseTools();
    }

    public function uninstall(): void
    {
        // Keep ConferenceMeeting history. Uninstall only removes course-tool links.
        $this->uninstall_course_fields_in_all_courses();
    }

    public function isPersonalConferenceEnabled(): bool
    {
        return $this->isBooleanSettingEnabled('enablePersonalConference');
    }

    public function isGlobalConferenceEnabled(): bool
    {
        return $this->isBooleanSettingEnabled('enableGlobalConference');
    }

    private function isBooleanSettingEnabled(string $name): bool
    {
        $value = $this->get($name);

        return true === $value
            || 1 === $value
            || '1' === $value
            || in_array(strtolower(trim((string) $value)), ['true', 'yes', 'on'], true);
    }

    private function syncCourseTools(): void
    {
        // Rebuild links through the standard plugin lifecycle. This also cleans
        // prototype course-tool rows left by development iterations.
        $this->uninstall_course_fields_in_all_courses();
        $this->install_course_fields_in_all_courses(true);
    }
}
