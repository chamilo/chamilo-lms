<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/permissions')]
class PermissionsController extends AbstractController
{
    #[Route('/is_allowed_to_edit')]
    public function isAllowedToEdit(Request $request): Response
    {
        $tutor = $request->query->getBoolean('tutor');
        $coach = $request->query->getBoolean('coach');
        $sessionCoach = $request->query->getBoolean('sessioncoach');
        $checkStudentView = $request->query->getBoolean('checkstudentview');

        $isAllowed = api_is_allowed_to_edit(
            $tutor,
            $coach,
            $sessionCoach,
            $checkStudentView
        );

        return $this->json([
            'isAllowedToEdit' => $isAllowed,
        ]);
    }
}
