<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\AccessUrlRelPlugin;
use Chamilo\CoreBundle\Entity\ConferenceMeeting;
use Chamilo\CoreBundle\Entity\ConferenceRecording;
use Chamilo\CoreBundle\Framework\Container;
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
                'delete_recordings_on_course_delete' => 'boolean',
                'hide_conference_link' => 'boolean',
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

    // Hook called when a course is deleted
    public function doWhenDeletingCourse($courseId): void
    {
        // Check if the setting is enabled
        if ($this->get('delete_recordings_on_course_delete') !== 'true') {
            return;
        }

        $this->removeBbbRecordingsForCourse($courseId);
    }

    // Hook called when a session is deleted
    public function doWhenDeletingSession($sessionId): void
    {
        // Check if the setting is enabled
        if ($this->get('delete_recordings_on_course_delete') !== 'true') {
            return;
        }

        $this->removeBbbRecordingsForSession($sessionId);
    }

    // Remove BBB recordings linked to a specific course
    private function removeBbbRecordingsForCourse(int $courseId): void
    {
        $em = Database::getManager();
        $meetingRepo = $em->getRepository(ConferenceMeeting::class);
        $recordingRepo = $em->getRepository(ConferenceRecording::class);

        // Get all BBB meetings for this course
        $meetings = $meetingRepo->createQueryBuilder('m')
            ->where('m.course = :cid')
            ->andWhere('m.serviceProvider = :sp')
            ->setParameters(['cid' => $courseId, 'sp' => 'bbb'])
            ->getQuery()
            ->getResult();

        foreach ($meetings as $meeting) {
            // Get all recordings of this meeting
            $recordings = $recordingRepo->findBy([
                'meeting' => $meeting,
                'formatType' => 'bbb',
            ]);

            foreach ($recordings as $rec) {
                // Try to extract the record ID from the URL
                if ($recordId = $this->extractRecordId($rec->getResourceUrl())) {
                    $this->deleteRecording($recordId); // Call BBB API to delete
                }

                $em->remove($rec); // Remove local record
            }

            $em->remove($meeting); // Optionally remove the meeting entity
        }

        $em->flush(); // Save all removals
    }

    // Remove BBB recordings linked to a specific session
    private function removeBbbRecordingsForSession(int $sessionId): void
    {
        $em = Database::getManager();
        $meetingRepo = $em->getRepository(ConferenceMeeting::class);
        $recordingRepo = $em->getRepository(ConferenceRecording::class);

        // Get all BBB meetings for this session
        $meetings = $meetingRepo->findBy([
            'session' => $sessionId,
            'serviceProvider' => 'bbb',
        ]);

        foreach ($meetings as $meeting) {
            $recordings = $recordingRepo->findBy([
                'meeting' => $meeting,
                'formatType' => 'bbb',
            ]);

            foreach ($recordings as $rec) {
                if ($recordId = $this->extractRecordId($rec->getResourceUrl())) {
                    $this->deleteRecording($recordId);
                }

                $em->remove($rec);
            }

            $em->remove($meeting);
        }

        $em->flush();
    }

    // Extracts the recordID from the BBB recording URL
    private function extractRecordId(string $url): ?string
    {
        // Match parameter ?recordID=xxx
        if (preg_match('/[?&]recordID=([\w-]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Optional: match paths like .../recordingID-123456
        if (preg_match('/recordingID[-=](\d+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    // Sends a deleteRecordings API request to BigBlueButton
    private function deleteRecording(string $recordId): void
    {
        $host = rtrim($this->get('host'), '/');
        $salt = $this->get('salt');

        $query = "recordID={$recordId}";
        $checksum = sha1('deleteRecordings' . $query . $salt);
        $url = "{$host}/bigbluebutton/api/deleteRecordings?{$query}&checksum={$checksum}";

        // Send the request (silently)
        @file_get_contents($url);
    }

    /**
     * Installs the plugin
     */
    public function install(): void
    {
        $entityManager = Database::getManager();

        $pluginRepo = Container::getPluginRepository();
        $plugin = $pluginRepo->findOneByTitle($this->get_name());

        if (!$plugin) {
            // Create the plugin only if it does not exist
            $plugin = new \Chamilo\CoreBundle\Entity\Plugin();
            $plugin->setTitle($this->get_name());
            $plugin->setInstalled(true);
            $plugin->setInstalledVersion($this->get_version());
            $plugin->setSource(\Chamilo\CoreBundle\Entity\Plugin::SOURCE_OFFICIAL);

            $entityManager->persist($plugin);
            $entityManager->flush();
        } else {
            // Ensure Doctrine manages it in the current UnitOfWork
            $plugin = $entityManager->merge($plugin);
        }

        // Check if the plugin has relations for access URLs
        $accessUrlRepo = Container::getAccessUrlRepository();
        $accessUrlRelPluginRepo = Container::getAccessUrlRelPluginRepository();

        $accessUrls = $accessUrlRepo->findAll();

        foreach ($accessUrls as $accessUrl) {
            $rel = $accessUrlRelPluginRepo->findOneBy([
                'plugin' => $plugin,
                'url' => $accessUrl,
            ]);

            if (!$rel) {
                $rel = new AccessUrlRelPlugin();
                $rel->setPlugin($plugin);
                $rel->setUrl($accessUrl);
                $rel->setActive(true);

                $configuration = [];
                foreach ($this->fields as $name => $type) {
                    $defaultValue = '';

                    if (is_array($type)) {
                        $defaultValue = $type['type'] === 'boolean' ? 'false' : '';
                    } else {
                        switch ($type) {
                            case 'boolean':
                            case 'checkbox':
                                $defaultValue = 'false';
                                break;
                            default:
                                $defaultValue = '';
                                break;
                        }
                    }

                    $configuration[$name] = $defaultValue;
                }

                $rel->setConfiguration($configuration);

                $entityManager->persist($rel);
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
