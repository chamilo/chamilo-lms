<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseGroup;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseGroup\CourseGroupCategoryForm;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<CourseGroupCategoryForm, CourseGroupCategoryForm>
 */
final readonly class CourseGroupCategoryFormProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseGroupManager $manager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CourseGroupCategoryForm
    {
        if (!$data instanceof CourseGroupCategoryForm) {
            throw new BadRequestHttpException('Invalid category form payload.');
        }
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }
        $categoryId = (int) ($uriVariables['categoryId'] ?? $request->attributes->get('categoryId', 0));
        $data->categoryId = $this->manager->saveCategory($data, $categoryId);
        $data->success = true;
        $data->message = 'Saved';

        return $data;
    }
}
