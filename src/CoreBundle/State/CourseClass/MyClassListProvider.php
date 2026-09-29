<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseClass;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseClass\MyClassList;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<MyClassList>
 */
final readonly class MyClassListProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseClassManager $manager,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): MyClassList
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }

        $data = $this->manager->getMyClassesData($request);
        $resource = new MyClassList();
        $resource->items = (array) $data['items'];
        $resource->totalItems = (int) $data['totalItems'];
        $resource->canAddClasses = !empty($data['canAddClasses']);
        $resource->addClassesUrl = (string) $data['addClassesUrl'];

        return $resource;
    }
}
