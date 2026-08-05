<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseGroup;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseGroup\CourseGroupCategoryForm;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<CourseGroupCategoryForm, CourseGroupCategoryForm>
 */
final readonly class CourseGroupCategoryFormProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseGroupManager $manager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CourseGroupCategoryForm
    {
        if (!$data instanceof CourseGroupCategoryForm) {
            throw new BadRequestHttpException('Invalid category form payload.');
        }
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($this->manager->getCsrfIntention(), $data->csrfToken))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }
        $categoryId = (int) ($uriVariables['categoryId'] ?? $request->attributes->get('categoryId', 0));
        $data->categoryId = $this->manager->saveCategory($data, $request, $categoryId);
        $data->success = true;
        $data->message = 'Saved';

        return $data;
    }
}
