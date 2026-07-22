<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class CreateCourseTool
{
    public function __construct(
        private Security $security,
        private CourseHelper $courseHelper,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @return array{
     *     created: true,
     *     course: array{
     *         course_id: int,
     *         title: string,
     *         code: string,
     *         visual_code: string|null,
     *         language: string,
     *         visibility: int,
     *         url: string
     *     }
     * }
     */
    #[McpTool(
        name: 'create_course',
        description: 'Create a Chamilo course for the authenticated teacher using the platform course-creation rules.',
    )]
    public function createCourse(
        string $title,
        ?string $code = null,
        ?string $language = null,
    ): array {
        $user = $this->security->getUser();

        if (!$user instanceof User || null === $user->getId()) {
            throw new AccessDeniedException('An authenticated Chamilo user is required.');
        }

        if (
            !$this->security->isGranted('ROLE_TEACHER')
            && !$this->security->isGranted('ROLE_ADMIN')
        ) {
            throw new AccessDeniedException('Only teachers and administrators can create courses.');
        }

        $title = trim($title);
        if ('' === $title) {
            throw new InvalidArgumentException('The course title is required.');
        }

        if (mb_strlen($title) > 250) {
            throw new InvalidArgumentException('The course title cannot be longer than 250 characters.');
        }

        $code = null !== $code ? trim($code) : null;
        if ('' === $code) {
            $code = null;
        }

        if (null !== $code && mb_strlen($code) > CourseHelper::MAX_COURSE_LENGTH_CODE) {
            throw new InvalidArgumentException(
                \sprintf(
                    'The course code cannot be longer than %d characters.',
                    CourseHelper::MAX_COURSE_LENGTH_CODE
                )
            );
        }

        $language = null !== $language ? trim($language) : null;
        if ('' === $language) {
            $language = null;
        }

        if (null !== $language && mb_strlen($language) > 20) {
            throw new InvalidArgumentException('The course language code cannot be longer than 20 characters.');
        }

        $params = [
            'title' => $title,
            'exemplary_content' => false,
        ];

        if (null !== $code) {
            $params['wanted_code'] = $code;
        }

        if (null !== $language) {
            $params['course_language'] = $language;
        }

        /** @var Course|null $course */
        $course = $this->entityManager->wrapInTransaction(
            fn (): ?Course => $this->courseHelper->createCourse($params)
        );

        if (!$course instanceof Course || null === $course->getId()) {
            throw new RuntimeException('Chamilo could not create the course.');
        }

        return [
            'created' => true,
            'course' => [
                'course_id' => $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
                'visual_code' => $course->getVisualCode(),
                'language' => $course->getCourseLanguage(),
                'visibility' => $course->getVisibility(),
                'url' => $this->urlGenerator->generate(
                    'chamilo_core_course_home',
                    ['cid' => $course->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ],
        ];
    }
}
