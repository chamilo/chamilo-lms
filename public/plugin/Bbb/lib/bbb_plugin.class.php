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
                // Core BBB connection
                'host'  => 'text',
                'salt'  => 'text',

                // Global features
                'enable_global_conference'          => 'boolean',
                'enable_global_conference_per_user' => 'boolean',
                'enable_conference_in_course_groups'=> 'boolean',
                'enable_global_conference_link'     => 'boolean',
                'disable_download_conference_link'  => 'boolean',
                'hide_conference_link'              => 'boolean',
                'disable_course_settings'           => 'boolean',
                'max_users_limit'                   => 'text',
                'meeting_duration'                  => 'text',

                // Roles allowed to see/link global conference
                'global_conference_allow_roles' => [
                    'type'    => 'select',
                    'options' => [
                        PLATFORM_ADMIN => get_lang('Administrator'),
                        COURSEMANAGER  => get_lang('Teacher'),
                        STUDENT        => get_lang('Learner'),
                        STUDENT_BOSS   => get_lang('Superior (n+1)'),
                    ],
                    'attributes' => ['multiple' => 'multiple'],
                ],

                // Recordings
                'allow_regenerate_recording'           => 'boolean',
                'big_blue_button_record_and_store'     => 'checkbox',
                'bbb_enable_conference_in_groups'      => 'checkbox',
                'bbb_force_record_generation'          => 'checkbox',
                'delete_recordings_on_course_delete'   => 'boolean',

                // Webhooks (for live metrics → dashboard)
                'webhooks_enabled' => 'boolean',
                'webhooks_scope'   => [
                    'type'    => 'select',
                    'options' => [
                        'per_meeting' => 'Per meeting',
                        'global'      => 'Global',
                    ],
                ],
                'webhooks_hash_algo' => [
                    'type'    => 'select',
                    'options' => [
                        'sha256' => 'SHA-256',
                        'sha1'   => 'SHA-1',
                    ],
                ],
                // Comma-separated BBB event names to subscribe (optional)
                'webhooks_event_filter' => 'text',
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
        $em          = Database::getManager();
        $courseRepo  = $em->getRepository(Course::class);
        $settingRepo = $em->getRepository(CCourseSetting::class);

        $courses = $courseRepo->createQueryBuilder('c')
            ->select('c.id')
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getArrayResult();

        foreach ($courses as $course) {
            $setting = $settingRepo->findOneBy([
                'variable' => $variable,
                'cId'      => $course['id'],
            ]);

            if ($setting) {
                $setting->setValue($value);
                $em->persist($setting);
            }
        }

        $em->flush();
    }

    /* -----------------------------------------------------------------
     * Cleanup hooks: delete BBB recordings when course/session is removed
     * ----------------------------------------------------------------- */

    public function doWhenDeletingCourse($courseId): void
    {
        if ($this->get('delete_recordings_on_course_delete') !== 'true') {
            return;
        }
        $this->removeBbbRecordingsForCourse((int)$courseId);
    }

    public function doWhenDeletingSession($sessionId): void
    {
        if ($this->get('delete_recordings_on_course_delete') !== 'true') {
            return;
        }
        $this->removeBbbRecordingsForSession((int)$sessionId);
    }

    private function removeBbbRecordingsForCourse(int $courseId): void
    {
        $em           = Database::getManager();
        $meetingRepo  = $em->getRepository(ConferenceMeeting::class);
        $recordingRepo= $em->getRepository(ConferenceRecording::class);

        $meetings = $meetingRepo->createQueryBuilder('m')
            ->where('m.course = :cid')
            ->andWhere('m.serviceProvider = :sp')
            ->setParameters(['cid' => $courseId, 'sp' => 'bbb'])
            ->getQuery()->getResult();

        foreach ($meetings as $meeting) {
            $recs = $recordingRepo->findBy([
                'meeting'    => $meeting,
                'formatType' => 'bbb',
            ]);

            foreach ($recs as $rec) {
                if ($recordId = $this->extractRecordId($rec->getResourceUrl())) {
                    $this->deleteRecording($recordId);
                }
                $em->remove($rec);
            }

            // Optionally remove the meeting entity as well
            $em->remove($meeting);
        }
        $em->flush();
    }

    private function removeBbbRecordingsForSession(int $sessionId): void
    {
        $em           = Database::getManager();
        $meetingRepo  = $em->getRepository(ConferenceMeeting::class);
        $recordingRepo= $em->getRepository(ConferenceRecording::class);

        $meetings = $meetingRepo->findBy([
            'session'        => $sessionId,
            'serviceProvider'=> 'bbb',
        ]);

        foreach ($meetings as $meeting) {
            $recs = $recordingRepo->findBy([
                'meeting'    => $meeting,
                'formatType' => 'bbb',
            ]);

            foreach ($recs as $rec) {
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
        if (preg_match('/[?&]recordID=([\w-]+)/', $url, $m)) {
            return $m[1];
        }
        if (preg_match('/recordingID[-=](\d+)/', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    // Sends a deleteRecordings API request to BigBlueButton
    private function deleteRecording(string $recordId): void
    {
        $host = rtrim((string)$this->get('host'), '/');
        if ($host === '') {
            return;
        }
        if (!preg_match('#/bigbluebutton$#', $host)) {
            $host .= '/bigbluebutton';
        }

        $salt     = (string)$this->get('salt');
        $query    = 'recordID=' . rawurlencode($recordId);
        $checksum = sha1('deleteRecordings' . $query . $salt);
        $url      = $host . '/api/deleteRecordings?' . $query . '&checksum=' . $checksum;

        // Best-effort request (no exception bubbling here)
        @file_get_contents($url);
    }

    /* --------------------------------
     * Install / uninstall lifecycle
     * -------------------------------- */

    public function install(): void
    {
        $em = Database::getManager();

        $pluginRepo = Container::getPluginRepository();
        $plugin     = $pluginRepo->findOneByTitle($this->get_name());

        if (!$plugin) {
            $plugin = new \Chamilo\CoreBundle\Entity\Plugin();
            $plugin->setTitle($this->get_name());
        } else {
            $plugin = $em->merge($plugin);
        }

        $plugin->setInstalled(true);
        $plugin->setInstalledVersion($this->get_version());
        $plugin->setSource(\Chamilo\CoreBundle\Entity\Plugin::SOURCE_OFFICIAL);
        $em->persist($plugin);

        $accessUrlRepo         = Container::getAccessUrlRepository();
        $accessUrlRelPluginRepo= Container::getAccessUrlRelPluginRepository();

        foreach ($accessUrlRepo->findAll() as $accessUrl) {
            $rel = $accessUrlRelPluginRepo->findOneBy(['plugin' => $plugin, 'url' => $accessUrl]);
            if ($rel) {
                continue;
            }

            $rel = new AccessUrlRelPlugin();
            $rel->setPlugin($plugin);
            $rel->setUrl($accessUrl);
            $rel->setActive(true);

            // Build default configuration values
            $configuration = [];
            foreach ($this->fields as $name => $type) {
                if (is_array($type)) {
                    $configuration[$name] = ($type['type'] === 'boolean' || $type['type'] === 'checkbox') ? 'false' : '';
                } else {
                    $configuration[$name] = in_array($type, ['boolean','checkbox'], true) ? 'false' : '';
                }
            }

            // Explicit defaults for webhooks
            $configuration['webhooks_enabled']      = $configuration['webhooks_enabled']      ?? 'false';
            $configuration['webhooks_scope']        = $configuration['webhooks_scope']        ?? 'per_meeting';
            $configuration['webhooks_hash_algo']    = $configuration['webhooks_hash_algo']    ?? 'sha256';
            $configuration['webhooks_event_filter'] = $configuration['webhooks_event_filter'] ?? '';

            $rel->setConfiguration($configuration);
            $em->persist($rel);
        }

        $em->flush();
    }

    /**
     * Uninstalls the plugin:
     * - Unlinks from access URLs
     * - Marks plugin as not installed
     * (Keeps course-level data for safety)
     */
    public function uninstall(): void
    {
        $em        = Database::getManager();
        $plugin    = Container::getPluginRepository()->findOneByTitle($this->get_name());

        if (!$plugin) {
            return;
        }

        foreach (Container::getAccessUrlRelPluginRepository()->findBy(['plugin' => $plugin]) as $rel) {
            $em->remove($rel);
        }

        $plugin->setInstalled(false);
        $em->persist($plugin);
        $em->flush();
    }

    /* ----------------------------
     * UI helpers / permissions
     * ---------------------------- */

    /** Whether current user can see the global conference link. */
    public function canCurrentUserSeeGlobalConferenceLink(): bool
    {
        $allowed = $this->get('global_conference_allow_roles') ?? [];

        // Stored as array (multi-select) or empty → default to admin only
        if (empty($allowed)) {
            return api_is_platform_admin();
        }

        foreach ($allowed as $status) {
            switch ((int)$status) {
                case PLATFORM_ADMIN:
                    if (api_is_platform_admin()) { return true; }
                    break;
                case COURSEMANAGER:
                    if (api_is_teacher()) { return true; }
                    break;
                case STUDENT:
                    if (api_is_student()) { return true; }
                    break;
                case STUDENT_BOSS:
                    if (api_is_student_boss()) { return true; }
                    break;
            }
        }
        return false;
    }

    /** Setting to decide if the "share link" is shown below the join button. */
    public function showShareLink(): bool
    {
        if (api_get_course_int_id() > 0) {
            $hide = (string)$this->get('hide_conference_link');
            if ($hide === 'true') {
                return false;
            }
        }
        return true;
    }

    /* ----------------------------
     * Webhooks helpers & health
     * ---------------------------- */

    /** Toggle: are webhooks enabled for this access URL? */
    public function webhooksEnabled(): bool
    {
        return $this->get('webhooks_enabled') === 'true';
    }

    /** Scope: "per_meeting" (default) or "global". */
    public function webhooksScope(): string
    {
        $v = (string)($this->get('webhooks_scope') ?? 'per_meeting');
        return in_array($v, ['per_meeting', 'global'], true) ? $v : 'per_meeting';
    }

    /**
     * Hash algorithm used to sign the callback URL (HMAC).
     * Note: This is *our* HMAC for webhook.php protection (sha256|sha1),
     * while BBB hook API checksums are always sha1(call+query+salt).
     */
    public function webhooksHashAlgo(): string
    {
        $v = (string)($this->get('webhooks_hash_algo') ?? 'sha256');
        return in_array($v, ['sha256', 'sha1'], true) ? $v : 'sha256';
    }

    /** Optional event filter sent to BBB hooks/create (comma-separated). */
    public function webhooksEventFilter(): string
    {
        return (string)($this->get('webhooks_event_filter') ?? '');
    }

    /**
     * Quick health check: verify BBB hooks/list responds SUCCESS.
     * Uses sha1(call+query+salt) per BBB spec.
     */
    public function checkWebhooksHealth(): array
    {
        if (!$this->webhooksEnabled()) {
            return ['enabled' => false, 'ok' => false, 'reason' => 'disabled'];
        }
        $host = rtrim((string)$this->get('host'), '/');
        $salt = (string)$this->get('salt');
        if ($host === '' || $salt === '') {
            return ['enabled' => true, 'ok' => false, 'reason' => 'missing_config'];
        }

        if (!preg_match('#/bigbluebutton$#', $host)) {
            $host .= '/bigbluebutton';
        }

        $call     = 'hooks/list';
        $query    = ''; // no params
        $checksum = sha1($call . $query . $salt);
        $url      = $host . '/api/' . $call . '?checksum=' . $checksum;

        $xml = @simplexml_load_file($url);
        if ($xml && (string)($xml->returncode ?? '') === 'SUCCESS') {
            return ['enabled' => true, 'ok' => true];
        }
        $reason = '';
        if ($xml && isset($xml->messageKey)) {
            $reason = (string)$xml->messageKey;
        }
        return ['enabled' => true, 'ok' => false, 'reason' => $reason ?: 'no_response'];
    }

    /**
     * Admin banner if hooks are enabled but not installed on server.
     */
    public function getWebhooksAdminWarningHtml(): ?string
    {
        $h = $this->checkWebhooksHealth();
        if ($h['enabled'] && !$h['ok']) {
            $msg = 'Webhooks are not installed on the BBB server — running in reduced mode (basic callbacks only).';
            if (!empty($h['reason'])) {
                $msg .= ' [' . htmlspecialchars($h['reason'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ']';
            }
            return '<div class="alert alert-warning" role="alert">' . $msg . '</div>';
        }
        return null;
    }
}
