<?php
declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/social')]
class SocialController extends AbstractController
{

    #[Route('/personal-data', name: 'chamilo_core_social_personal_data')]
    public function getPersonalData(): JsonResponse
    {
        $userId = $this->getUser()->getId();

        $permissionBlock = [];
        $personalData = [];
        $termLink = [];

        $dataForVue = [
            'personalData' => $personalData,
            'permissionBlock' => $permissionBlock,
            'termLink' => $termLink,
        ];

        return $this->json($dataForVue);
    }
}
