<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\Blog;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Entity\CBlog;

/**
 * Checks that a blog belongs to the exact active course/session context.
 *
 * Blogs are session-only resources: session context never falls back to a
 * base-course ResourceLink, and base-course context never accepts a session link.
 */
final readonly class BlogContextAccessChecker
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
    ) {}

    public function isInCurrentContext(CBlog $blog): bool
    {
        $courseId = (int) ($this->cidReqHelper->getCourseId() ?? 0);
        if ($courseId <= 0) {
            return false;
        }

        $sessionId = (int) ($this->cidReqHelper->getSessionId() ?? 0);
        $resourceNode = $blog->getResourceNode();
        if (null === $resourceNode) {
            return false;
        }

        foreach ($resourceNode->getResourceLinks() as $link) {
            $course = $link->getCourse();
            if (!$course instanceof Course || (int) $course->getId() !== $courseId) {
                continue;
            }

            $session = $link->getSession();
            if ($sessionId > 0) {
                if ($session instanceof Session && (int) $session->getId() === $sessionId) {
                    return true;
                }

                continue;
            }

            if (null === $session) {
                return true;
            }
        }

        return false;
    }
}
