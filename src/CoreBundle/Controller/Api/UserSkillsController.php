<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserSkillsController
{
    public function __invoke(Request $request, EntityManagerInterface $entityManager, AssetRepository $assetRepository): JsonResponse
    {
        $userId = $request->attributes->get('id');
        $skillRelUserRepository = $entityManager->getRepository(SkillRelUser::class);

        $skillRelUsers = $skillRelUserRepository->findBy(['user' => $userId]);

        $skillsData = [];
        foreach ($skillRelUsers as $skillRelUser) {
            $skillAsset = $skillRelUser->getSkill()->getAsset();

            $skillData = [
                'id' => $skillRelUser->getSkill()->getId(),
                'name' => $skillRelUser->getSkill()->getTitle(),
                'image' => $skillAsset ? $assetRepository->getAssetUrl($skillAsset) : '',
            ];
            $skillsData[] = $skillData;
        }

        return new JsonResponse($skillsData);
    }
}
