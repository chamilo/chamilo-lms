<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Admin\AdminQuestionBank;
use Chamilo\CoreBundle\Service\Admin\AdminQuestionBankManager;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<AdminQuestionBank, AdminQuestionBank>
 */
final readonly class AdminQuestionBankProcessor implements ProcessorInterface
{
    public function __construct(
        private AdminQuestionBankManager $manager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AdminQuestionBank
    {
        if (!$data instanceof AdminQuestionBank) {
            throw new BadRequestHttpException('Invalid question bank action payload.');
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(
            AdminQuestionBankManager::CSRF_TOKEN_ID,
            $data->submittedCsrfToken
        ))) {
            throw new BadRequestHttpException('The security token is invalid or has expired.');
        }

        if ('delete' !== $data->action) {
            throw new BadRequestHttpException('Unsupported question bank action.');
        }

        $questionId = (int) ($data->questionId ?? 0);
        $this->manager->deleteQuestion($questionId);

        $response = new AdminQuestionBank();
        $response->success = true;
        $response->message = 'Deleted';
        $response->questionId = $questionId;

        return $response;
    }
}
