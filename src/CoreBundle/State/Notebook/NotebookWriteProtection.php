<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Notebook;

use Chamilo\CoreBundle\EventListener\CourseContextRoleListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final readonly class NotebookWriteProtection
{
    public function __construct(
        private RequestStack $requestStack,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function assertWriteAllowed(string $submittedCsrfToken): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request instanceof Request && true === $request->attributes->get(
            CourseContextRoleListener::JWT_AUTHENTICATED_REQUEST_ATTRIBUTE,
            false,
        )) {
            return;
        }

        $csrfToken = new CsrfToken(NotebookItemProvider::CSRF_TOKEN_ID, $submittedCsrfToken);
        if (!$this->csrfTokenManager->isTokenValid($csrfToken)) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }
}
