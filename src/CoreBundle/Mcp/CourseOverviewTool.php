<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CQuiz;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Mcp\Capability\Attribute\McpTool;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class CourseOverviewTool
{
    public function __construct(
        private Security $security,
        private AccessUrlHelper $accessUrlHelper,
        private CourseRelUserRepository $courseRelUserRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return array{
     *     scope: string,
     *     course: array{
     *         course_id: int,
     *         title: string,
     *         code: string,
     *         visual_code: string|null,
     *         language: string,
     *         visibility: int,
     *         description: string|null
     *     },
     *     metrics: array{
     *         direct_students: int,
     *         documents: int,
     *         tests: int,
     *         learning_paths: int,
     *         forums: int
     *     }
     * }
     */
    #[McpTool(
        name: 'get_course_overview',
        description: 'Return base-course information and resource counts for a course managed by the authenticated teacher.',
    )]
    public function getCourseOverview(int $courseId): array
    {
        if ($courseId <= 0) {
            throw new RuntimeException('The course ID must be a positive integer.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || null === $user->getId()) {
            throw new AccessDeniedException('An authenticated Chamilo user is required.');
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl) {
            throw new RuntimeException('The current Chamilo access URL could not be resolved.');
        }

        $course = $this->courseRelUserRepository->findTeacherCourseForUserAndAccessUrl(
            $user,
            $accessUrl,
            $courseId,
        );

        if (!$course instanceof Course) {
            throw new AccessDeniedException('The course was not found or is not managed by the authenticated teacher.');
        }

        return [
            'scope' => 'base_course',
            'course' => [
                'course_id' => (int) $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
                'visual_code' => $course->getVisualCode(),
                'language' => $course->getCourseLanguage(),
                'visibility' => $course->getVisibility(),
                'description' => $course->getDescription(),
            ],
            'metrics' => [
                'direct_students' => $this->courseRelUserRepository->countDirectStudentsForCourse($course),
                'documents' => $this->countBaseCourseDocuments($course),
                'tests' => $this->countBaseCourseResources(CQuiz::class, $course),
                'learning_paths' => $this->countBaseCourseResources(CLp::class, $course),
                'forums' => $this->countBaseCourseResources(CForum::class, $course),
            ],
        ];
    }

    private function countBaseCourseDocuments(Course $course): int
    {
        return (int) $this->createBaseCourseResourceCountQuery(CDocument::class, $course)
            ->andWhere('resource.filetype != :folderType')
            ->andWhere('resource.template = :isTemplate')
            ->setParameter('folderType', 'folder')
            ->setParameter('isTemplate', false)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @param class-string<AbstractResource> $resourceClass
     */
    private function countBaseCourseResources(string $resourceClass, Course $course): int
    {
        return (int) $this->createBaseCourseResourceCountQuery($resourceClass, $course)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @param class-string<AbstractResource> $resourceClass
     */
    private function createBaseCourseResourceCountQuery(
        string $resourceClass,
        Course $course,
    ): QueryBuilder {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(DISTINCT resource.iid)')
            ->from($resourceClass, 'resource')
            ->innerJoin('resource.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'resourceLink')
            ->andWhere('IDENTITY(resourceLink.course) = :courseId')
            ->andWhere('resourceLink.session IS NULL')
            ->andWhere('resourceLink.group IS NULL')
            ->andWhere('resourceLink.userGroup IS NULL')
            ->andWhere('resourceLink.user IS NULL')
            ->setParameter(
                'courseId',
                (int) $course->getId(),
                Types::INTEGER,
            )
        ;
    }
}
