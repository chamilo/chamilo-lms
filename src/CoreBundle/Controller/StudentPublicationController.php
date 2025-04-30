<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\ServiceHelper\CidReqHelper;
use Chamilo\CoreBundle\ServiceHelper\MessageHelper;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/assignments')]
class StudentPublicationController extends AbstractController
{
    public function __construct(
        private readonly CStudentPublicationRepository $studentPublicationRepo,
        private readonly CidReqHelper $cidReqHelper
    ) {}


    #[Route('/student', name: 'chamilo_core_assignment_student_list', methods: ['GET'])]
    public function getStudentAssignments(SerializerInterface $serializer): JsonResponse
    {
        $course = $this->cidReqHelper->getCourseEntity();
        $session = $this->cidReqHelper->getSessionEntity();

        $assignments = $this->studentPublicationRepo->findVisibleAssignmentsForStudent($course, $session);

        $data = array_map(function ($row) use ($serializer) {
            $publication = $row[0] ?? null;
            $commentsCount = (int) ($row['commentsCount'] ?? 0);
            $correctionsCount = (int) ($row['correctionsCount'] ?? 0);
            $lastUpload = $row['lastUpload'] ?? null;

            $item = json_decode($serializer->serialize($publication, 'json', [
                'groups' => ['student_publication:read'],
            ]), true);

            $item['commentsCount'] = $commentsCount;
            $item['feedbackCount'] = $correctionsCount;
            $item['lastUpload'] = $lastUpload;

            return $item;
        }, $assignments);

        return new JsonResponse([
            'hydra:member' => $data,
            'hydra:totalItems' => count($data),
        ]);
    }

    #[Route('/progress', name: 'chamilo_core_student_progress', methods: ['GET'])]
    public function getStudentProgress(
        SerializerInterface $serializer
    ): JsonResponse {
        $course = $this->cidReqHelper->getCourseEntity();
        $session = $this->cidReqHelper->getSessionEntity();

        $progressList = $this->studentPublicationRepo->findStudentProgressByCourse($course, $session);

        return new JsonResponse([
            'hydra:member' => $progressList,
            'hydra:totalItems' => count($progressList),
        ]);
    }

    #[Route('/{assignmentId}/submissions', name: 'chamilo_core_student_submission_list', methods: ['GET'])]
    public function getAssignmentSubmissions(
        int $assignmentId,
        Request $request,
        SerializerInterface $serializer,
        CStudentPublicationRepository $repo,
        Security $security
    ): JsonResponse {
        /* @var User $user */
        $user = $security->getUser();

        $page = (int) $request->query->get('page', 1);
        $itemsPerPage = (int) $request->query->get('itemsPerPage', 10);
        $order = $request->query->all('order');

        [$submissions, $total] = $repo->findAssignmentSubmissionsPaginated(
            $assignmentId,
            $user,
            $page,
            $itemsPerPage,
            $order
        );

        $data = json_decode($serializer->serialize(
            $submissions,
            'json',
            ['groups' => ['student_publication:read']]
        ), true);

        return new JsonResponse([
            'hydra:member' => $data,
            'hydra:totalItems' => $total,
        ]);
    }

    #[Route('/{assignmentId}/submissions/teacher', name: 'chamilo_core_teacher_submission_list', methods: ['GET'])]
    public function getAssignmentSubmissionsForTeacher(
        int $assignmentId,
        Request $request,
        SerializerInterface $serializer,
        CStudentPublicationRepository $repo
    ): JsonResponse {
        $page = (int) $request->query->get('page', 1);
        $itemsPerPage = (int) $request->query->get('itemsPerPage', 10);
        $order = $request->query->all('order');

        [$submissions, $total] = $repo->findAllSubmissionsByAssignment(
            $assignmentId,
            $page,
            $itemsPerPage,
            $order
        );

        $data = json_decode($serializer->serialize(
            $submissions,
            'json',
            ['groups' => ['student_publication:read']]
        ), true);

        return new JsonResponse([
            'hydra:member' => $data,
            'hydra:totalItems' => $total,
        ]);
    }

    #[Route('/submissions/{id}', name: 'chamilo_core_student_submission_delete', methods: ['DELETE'])]
    public function deleteSubmission(
        int $id,
        EntityManagerInterface $em,
        CStudentPublicationRepository $repo
    ): JsonResponse {
        $submission = $repo->find($id);

        if (!$submission) {
            return new JsonResponse(['error' => 'Submission not found.'], 404);
        }

        $this->denyAccessUnlessGranted('DELETE', $submission->getResourceNode());

        $em->remove($submission->getResourceNode());
        $em->flush();

        return new JsonResponse(null, 204);
    }

    #[Route('/submissions/{id}/edit', name: 'chamilo_core_student_submission_edit', methods: ['PATCH'])]
    public function editSubmission(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        CStudentPublicationRepository $repo,
        MessageHelper $messageHelper
    ): JsonResponse {
        $submission = $repo->find($id);

        if (!$submission) {
            return new JsonResponse(['error' => 'Submission not found.'], 404);
        }

        $this->denyAccessUnlessGranted('EDIT', $submission->getResourceNode());

        $data = json_decode($request->getContent(), true);
        $title = $data['title'] ?? null;
        $description = $data['description'] ?? null;
        $sendMail = $data['sendMail'] ?? false;

        if ($title !== null) {
            $submission->setTitle($title);
        }
        if ($description !== null) {
            $submission->setDescription($description);
        }

        $em->flush();

        if ($sendMail) {
            $user = $submission->getUser();
            if ($user) {
                $messageSubject = sprintf('Feedback updated for "%s"', $submission->getTitle());
                $messageContent = sprintf(
                    'There is a new feedback update for your submission "%s". Please check it on the platform.',
                    $submission->getTitle()
                );

                $messageHelper->sendMessageSimple(
                    $user->getId(),
                    $messageSubject,
                    $messageContent,
                    $this->getUser()->getId()
                );
            }
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route('/submissions/{id}/move', name: 'chamilo_core_student_submission_move', methods: ['PATCH'])]
    public function moveSubmission(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        CStudentPublicationRepository $repo
    ): JsonResponse {
        $submission = $repo->find($id);

        if (!$submission) {
            return new JsonResponse(['error' => 'Submission not found.'], 404);
        }

        $this->denyAccessUnlessGranted('EDIT', $submission->getResourceNode());

        $data = json_decode($request->getContent(), true);
        $newAssignmentId = $data['newAssignmentId'] ?? null;

        if (!$newAssignmentId) {
            return new JsonResponse(['error' => 'New assignment ID is required.'], 400);
        }

        $newParent = $repo->find($newAssignmentId);

        if (!$newParent) {
            return new JsonResponse(['error' => 'Target assignment not found.'], 404);
        }

        $submission->setPublicationParent($newParent);

        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
