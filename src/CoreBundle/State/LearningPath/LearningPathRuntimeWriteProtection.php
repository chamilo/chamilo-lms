<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use Chamilo\CoreBundle\EventListener\CourseContextRoleListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final readonly class LearningPathRuntimeWriteProtection
{
    private const ACTION_TOKEN_INTENTION = 'learning_path_action';

    public function __construct(
        private RequestStack $requestStack,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function assertWriteAllowed(mixed $submittedCsrfToken): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request instanceof Request
            && true === $request->attributes->get(
                CourseContextRoleListener::JWT_AUTHENTICATED_REQUEST_ATTRIBUTE,
                false,
            )
        ) {
            return;
        }

        if (!\is_string($submittedCsrfToken) || '' === trim($submittedCsrfToken)) {
            throw new BadRequestHttpException('Missing CSRF token.');
        }

        $csrfToken = new CsrfToken(self::ACTION_TOKEN_INTENTION, $submittedCsrfToken);
        if (!$this->csrfTokenManager->isTokenValid($csrfToken)) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
    }
}
