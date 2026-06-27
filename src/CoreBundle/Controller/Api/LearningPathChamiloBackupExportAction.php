<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Service\LearningPath\LearningPathBackupEmptyException;
use Chamilo\CoreBundle\Service\LearningPath\LearningPathBackupResourceException;
use Chamilo\CoreBundle\Service\LearningPath\LearningPathChamiloBackupExportService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\LearningPath\LearningPathStateHelperTrait;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use RuntimeException;

#[IsGranted('ROLE_USER')]
final class LearningPathChamiloBackupExportAction extends AbstractController
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly SettingsManager $settingsManager,
        private readonly CLpRepository $learningPathRepository,
        private readonly LearningPathChamiloBackupExportService $exportService,
    ) {}

    #[Route(
        '/api/learning_paths/{lpId}/chamilo-backup.zip',
        name: 'api_learning_path_chamilo_backup',
        requirements: ['lpId' => '\\d+'],
        methods: ['GET'],
    )]
    public function __invoke(int $lpId, Request $request): BinaryFileResponse
    {
        if (!$this->settingEnabled('lp.allow_lp_chamilo_export')) {
            throw new AccessDeniedHttpException('Chamilo learning path export is disabled.');
        }

        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);
        if (null !== $group) {
            throw new BadRequestHttpException('Chamilo learning path export is not available in a group context.');
        }

        $this->assertLearningPathTeacher($this->security);

        $learningPath = $this->learningPathRepository->find($lpId);
        if (!$learningPath instanceof CLp) {
            throw new NotFoundHttpException('Learning path not found.');
        }

        $resourceNode = $learningPath->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted('EDIT', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to export this learning path.');
        }

        $resourceLink = $this->getContextResourceLink($learningPath, $course, $session, null);
        if (!$resourceLink instanceof ResourceLink) {
            throw new AccessDeniedHttpException('The learning path is not linked to the current context.');
        }

        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        try {
            $archivePath = $this->exportService->export($learningPath, $course, $session);
        } catch (LearningPathBackupEmptyException $exception) {
            throw new UnprocessableEntityHttpException($exception->getMessage(), $exception);
        } catch (LearningPathBackupResourceException $exception) {
            throw new UnprocessableEntityHttpException($exception->getMessage(), $exception);
        } catch (RuntimeException $exception) {
            throw new HttpException(
                500,
                'The Chamilo learning path backup could not be generated.',
                $exception,
            );
        }

        $filename = sprintf('learning-path-%d-%s.zip', $lpId, date('Ymd-His'));

        $response = new BinaryFileResponse($archivePath);
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);

        return $response;
    }

    private function settingEnabled(string $name): bool
    {
        return \in_array(
            strtolower(trim((string) $this->settingsManager->getSetting($name, true))),
            ['1', 'true', 'yes', 'on'],
            true,
        );
    }
}
