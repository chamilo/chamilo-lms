<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\User;
use ChamiloSession as Session;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;

class CourseToolAccessTracker
{
    public function __construct(
        private readonly Connection $connection,
        private readonly CidReqHelper $cidReqHelper,
        private readonly UserHelper $userHelper
    ) {}

    public function trackFromVueResourceRequest(Request $request): void
    {
        if (!$this->isTrackableVueResourceRequest($request)) {
            return;
        }

        $tool = $this->extractToolFromResourcesPath($request);
        if (null === $tool) {
            return;
        }

        $this->track($request, $tool);
    }

    public function trackFromResourceRequest(Request $request, ?string $tool = null): void
    {
        if (!$this->isTrackableResourceRequest($request)) {
            return;
        }

        $tool ??= $this->extractToolFromResourceRequest($request);
        if (null === $tool) {
            return;
        }

        $this->track($request, $tool);
    }

    private function track(Request $request, string $tool): void
    {
        if ($request->attributes->get('tool_access_checked')) {
            return;
        }
        $request->attributes->set('tool_access_checked', true);

        if (Session::read('login_as')) {
            return;
        }

        $courseId = (int) ($request->query->get('cid') ?: $this->cidReqHelper->getCourseId());
        if ($courseId <= 0) {
            return;
        }

        $sessionId = (int) ($request->query->get('sid') ?: $this->cidReqHelper->getSessionId() ?: 0);

        $tool = $this->normalizeToolName($tool);
        if ('' === $tool) {
            return;
        }

        $user = $this->userHelper->getCurrent();
        if (!$user instanceof User || (int) $user->getId() <= 0) {
            return;
        }

        $nowUtc = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $userId = (int) $user->getId();

        $this->connection->insert('track_e_access', [
            'access_user_id' => $userId,
            'c_id' => $courseId,
            'access_tool' => $tool,
            'access_date' => $nowUtc,
            'session_id' => $sessionId,
            'user_ip' => (string) ($request->getClientIp() ?? ''),
        ]);

        $affected = $this->connection->executeStatement(
            'UPDATE track_e_lastaccess
                SET access_date = :access_date
              WHERE access_user_id = :user_id
                AND c_id = :c_id
                AND access_tool = :access_tool
                AND session_id = :session_id',
            [
                'access_date' => $nowUtc,
                'user_id' => $userId,
                'c_id' => $courseId,
                'access_tool' => $tool,
                'session_id' => $sessionId,
            ]
        );

        if (0 === $affected) {
            $this->connection->insert('track_e_lastaccess', [
                'access_user_id' => $userId,
                'c_id' => $courseId,
                'access_tool' => $tool,
                'access_date' => $nowUtc,
                'session_id' => $sessionId,
            ]);
        }
    }

    private function isTrackableVueResourceRequest(Request $request): bool
    {
        if ('GET' !== $request->getMethod()) {
            return false;
        }

        if ($request->isXmlHttpRequest()) {
            return false;
        }

        $path = (string) $request->getPathInfo();
        if (!str_starts_with($path, '/resources/')) {
            return false;
        }

        if (str_starts_with($path, '/api')) {
            return false;
        }

        $accept = (string) $request->headers->get('accept', '');
        $format = (string) $request->getRequestFormat('');
        if (!str_contains($accept, 'text/html') && 'html' !== $format && '' !== $format) {
            return false;
        }

        return true;
    }

    private function isTrackableResourceRequest(Request $request): bool
    {
        if ('GET' !== $request->getMethod()) {
            return false;
        }

        if ($request->isXmlHttpRequest()) {
            return false;
        }

        $path = (string) $request->getPathInfo();

        return str_starts_with($path, '/r/');
    }

    private function extractToolFromResourcesPath(Request $request): ?string
    {
        $path = (string) $request->getPathInfo();

        if (!preg_match('#^/resources/([^/]+)(?:/|$)#', $path, $matches)) {
            return null;
        }

        $segment = strtolower(trim((string) ($matches[1] ?? '')));
        if ('' === $segment) {
            return null;
        }

        return $this->mapToolName($segment);
    }

    private function extractToolFromResourceRequest(Request $request): ?string
    {
        $type = (string) $request->attributes->get('type', '');
        if ('' === $type) {
            $type = (string) $request->get('type', '');
        }

        if ('' !== $type) {
            return $this->mapToolName($type);
        }

        $tool = (string) $request->attributes->get('tool', '');
        if ('' === $tool) {
            $tool = (string) $request->get('tool', '');
        }

        return '' === $tool ? null : $this->mapToolName($tool);
    }

    private function mapToolName(string $tool): string
    {
        $tool = strtolower(trim($tool));

        $map = [
            'lp' => 'learnpath',
            'learnpath' => 'learnpath',
            'learning_path' => 'learnpath',
            'learningpath' => 'learnpath',
            'document' => 'document',
            'documents' => 'document',
            'assignment' => 'work',
            'assignments' => 'work',
            'student_publication' => 'work',
            'student_publications' => 'work',
            'work' => 'work',
            'works' => 'work',
            'attendance' => 'attendance',
            'dropbox' => 'dropbox',
            'glossary' => 'glossary',
            'links' => 'link',
            'link' => 'link',
            'forum' => 'forum',
            'forums' => 'forum',
            'quiz' => 'quiz',
            'quizzes' => 'quiz',
            'exercise' => 'quiz',
            'exercises' => 'quiz',
            'ccalendarevent' => 'agenda',
            'agenda' => 'agenda',
            'calendar' => 'agenda',
        ];

        return $map[$tool] ?? $tool;
    }

    private function normalizeToolName(string $tool): string
    {
        $tool = strtolower(trim($tool));
        $tool = preg_replace('/[^a-z0-9_-]/', '', $tool) ?? '';

        if (\strlen($tool) > 15) {
            $tool = substr($tool, 0, 15);
        }

        return $tool;
    }
}
