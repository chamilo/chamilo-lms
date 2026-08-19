<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseUser;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseUser\CourseUserImport;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<CourseUserImport>
 */
final readonly class CourseUserImportProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseUserManager $courseUserManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseUserImport
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        [$course, $session] = $this->courseUserManager->resolveContext();
        $this->courseUserManager->assertCanManage($course, $session);
        if (!$this->courseUserManager->canUnsubscribe($course, $session)) {
            throw new AccessDeniedHttpException('Course user import is disabled for the current manager.');
        }

        $response = new CourseUserImport();
        $response->canImport = true;

        return $response;
    }
}
