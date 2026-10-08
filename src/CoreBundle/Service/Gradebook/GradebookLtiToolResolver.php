<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Chamilo\LtiBundle\Entity\ExternalTool;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class GradebookLtiToolResolver
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CShortcutRepository $shortcutRepository,
    ) {}

    /**
     * @return list<ExternalTool>
     */
    public function getAvailableTools(Course $course, ?Session $session): array
    {
        /** @var ExternalTool[] $tools */
        $tools = $this->entityManager->getRepository(ExternalTool::class)->findBy(
            ['gradebookEval' => null],
            ['id' => 'ASC'],
        );

        return array_values(array_filter(
            $tools,
            fn (ExternalTool $tool): bool => $this->isAssignedToContext($tool, $course, $session),
        ));
    }

    public function requireAvailableTool(int $toolId, Course $course, ?Session $session): ExternalTool
    {
        if ($toolId <= 0) {
            throw new BadRequestHttpException('A valid LTI tool id is required.');
        }

        $tool = $this->entityManager->getRepository(ExternalTool::class)->find($toolId);
        if (!$tool instanceof ExternalTool) {
            throw new NotFoundHttpException('The requested LTI tool was not found.');
        }

        if (null !== $tool->getGradebookEval()) {
            throw new BadRequestHttpException('The requested LTI tool is already linked to the Gradebook.');
        }

        if (!$this->isAssignedToContext($tool, $course, $session)) {
            throw new AccessDeniedHttpException('The requested LTI tool is outside the current course context.');
        }

        return $tool;
    }

    private function isAssignedToContext(ExternalTool $tool, Course $course, ?Session $session): bool
    {
        if (!$tool->hasResourceNode()) {
            return false;
        }

        if ($this->hasContextLink($tool, $course, $session)) {
            return true;
        }

        $shortcut = $this->shortcutRepository->findShortcutFromResourceInCourse($tool, $course);

        return null !== $shortcut && $this->hasContextLink($shortcut, $course, $session);
    }

    private function hasContextLink(AbstractResource $resource, Course $course, ?Session $session): bool
    {
        $resourceNode = $resource->getResourceNode();
        if (null === $resourceNode) {
            return false;
        }

        $courseId = (int) $course->getId();
        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $hasCourseLevelLink = false;

        foreach ($resourceNode->getResourceLinks() as $link) {
            $linkCourse = $link->getCourse();
            if (null === $linkCourse || (int) $linkCourse->getId() !== $courseId) {
                continue;
            }

            $linkSessionId = null !== $link->getSession() ? (int) $link->getSession()->getId() : 0;

            if ($sessionId > 0) {
                if ($linkSessionId === $sessionId) {
                    return true;
                }

                if (0 === $linkSessionId) {
                    $hasCourseLevelLink = true;
                }

                continue;
            }

            if (0 === $linkSessionId) {
                return true;
            }
        }

        return $sessionId > 0 && $hasCourseLevelLink;
    }
}
