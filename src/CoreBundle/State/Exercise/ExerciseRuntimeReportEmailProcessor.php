<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeReportEmail;
use Chamilo\CoreBundle\Service\Exercise\ExerciseRuntimeResultEmailService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Sends reviewed exercise result emails from the migrated report.
 *
 * @implements ProcessorInterface<ExerciseRuntimeReportEmail, ExerciseRuntimeReportEmail>
 */
final readonly class ExerciseRuntimeReportEmailProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private ExerciseRuntimeResultEmailService $emailService,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseRuntimeReportEmail
    {
        if (!$data instanceof ExerciseRuntimeReportEmail) {
            throw new BadRequestHttpException('Invalid email request.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(
            ExerciseRuntimeReportProvider::EMAIL_ACTION_CSRF_TOKEN_ID,
            $data->submittedCsrfToken
        ))) {
            throw new BadRequestHttpException('Invalid security token.');
        }

        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : (int) ($data->exerciseId ?? 0);
        $result = $this->emailService->sendReviewedAttempts($exerciseId, $request, $data->node);

        $response = new ExerciseRuntimeReportEmail();
        $response->exerciseId = $exerciseId;
        $response->success = $result['success'];
        $response->message = $result['message'];
        $response->totalCount = $result['totalCount'];
        $response->sentCount = $result['sentCount'];
        $response->skippedCount = $result['skippedCount'];
        $response->failedCount = $result['failedCount'];
        $response->failures = $result['failures'];

        return $response;
    }
}
