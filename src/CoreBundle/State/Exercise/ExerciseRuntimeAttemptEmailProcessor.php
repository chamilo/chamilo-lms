<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeAttemptEmail;
use Chamilo\CoreBundle\Service\Exercise\ExerciseRuntimeResultEmailService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Sends one migrated exercise attempt result email using Symfony mailer.
 *
 * @implements ProcessorInterface<ExerciseRuntimeAttemptEmail, ExerciseRuntimeAttemptEmail>
 */
final readonly class ExerciseRuntimeAttemptEmailProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private ExerciseRuntimeResultEmailService $emailService,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseRuntimeAttemptEmail
    {
        if (!$data instanceof ExerciseRuntimeAttemptEmail) {
            throw new BadRequestHttpException('Invalid email request.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : (int) ($data->exerciseId ?? 0);
        $attemptId = isset($uriVariables['attemptId']) ? (int) $uriVariables['attemptId'] : (int) ($data->attemptId ?? 0);
        $result = $this->emailService->sendAttempt($exerciseId, $attemptId, $request, (string) ($data->node ?? ''));

        $response = new ExerciseRuntimeAttemptEmail();
        $response->exerciseId = $exerciseId;
        $response->attemptId = $attemptId;
        $response->success = $result['success'];
        $response->message = $result['message'];
        $response->recipientId = $result['recipientId'];
        $response->recipientName = $result['recipientName'];
        $response->recipientEmail = $result['recipientEmail'];

        return $response;
    }
}
