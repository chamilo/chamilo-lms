<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

/**
 * Rewrites the sid (session id) value of course-context URLs (cid=X&sid=Y&gid=Z)
 * embedded in HTML content so they point to the current session.
 */
readonly class CourseLinkSessionHelper
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
    ) {}

    /**
     * Rewrites the sid of every course-context URL whose cid matches $courseId,
     * using the session currently resolved by CidReqHelper.
     */
    public function rewriteSessionForCourse(string $html, int $courseId): string
    {
        return $this->replaceSessionIdInUrls($html, $courseId, (int) $this->cidReqHelper->getSessionId());
    }

    /**
     * Rewrites the sid value of every course-context URL (cid=X&sid=Y&gid=Z) whose cid
     * matches $courseId so it points to $sessionId. URLs referencing a different course
     * are left untouched.
     */
    public function replaceSessionIdInUrls(string $html, int $courseId, int $sessionId): string
    {
        if ('' === $html || 0 === $courseId) {
            return $html;
        }

        $pattern = '/(cid='.$courseId.'(?:&amp;|&)sid=)\d+/';

        return preg_replace_callback(
            $pattern,
            static fn (array $matches): string => $matches[1].$sessionId,
            $html
        ) ?? $html;
    }
}
