<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Admin\AdminQuestionBank;
use Chamilo\CoreBundle\Service\Admin\AdminQuestionBankManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<AdminQuestionBank>
 */
final readonly class AdminQuestionBankProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private AdminQuestionBankManager $manager,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AdminQuestionBank
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request could not be resolved.');
        }

        $data = $this->manager->getData($request);
        $response = new AdminQuestionBank();
        $response->items = $data['items'];
        $response->courseOptions = $data['courseOptions'];
        $response->difficultyOptions = $data['difficultyOptions'];
        $response->questionTypeOptions = $data['questionTypeOptions'];
        $response->extraFields = $data['extraFields'];
        $response->filters = $data['filters'];
        $response->page = $data['page'];
        $response->itemsPerPage = $data['itemsPerPage'];
        $response->totalItems = $data['totalItems'];
        $response->searched = $data['searched'];

        return $response;
    }
}
