<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BadgeController extends AbstractController
{
    #[Route('/badge/{id}', name: 'badge_issued')]
    public function issued(int $id): Response
    {
        return $this->redirect(
            '/main/badge/issued.php?'.http_build_query(['issue' => $id])
        );
    }
}
