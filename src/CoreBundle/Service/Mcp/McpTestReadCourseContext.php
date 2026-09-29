<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Mcp;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Course-access resolver for the read-only test/question/answer MCP tools.
 *
 * Unlike McpTeacherCourseContext (strictly "teacher of this course"), these
 * tools are also open to roles that manage the question bank or the platform
 * as a whole: ROLE_QUESTION_MANAGER, ROLE_ADMIN and ROLE_GLOBAL_ADMIN (the
 * latter two already imply ROLE_QUESTION_MANAGER via the role hierarchy in
 * security.yaml, so a single isGranted('ROLE_QUESTION_MANAGER') check would
 * technically cover all three — the explicit ROLE_ADMIN check is kept for
 * clarity and to stay correct even if the hierarchy changes later). Those
 * roles can read any course's tests within the current AccessUrl; a plain
 * teacher is still restricted to courses they actually teach.
 */
final readonly class McpTestReadCourseContext
{
    public function __construct(
        private Security $security,
        private AccessUrlHelper $accessUrlHelper,
        private CourseRelUserRepository $courseRelUserRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return array{course: Course, user: User}
     */
    public function resolve(int $courseId): array
    {
        if ($courseId <= 0) {
            throw new InvalidArgumentException('The course ID must be a positive integer.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || null === $user->getId()) {
            throw new AccessDeniedException('An authenticated Chamilo user is required.');
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl) {
            throw new RuntimeException('The current Chamilo access URL could not be resolved.');
        }

        $hasElevatedAccess = $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_QUESTION_MANAGER');

        if ($hasElevatedAccess) {
            $course = $this->findCourseForAccessUrl($courseId, $accessUrl);
            if (!$course instanceof Course) {
                throw new InvalidArgumentException('The course was not found.');
            }

            return ['course' => $course, 'user' => $user];
        }

        $course = $this->courseRelUserRepository->findTeacherCourseForUserAndAccessUrl(
            $user,
            $accessUrl,
            $courseId,
        );
        if (!$course instanceof Course) {
            throw new AccessDeniedException('The course was not found, is not managed by the authenticated teacher, and the user does not hold a question manager or administrator role.');
        }

        return ['course' => $course, 'user' => $user];
    }

    private function findCourseForAccessUrl(int $courseId, AccessUrl $accessUrl): ?Course
    {
        $course = $this->entityManager->createQueryBuilder()
            ->select('course')
            ->from(Course::class, 'course')
            ->innerJoin('course.urls', 'urlRelation')
            ->andWhere('course.id = :courseId')
            ->andWhere('IDENTITY(urlRelation.url) = :accessUrlId')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('accessUrlId', (int) $accessUrl->getId(), Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $course instanceof Course ? $course : null;
    }
}
