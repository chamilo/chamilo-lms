<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ExerciseRuntimeAttemptFileDownloadAction extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ResourceNodeRepository $resourceNodeRepository,
        private readonly Security $security,
    ) {}

    #[Route(
        '/api/exercise/runtime/{exerciseId}/attempt/{attemptId}/file/{resourceNodeId}/download',
        name: 'chamilo_core_exercise_runtime_attempt_file_download',
        requirements: [
            'exerciseId' => '\\d+',
            'attemptId' => '\\d+',
            'resourceNodeId' => '\\d+',
        ],
        methods: ['GET']
    )]
    public function __invoke(int $exerciseId, int $attemptId, int $resourceNodeId, Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid authenticated user is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $attemptRow = $this->getAttemptRowWithFile($exerciseId, $attemptId, $resourceNodeId, $course, $session);
        $attempt = $attemptRow->getTrackEExercise();

        if (!$this->canDownloadAttemptFile($attempt, $user)) {
            throw new AccessDeniedHttpException('You are not allowed to download this exercise attempt file.');
        }

        $resourceNode = $this->getAttachedResourceNode($attemptRow, $resourceNodeId);
        if (!$resourceNode instanceof ResourceNode) {
            throw new NotFoundHttpException('The requested exercise attempt file was not found.');
        }

        $resourceFile = $resourceNode->getResourceFiles()->first();
        if (!$resourceFile instanceof ResourceFile) {
            throw new NotFoundHttpException('The requested exercise attempt file was not found.');
        }

        $stream = $this->resourceNodeRepository->getResourceNodeFileStream($resourceNode, $resourceFile);
        if (!\is_resource($stream)) {
            throw new NotFoundHttpException('The requested exercise attempt file could not be opened.');
        }

        $fileName = $resourceFile->getOriginalName() ?: $resourceNode->getTitle() ?: 'answer-file';
        $response = new StreamedResponse(static function () use ($stream): void {
            fpassthru($stream);
            if (\is_resource($stream)) {
                fclose($stream);
            }
        });

        $response->headers->set('Content-Type', $resourceFile->getMimeType() ?: 'application/octet-stream');
        if (0 < (int) $resourceFile->getSize()) {
            $response->headers->set('Content-Length', (string) $resourceFile->getSize());
        }
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                true === $request->query->getBoolean('inline') ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $fileName,
                $this->getAsciiFileName($fileName)
            )
        );

        return $response;
    }

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if (0 >= $courseId) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }

    private function getSession(Request $request): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if (0 >= $sessionId) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
    }

    private function getAttemptRowWithFile(
        int $exerciseId,
        int $attemptId,
        int $resourceNodeId,
        Course $course,
        ?Session $session,
    ): TrackEAttempt {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attemptRow')
            ->addSelect('attempt', 'attemptFile', 'resourceNode', 'resourceFile')
            ->from(TrackEAttempt::class, 'attemptRow')
            ->innerJoin('attemptRow.trackExercise', 'attempt')
            ->innerJoin('attemptRow.attemptFiles', 'attemptFile')
            ->innerJoin('attemptFile.resourceNode', 'resourceNode')
            ->leftJoin('resourceNode.resourceFiles', 'resourceFile')
            ->andWhere('attempt.exeId = :attemptId')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->andWhere('resourceNode.id = :resourceNodeId')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->setParameter('exerciseId', $exerciseId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('resourceNodeId', $resourceNodeId, Types::INTEGER)
            ->setMaxResults(1)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('attempt.session IS NULL');
        }

        $attemptRow = $queryBuilder->getQuery()->getOneOrNullResult();
        if (!$attemptRow instanceof TrackEAttempt) {
            throw new NotFoundHttpException('The requested exercise attempt file was not found.');
        }

        return $attemptRow;
    }

    private function canDownloadAttemptFile(TrackEExercise $attempt, User $user): bool
    {
        if ((int) $attempt->getUser()->getId() === (int) $user->getId()) {
            return true;
        }

        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
            || $this->security->isGranted('ROLE_ADMIN');
    }

    private function getAttachedResourceNode(TrackEAttempt $attemptRow, int $resourceNodeId): ?ResourceNode
    {
        foreach ($attemptRow->getAttemptFiles() as $attemptFile) {
            $resourceNode = $attemptFile->getResourceNode();
            if ($resourceNode instanceof ResourceNode && (int) $resourceNode->getId() === $resourceNodeId) {
                return $resourceNode;
            }
        }

        return null;
    }

    private function getAsciiFileName(string $fileName): string
    {
        $asciiFileName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $fileName);
        if (!\is_string($asciiFileName)) {
            return 'answer-file';
        }

        $asciiFileName = trim($asciiFileName, '._-');

        return '' !== $asciiFileName ? $asciiFileName : 'answer-file';
    }
}
