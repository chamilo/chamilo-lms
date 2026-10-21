<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Tells real course activity apart from the requests a page sends on its own, so that a course
 * page left open stops counting as time spent in the course after
 * tracking.maximum_inactive_time_to_consider minutes without a real action.
 */
final readonly class CourseAccessActivityHelper
{
    private const SESSION_KEY = 'course_access_last_activity';
    private const DEFAULT_MAXIMUM_INACTIVE_MINUTES = 15;

    /**
     * Sent on a timer by an open course page: they carry the course but are not an action.
     * The docked global chat adds the course to its polling requests too.
     */
    private const BACKGROUND_ROUTES = [
        'sync_learning_path_runtime',
        'chamilo_core_chat_api_contacts',
        'chamilo_core_chat_api_heartbeat',
        'chamilo_core_chat_api_history_since',
        'chamilo_core_chat_api_video_signals',
    ];

    /**
     * Sent on a timer without the course: they are neither an action nor leaving the course.
     */
    private const NON_NAVIGATION_ROUTES = ['session_online_presence'];
    private const NON_NAVIGATION_PATHS = ['/main/inc/ajax/keepalive.ajax.php'];

    public function __construct(
        private SettingsManager $settingsManager,
    ) {}

    public function isBackgroundRequest(Request $request): bool
    {
        return \in_array($request->attributes->get('_route'), self::BACKGROUND_ROUTES, true);
    }

    public function isNonNavigationRequest(Request $request): bool
    {
        return \in_array($request->attributes->get('_route'), self::NON_NAVIGATION_ROUTES, true)
            || \in_array($request->getPathInfo(), self::NON_NAVIGATION_PATHS, true);
    }

    public function markActivity(?SessionInterface $session, int $courseId, int $sessionId): void
    {
        if (null === $session) {
            return;
        }

        $activity = (array) $session->get(self::SESSION_KEY, []);
        $activity[$courseId.':'.$sessionId] = time();
        $session->set(self::SESSION_KEY, $activity);
    }

    /**
     * The latest moment that may still count as time in the course: now, or the last real
     * action plus the allowed inactivity when that is earlier.
     */
    public function getCountableUntil(?SessionInterface $session, int $courseId, int $sessionId): DateTime
    {
        $now = time();
        $activity = (array) ($session?->get(self::SESSION_KEY, []) ?? []);
        $lastActivity = (int) ($activity[$courseId.':'.$sessionId] ?? 0);
        if ($lastActivity <= 0) {
            return (new DateTime())->setTimestamp($now);
        }

        return (new DateTime())->setTimestamp(min($now, $lastActivity + $this->getMaximumInactiveSeconds()));
    }

    public function isInactive(?SessionInterface $session, int $courseId, int $sessionId): bool
    {
        return $this->getCountableUntil($session, $courseId, $sessionId)->getTimestamp() < time();
    }

    public function getMaximumInactiveSeconds(): int
    {
        $minutes = (int) $this->settingsManager->getSetting('tracking.maximum_inactive_time_to_consider', true);

        return 60 * ($minutes > 0 ? $minutes : self::DEFAULT_MAXIMUM_INACTIVE_MINUTES);
    }
}
