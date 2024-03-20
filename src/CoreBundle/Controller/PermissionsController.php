<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

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

    #[Route('/is_allowed_to_edit_course/{courseId}')]
    public function isAllowedToEditCourse(int $courseId, AuthorizationCheckerInterface $authorizationChecker, EntityManagerInterface $entityManager): Response
    {
        $course = $entityManager->getRepository(Course::class)->find($courseId);

        if (!$course) {
            return $this->json(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }

        $isAllowed = $authorizationChecker->isGranted(CourseVoter::EDIT, $course);

        return $this->json([
            'isAllowedToEditCourse' => $isAllowed,
        ]);
    }

}
