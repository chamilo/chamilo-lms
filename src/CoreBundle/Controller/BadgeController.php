<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BadgeController extends AbstractController
{
    use ControllerTrait;

    #[Route('/badge/{id}', name: 'badge_issued')]
    public function issued(int $id): Response
    {
        return $this->redirect(
            '/main/badge/issued.php?'.http_build_query(['issue' => $id])
        );
    }

    #[Route('/badge/{skillId}/user/{userId}')]
    #[Route('/skill/{skillId}/user/{userId}', name: 'badge_issued_all')]
    public function issuedAll(int $skillId, int $userId): Response
    {
        return $this->redirect(
            '/main/badge/issued_all.php?'.http_build_query(['skill' => $skillId, 'user' => $userId])
        );
    }
}
