<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class DocumentLearningPathUsageAction extends AbstractController
{
    public function __construct(
        private CLpItemRepository $lpItemRepo
    ) {}

    public function __invoke($iid): JsonResponse
    {
        $lpUsages = $this->lpItemRepo->findLearningPathsUsingDocument((int) $iid);

        return new JsonResponse([
            'usedInLp' => !empty($lpUsages),
            'lpList' => $lpUsages,
        ]);
    }
}
