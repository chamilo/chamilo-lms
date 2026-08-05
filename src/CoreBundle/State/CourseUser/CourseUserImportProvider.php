<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseUser;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseUser\CourseUserImport;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<CourseUserImport>
 */
final readonly class CourseUserImportProvider implements ProviderInterface
{
    public function __construct(
        private CourseUserManager $courseUserManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseUserImport
    {
        [$course, $session] = $this->courseUserManager->resolveContext();
        $this->courseUserManager->assertCanManage($course, $session);
        if (!$this->courseUserManager->canUnsubscribe($course, $session)) {
            throw new AccessDeniedHttpException('Course user import is disabled for the current manager.');
        }

        $response = new CourseUserImport();
        $response->canImport = true;
        $response->csrfToken = (string) $this->csrfTokenManager->getToken(CourseUserListProvider::CSRF_TOKEN_ID);

        return $response;
    }
}
