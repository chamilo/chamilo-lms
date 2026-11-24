<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\State\CourseSettings\CourseSettingsManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class CourseSettingsIntroductionPositionController extends AbstractController
{
    public function __construct(
        private readonly CourseSettingsManager $manager,
    ) {}

    #[Route('/api/course-settings/introduction-position', name: 'api_course_settings_introduction_position', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true);
        if (!\is_array($payload)) {
            throw new BadRequestHttpException('Invalid introduction position payload.');
        }

        $position = $payload['position'] ?? $payload['introduction_position'] ?? '';
        if (!\is_scalar($position)) {
            throw new BadRequestHttpException('Invalid introduction position.');
        }

        $saved = $this->manager->saveIntroductionPosition(trim((string) $position));

        return $this->json([
            'success' => true,
            'introduction_position' => $saved,
        ]);
    }
}
