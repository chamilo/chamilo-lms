<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseGroup;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseGroup\CourseGroupImport;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<CourseGroupImport>
 */
final readonly class CourseGroupImportProvider implements ProviderInterface
{
    public function __construct(
        private CourseGroupManager $manager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseGroupImport
    {
        [$course, $session] = $this->manager->resolveContext();
        $this->manager->assertCanManage($course, $session);
        $resource = new CourseGroupImport();
        $resource->canImport = true;
        $resource->csrfToken = $this->csrfTokenManager->getToken($this->manager->getCsrfIntention())->getValue();

        return $resource;
    }
}
