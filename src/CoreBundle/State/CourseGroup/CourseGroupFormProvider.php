<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseGroup;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseGroup\CourseGroupForm;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<CourseGroupForm>
 */
final readonly class CourseGroupFormProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseGroupManager $manager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseGroupForm
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }
        $groupId = (int) ($uriVariables['groupId'] ?? $request->attributes->get('groupId', 0));
        $data = $this->manager->getGroupFormData($request, $groupId);
        $resource = new CourseGroupForm();
        foreach ($data as $property => $value) {
            $resource->{$property} = $value;
        }
        $resource->csrfToken = $this->csrfTokenManager->getToken($this->manager->getCsrfIntention())->getValue();

        return $resource;
    }
}
