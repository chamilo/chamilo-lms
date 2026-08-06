<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\State\CourseSettings\CourseSettingsManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class CourseSettingsEntryController extends AbstractController
{
    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly CourseSettingsManager $courseSettingsManager,
    ) {}

    #[Route('/course-settings/{courseId}', name: 'course_settings_entry', requirements: ['courseId' => '\d+'], methods: ['GET'])]
    public function __invoke(int $courseId, Request $request): RedirectResponse
    {
        $course = $this->courseRepository->find($courseId);
        if (!$course instanceof Course) {
            throw new NotFoundHttpException('The requested course was not found.');
        }

        $this->courseSettingsManager->assertCanEdit($course);
        $resourceNodeId = (int) ($course->getResourceNode()?->getId() ?? 0);
        if ($resourceNodeId <= 0) {
            throw new NotFoundHttpException('The course resource node was not found.');
        }

        $query = [
            'cid' => $courseId,
            'sid' => max(0, $request->query->getInt('sid')),
            'gid' => max(0, $request->query->getInt('gid')),
        ];

        return new RedirectResponse(
            '/resources/course-settings/'.$resourceNodeId.'/?'.http_build_query($query),
        );
    }
}
