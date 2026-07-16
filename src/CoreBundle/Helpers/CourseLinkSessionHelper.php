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
     * Ensures every course-context URL (cid=X&sid=Y&gid=Z) whose cid matches $courseId
     * carries $sessionId as its sid: the value is replaced when the sid is already
     * present, and inserted right after the cid when it is missing (only if there is an
     * active session). The existing separator style (& or &amp;) is preserved, and URLs
     * referencing a different course are left untouched.
     */
    public function replaceSessionIdInUrls(string $html, int $courseId, int $sessionId): string
    {
        if ('' === $html || 0 === $courseId) {
            return $html;
        }

        // Group 1: cid=<courseId> (the (?!\d) guard avoids matching a longer course id).
        // Group 2: an existing &sid=<n> right after the cid, if any.
        // Group 3: the separator preceding the next param (e.g. gid), if any.
        $pattern = '/(cid='.$courseId.')(?!\d)((?:&amp;|&)sid=\d+)?((?:&amp;|&))?/';

        return preg_replace_callback(
            $pattern,
            static function (array $matches) use ($sessionId): string {
                $cid = $matches[1];
                $existingSid = $matches[2] ?? '';
                $trailingSeparator = $matches[3] ?? '';

                if ('' !== $existingSid) {
                    $separator = str_starts_with($existingSid, '&amp;') ? '&amp;' : '&';

                    return $cid.$separator.'sid='.$sessionId.$trailingSeparator;
                }

                // No sid present: only add one when there is an active session.
                if ($sessionId <= 0) {
                    return $matches[0];
                }

                $separator = '' !== $trailingSeparator ? $trailingSeparator : '&';

                return $cid.$separator.'sid='.$sessionId.$trailingSeparator;
            },
            $html
        ) ?? $html;
    }
}
