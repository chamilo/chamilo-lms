<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookLtiOptions;
use Chamilo\CoreBundle\Service\Gradebook\GradebookLtiToolResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<GradebookLtiOptions>
 */
final readonly class GradebookLtiOptionsProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private GradebookContextResolver $contextResolver,
        private GradebookLtiToolResolver $ltiToolResolver,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GradebookLtiOptions
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $resolved = $this->contextResolver->resolve($request, true);
        $result = new GradebookLtiOptions();

        foreach ($this->ltiToolResolver->getAvailableTools($resolved->course, $resolved->session) as $tool) {
            $result->tools[] = [
                'value' => (int) $tool->getId(),
                'label' => $tool->getTitle(),
            ];
        }

        return $result;
    }
}
