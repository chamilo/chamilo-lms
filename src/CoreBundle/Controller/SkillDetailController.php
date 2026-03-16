<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelGradebook;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class SkillDetailController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('/skill/{id}/detail-data', name: 'skill_detail_data', methods: ['GET'])]
    public function __invoke(int $id): JsonResponse
    {
        $skill = $this->em->getRepository(Skill::class)->find($id);

        if (!$skill) {
            return $this->json(['error' => 'Skill not found'], Response::HTTP_NOT_FOUND);
        }

        $gradebookLinks = [];

        /** @var SkillRelGradebook $srg */
        foreach ($skill->getGradeBookCategories() as $srg) {
            $category = $srg->getGradeBookCategory();
            $course = $category->getCourse();
            $session = $category->getSession();

            $gradebookLinks[] = [
                'courseTitle' => $course->getTitle(),
                'courseId' => $course->getId(),
                'sessionId' => $session?->getId(),
                'sessionTitle' => $session?->getTitle(),
            ];
        }

        return $this->json([
            'gradebookLinks' => $gradebookLinks,
        ]);
    }
}
