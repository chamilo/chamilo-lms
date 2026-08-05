<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseGroup;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseGroup\CourseGroupCategoryForm;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<CourseGroupCategoryForm>
 */
final readonly class CourseGroupCategoryFormProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseGroupManager $manager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseGroupCategoryForm
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }
        $categoryId = (int) ($uriVariables['categoryId'] ?? $request->attributes->get('categoryId', 0));
        $data = $this->manager->getCategoryFormData($categoryId);
        $resource = new CourseGroupCategoryForm();
        foreach ($data as $property => $value) {
            $resource->{$property} = $value;
        }
        $resource->csrfToken = $this->csrfTokenManager->getToken($this->manager->getCsrfIntention())->getValue();

        return $resource;
    }
}
