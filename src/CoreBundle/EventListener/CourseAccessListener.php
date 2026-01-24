<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use ChamiloSession as Session;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * In and outs of a course.
 * This listener is always called when user enters the course home.
 * It also logs tool access for C2 rewritten tools under /resources/* routes.
 */
class CourseAccessListener
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Connection $connection,
        private readonly CidReqHelper $cidReqHelper,
        private readonly UserHelper $userHelper
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || $event->getRequest()->attributes->get('access_checked')) {
            // If it's not the main request or we've already handled access in this request, do nothing.
            return;
        }

        $request = $event->getRequest();

        $courseId = (int) $this->cidReqHelper->getCourseId();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $sessionId = 0;
        if (!empty($session)) {
            $sessionId = $session->getId();
        }

        if ($courseId <= 0) {
            return;
        }

        $user = $this->userHelper->getCurrent();
        if (!$user) {
            return;
        }

        // only log access for the Doctrine-backed Chamilo User entity with a valid ID.
        if (!$user instanceof User || (int) $user->getId() <= 0) {
            return;
        }

        $ip = (string) ($request->getClientIp() ?? '');

        // --- Existing behavior: track_e_course_access ---
        $accessRepository = $this->em->getRepository(TrackECourseAccess::class);
        $access = $accessRepository->findExistingAccess($user, $courseId, $sessionId);

        if ($access) {
            $accessRepository->updateAccess($access);
        } else {
            if (!empty($session) && $session->getDuration() > 0) {
                $subscription = $user->getSubscriptionToSession($session);
                if ($subscription) {
                    $duration = $session->getDuration() + $subscription->getDuration();

                    $startDate = new DateTime();
                    $endDate = (clone $startDate)->modify("+$duration days");

                    $subscription
                        ->setAccessStartDate($startDate)
                        ->setAccessEndDate($endDate)
                    ;

                    $this->em->flush();
                }
            }

            $accessRepository->recordAccess($user, $courseId, $sessionId, $ip);
        }

        // track_e_access + track_e_lastaccess (C2 tools) ---
        $this->logToolAccessIfNeeded($request, $courseId, $sessionId);

        // Set a flag on the request to indicate that access has been checked.
        $request->attributes->set('access_checked', true);
    }

    private function logToolAccessIfNeeded(Request $request, int $courseId, int $sessionId): void
    {
        // Avoid duplicate tool logs in the same request lifecycle.
        if ($request->attributes->get('tool_access_checked')) {
            return;
        }
        $request->attributes->set('tool_access_checked', true);

        // Match legacy behavior: do not track when impersonating.
        if (Session::read('login_as')) {
            return;
        }

        // Avoid unnecessary inserts: only log on real "page entry" requests.
        if (!$this->isTrackablePageEntry($request)) {
            return;
        }

        // Detect tool from /resources/{tool}/... (Vue router paths).
        $tool = $this->extractToolFromResourcesPath($request);
        if (null === $tool) {
            return;
        }

        // Keep tool name short and consistent (legacy recommends <= 15 chars).
        $tool = $this->normalizeToolName($tool);
        if ('' === $tool) {
            return;
        }

        $user = $this->userHelper->getCurrent();
        if (!$user) {
            return;
        }

        // If later you want the same "session visibility + extra field" rule as legacy,
        // this is the place to add it in a Symfony way.
        // For now we keep it safe and do not spam: only page entries are logged.

        $nowUtc = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $ip = (string) ($request->getClientIp() ?? '');

        // Equivalent to Event::event_access_tool():
        // - Insert one row per entry in track_e_access
        $this->connection->insert('track_e_access', [
            'access_user_id' => (int) $user->getId(),
            'c_id' => $courseId,
            'access_tool' => $tool,
            'access_date' => $nowUtc,
            'session_id' => $sessionId,
            'user_ip' => $ip,
        ]);

        // - Update or insert in track_e_lastaccess ("what's new" logic)
        $affected = $this->connection->executeStatement(
            'UPDATE track_e_lastaccess
               SET access_date = :access_date
             WHERE access_user_id = :user_id
               AND c_id = :c_id
               AND access_tool = :access_tool
               AND session_id = :session_id',
            [
                'access_date' => $nowUtc,
                'user_id' => (int) $user->getId(),
                'c_id' => $courseId,
                'access_tool' => $tool,
                'session_id' => $sessionId,
            ]
        );

        if (0 === $affected) {
            $this->connection->insert('track_e_lastaccess', [
                'access_user_id' => (int) $user->getId(),
                'c_id' => $courseId,
                'access_tool' => $tool,
                'access_date' => $nowUtc,
                'session_id' => $sessionId,
            ]);
        }
    }

    private function isTrackablePageEntry(Request $request): bool
    {
        // Track only GET entries (like opening a tool page).
        if ('GET' !== $request->getMethod()) {
            return false;
        }

        // Avoid tracking API calls and XHR/fetch requests.
        if ($request->isXmlHttpRequest()) {
            return false;
        }

        $path = (string) $request->getPathInfo();
        if (str_starts_with($path, '/api')) {
            return false;
        }

        // Only track /resources/* pages (rewritten tools entry points).
        if (!str_starts_with($path, '/resources/')) {
            return false;
        }

        // Heuristic: prefer HTML navigation (avoid assets, etc.)
        $accept = (string) $request->headers->get('accept', '');
        $format = (string) $request->getRequestFormat('');
        if (!str_contains($accept, 'text/html') && 'html' !== $format && '' !== $format) {
            return false;
        }

        return true;
    }

    private function extractToolFromResourcesPath(Request $request): ?string
    {
        $path = (string) $request->getPathInfo();

        // Expected Vue routes:
        // /resources/document/:node/
        // /resources/lp/:node
        // /resources/ccalendarevent
        // etc.
        if (!preg_match('#^/resources/([^/]+)(?:/|$)#', $path, $m)) {
            return null;
        }

        $segment = strtolower(trim((string) ($m[1] ?? '')));
        if ('' === $segment) {
            return null;
        }

        // Map Vue route segment -> legacy-ish tool name stored in track_e_access.access_tool
        $map = [
            'lp' => 'learnpath',
            'learnpath' => 'learnpath',
            'document' => 'document',
            'documents' => 'document',
            'assignment' => 'work',
            'assignments' => 'work',
            'attendance' => 'attendance',
            'dropbox' => 'dropbox',
            'glossary' => 'glossary',
            'links' => 'link',
            'link' => 'link',
            'ccalendarevent' => 'agenda',
        ];

        return $map[$segment] ?? $segment;
    }

    private function normalizeToolName(string $tool): string
    {
        $tool = strtolower(trim($tool));

        // Avoid unexpected long values.
        if (\strlen($tool) > 15) {
            $tool = substr($tool, 0, 15);
        }

        // Defensive: allow only simple chars.
        return preg_replace('/[^a-z0-9_-]/', '', $tool) ?? '';
    }
}
