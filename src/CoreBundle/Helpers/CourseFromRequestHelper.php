<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves the course context (course, session id, group id) referenced by the
 * current request, without reading or writing the user session. Understands the
 * 2.0 parameters (`cid` as numeric id or course code, `sid`, `gid`) and their
 * legacy 1.11.x counterparts (`cidReq` as course code, `id_session`, `gidReq`).
 *
 * Read-only counterpart of CidReqListener: that listener is the only component
 * allowed to persist the course context in the user session.
 */
class CourseFromRequestHelper
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Returns the raw course reference (numeric id or course code) carried by
     * the request in `cid` or the legacy `cidReq`, or null when absent.
     */
    public function getCourseReference(Request $request): ?string
    {
        foreach (['cid', 'cidReq'] as $parameter) {
            foreach ([$request->attributes->get($parameter), $request->query->get($parameter)] as $value) {
                if (!\is_scalar($value)) {
                    continue;
                }

                $value = trim((string) $value);

                if ('' !== $value && '0' !== $value) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Returns the course id when the request references the course numerically,
     * null when absent or referenced by course code. Use resolveCourse() when a
     * code reference must be honored too — this getter is for callers that only
     * need a cheap id comparison without a database lookup.
     */
    public function getCourseId(Request $request): ?int
    {
        $reference = $this->getCourseReference($request);

        if (null !== $reference && ctype_digit($reference)) {
            return (int) $reference;
        }

        return null;
    }

    /**
     * Returns the session id carried by the request in `sid` or the legacy
     * `id_session`, or null when absent.
     */
    public function getSessionId(Request $request): ?int
    {
        return $this->getNumericReference($request, ['sid', 'id_session']);
    }

    /**
     * Returns the group id carried by the request in `gid` or the legacy
     * `gidReq`, or null when absent.
     */
    public function getGroupId(Request $request): ?int
    {
        return $this->getNumericReference($request, ['gid', 'gidReq']);
    }

    public function resolveCourse(Request $request): ?Course
    {
        $reference = $this->getCourseReference($request);

        return null === $reference ? null : $this->resolveByReference($reference);
    }

    /**
     * Finds a course by numeric id or by course code. A numeric reference that
     * matches no id falls back to a code lookup — course codes can be numeric.
     */
    public function resolveByReference(string $reference): ?Course
    {
        if (ctype_digit($reference)) {
            $course = $this->em->getRepository(Course::class)->find((int) $reference);

            if (null !== $course) {
                return $course;
            }
        }

        return $this->em->getRepository(Course::class)->findOneBy(['code' => $reference]);
    }

    /**
     * First strictly positive integer found among the given parameters (route
     * attributes first, then query). Non-numeric and zero values are ignored —
     * 1.11.x links carry an explicit `id_session=0&gidReq=0` meaning "none".
     */
    private function getNumericReference(Request $request, array $parameters): ?int
    {
        foreach ($parameters as $parameter) {
            foreach ([$request->attributes->get($parameter), $request->query->get($parameter)] as $value) {
                if (!\is_scalar($value)) {
                    continue;
                }

                $value = trim((string) $value);

                if (ctype_digit($value) && 0 !== (int) $value) {
                    return (int) $value;
                }
            }
        }

        return null;
    }
}
