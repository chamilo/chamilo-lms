<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\State\Notebook;

use Chamilo\CoreBundle\EventListener\CourseContextRoleListener;
use Chamilo\CoreBundle\State\Notebook\NotebookWriteProtection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class NotebookWriteProtectionTest extends TestCase
{
    public function testAcceptsServerMarkedJwtRequestWithoutSessionCsrf(): void
    {
        $request = new Request();
        $request->attributes->set(
            CourseContextRoleListener::JWT_AUTHENTICATED_REQUEST_ATTRIBUTE,
            true,
        );

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager
            ->expects(self::never())
            ->method('isTokenValid')
        ;

        $protection = new NotebookWriteProtection($requestStack, $csrfTokenManager);
        $protection->assertWriteAllowed('');
    }

    public function testAcceptsValidCsrfForNonJwtRequest(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager
            ->expects(self::once())
            ->method('isTokenValid')
            ->with(self::callback(
                static fn (CsrfToken $token): bool => 'notebook_item' === $token->getId()
                    && 'valid-session-csrf' === $token->getValue()
            ))
            ->willReturn(true)
        ;

        $protection = new NotebookWriteProtection($requestStack, $csrfTokenManager);
        $protection->assertWriteAllowed('valid-session-csrf');
    }

    public function testRejectsInvalidCsrfForNonJwtRequest(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager
            ->expects(self::once())
            ->method('isTokenValid')
            ->willReturn(false)
        ;

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('The security token is invalid.');

        $protection = new NotebookWriteProtection($requestStack, $csrfTokenManager);
        $protection->assertWriteAllowed('');
    }
}
