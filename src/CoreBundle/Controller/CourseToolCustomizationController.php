<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Service\CourseToolCustomizationService;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class CourseToolCustomizationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly IsAllowedToEditHelper $isAllowedToEdit,
        private readonly CourseToolCustomizationService $customization,
    ) {}

    #[Route(
        '/course/{courseId}/tools/{toolId}/customize',
        name: 'course_tool_customize',
        requirements: ['courseId' => '\d+', 'toolId' => '\d+'],
        methods: ['POST'],
    )]
    public function customize(int $courseId, int $toolId, Request $request): JsonResponse
    {
        // This is a per-course feature, never a session-level customization.
        if ($request->query->getInt('cid') !== $courseId || 0 !== $request->query->getInt('sid')) {
            throw new BadRequestHttpException('Invalid course context.');
        }

        $tool = $this->entityManager->getRepository(CTool::class)->find($toolId);
        if (!$tool instanceof CTool || $tool->getCourse()->getId() !== $courseId || null !== $tool->getSession()) {
            throw $this->createNotFoundException('Course tool not found.');
        }

        if (!$this->getUser() instanceof User
            || !$this->isAllowedToEdit->check(course: $tool->getCourse(), session: null)
        ) {
            throw $this->createAccessDeniedException('You cannot customize this course tool.');
        }

        $icon = $request->files->get('icon');
        if (null !== $icon && !$icon instanceof UploadedFile) {
            throw new BadRequestHttpException('Invalid SVG upload.');
        }

        return $this->json($this->customization->update(
            $tool,
            (string) $request->request->get('title', ''),
            $icon,
        ));
    }
}
