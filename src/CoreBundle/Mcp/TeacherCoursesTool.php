<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Mcp\Capability\Attribute\McpTool;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class TeacherCoursesTool
{
    public function __construct(
        private Security $security,
        private AccessUrlHelper $accessUrlHelper,
        private CourseRelUserRepository $courseRelUserRepository,
    ) {}

    /**
     * @return array{
     *     count: int,
     *     courses: list<array{
     *         course_id: int,
     *         title: string,
     *         code: string,
     *         visual_code: string|null,
     *         visibility: int
     *     }>
     * }
     */
    #[McpTool(
        name: 'list_my_teacher_courses',
        description: 'List courses the authenticated Chamilo user manages as a teacher on the current portal.',
    )]
    public function listMyTeacherCourses(): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof User || null === $user->getId()) {
            throw new AccessDeniedException('An authenticated Chamilo user is required.');
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl) {
            throw new RuntimeException('The current Chamilo access URL could not be resolved.');
        }

        $rows = $this->courseRelUserRepository->findTeacherCoursesForUserAndAccessUrl($user, $accessUrl);
        $courses = [];

        foreach ($rows as $row) {
            $courses[] = [
                'course_id' => (int) $row['id'],
                'title' => (string) $row['title'],
                'code' => (string) $row['code'],
                'visual_code' => null === $row['visualCode'] ? null : (string) $row['visualCode'],
                'visibility' => (int) $row['visibility'],
            ];
        }

        return [
            'count' => \count($courses),
            'courses' => $courses,
        ];
    }
}
