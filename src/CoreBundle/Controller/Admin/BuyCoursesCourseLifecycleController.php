<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use BuyCoursesPlugin;
use Chamilo\CoreBundle\Service\BuyCoursesCourseDeletionService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

use const FILTER_VALIDATE_INT;
use const PHP_BINDIR;

#[IsGranted('ROLE_ADMIN')]
final class BuyCoursesCourseLifecycleController extends AbstractController
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly BuyCoursesCourseDeletionService $deletionService,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/admin/buycourses/course-lifecycle-data', name: 'admin_buycourses_course_lifecycle_data', methods: ['GET'])]
    public function data(): JsonResponse
    {
        $this->requireBuyCoursesServices();

        try {
            return $this->json($this->buildPreview());
        } catch (Throwable $exception) {
            $this->logger->error('Unable to preview BuyCourses course lifecycle.', ['exception' => $exception]);

            return $this->json(['error' => 'Unable to load course lifecycle data.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/admin/buycourses/course-lifecycle-run', name: 'admin_buycourses_course_lifecycle_run', methods: ['POST'])]
    public function run(Request $request): JsonResponse
    {
        $this->requireBuyCoursesServices();

        try {
            $payload = $request->toArray();
        } catch (Throwable) {
            return $this->json(['error' => 'Invalid request body.'], Response::HTTP_BAD_REQUEST);
        }

        $action = $payload['action'] ?? null;
        if (!\is_string($action) || !\in_array($action, ['simulate', 'execute'], true)) {
            return $this->json(['error' => 'Unsupported lifecycle action.'], Response::HTTP_BAD_REQUEST);
        }

        $courseId = null;
        if ('execute' === $action) {
            $courseId = filter_var($payload['courseId'] ?? null, FILTER_VALIDATE_INT);
            if (false === $courseId || $courseId < 1) {
                return $this->json(['error' => 'Choose a valid eligible course ID.'], Response::HTTP_BAD_REQUEST);
            }

            if ('DELETE EXPIRED COURSES' !== ($payload['confirmation'] ?? null)) {
                return $this->json(['error' => 'Enter the required confirmation phrase.'], Response::HTTP_BAD_REQUEST);
            }

            try {
                $preview = $this->buildPreview();
                $selectedEligible = false;
                foreach ($preview['courses'] as $row) {
                    if ((int) $row['course_id'] === $courseId && 'delete' === $row['decision']['action']) {
                        $selectedEligible = true;

                        break;
                    }
                }
                if (!$selectedEligible) {
                    return $this->json(['error' => 'The selected course is not eligible for deletion.'], Response::HTTP_CONFLICT);
                }
            } catch (Throwable $exception) {
                $this->logger->error('BuyCourses lifecycle eligibility check failed.', ['exception' => $exception]);

                return $this->json(['error' => 'Unable to verify eligible courses.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $phpBinary = PHP_BINDIR.'/php';
        $console = $this->kernel->getProjectDir().'/bin/console';
        if (!is_executable($phpBinary) || !is_file($console)) {
            return $this->json(['error' => 'The PHP CLI is not available.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $args = [
            $phpBinary,
            $console,
            'chamilo:buycourses:process-recurring-subscriptions',
            '--env='.$this->kernel->getEnvironment(),
        ];

        if ('simulate' === $action) {
            $args[] = '--dry-run';
        } else {
            $args[] = '--delete-expired';
            $args[] = '--max-deletions=1';
            $args[] = '--course-id='.$courseId;
        }

        try {
            $process = new Process($args, $this->kernel->getProjectDir());
            $process->setTimeout(null);
            $process->run();

            $result = [
                'success' => $process->isSuccessful(),
                // Keep stack traces and gateway identifiers out of HTTP responses.
                'output' => trim($process->getOutput()),
                'action' => $action,
            ];

            if (!$result['success']) {
                $this->logger->warning('BuyCourses lifecycle command failed.', [
                    'action' => $action,
                    'exit_code' => $process->getExitCode(),
                ]);
                $result['error'] = 'The lifecycle command reported an error. Review the output.';

                return $this->json($result, Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $result['preview'] = $this->buildPreview();

            return $this->json($result);
        } catch (Throwable $exception) {
            $this->logger->error('BuyCourses lifecycle command could not be started.', ['exception' => $exception]);

            return $this->json(['error' => 'The lifecycle command could not be completed.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @return array{courses: array<int, array<string, mixed>>, counts: array<string, int>}
     */
    private function buildPreview(): array
    {
        $courses = $this->deletionService->preview();
        $counts = ['delete' => 0, 'pending' => 0, 'blocked' => 0, 'disabled' => 0];

        foreach ($courses as $course) {
            $action = (string) ($course['decision']['action'] ?? 'blocked');
            if (\array_key_exists($action, $counts)) {
                ++$counts[$action];
            }
        }

        return ['courses' => $courses, 'counts' => $counts];
    }

    private function requireBuyCoursesServices(): void
    {
        $plugin = BuyCoursesPlugin::create();
        if (!$plugin->isEnabled() || 'true' !== $plugin->get('include_services')) {
            throw $this->createNotFoundException();
        }
    }
}
