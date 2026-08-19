<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseGroup;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseGroup\CourseGroupImport;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<CourseGroupImport>
 */
final readonly class CourseGroupImportProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseGroupManager $manager,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseGroupImport
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }
        [$course, $session] = $this->manager->resolveContext();
        $this->manager->assertCanManage($course, $session);
        $resource = new CourseGroupImport();
        $resource->canImport = true;

        return $resource;
    }
}
