<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\State\LearningPath;

use Chamilo\CoreBundle\EventListener\CourseContextRoleListener;
use Chamilo\CoreBundle\State\LearningPath\LearningPathRuntimeWriteProtection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class LearningPathRuntimeWriteProtectionTest extends TestCase
{
    public function testAllowsJwtAuthenticatedRuntimeWriteWithoutCsrf(): void
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

        $protection = new LearningPathRuntimeWriteProtection($requestStack, $csrfTokenManager);
        $protection->assertWriteAllowed('');
    }

    public function testAllowsValidCsrfForNonJwtRequest(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager
            ->expects(self::once())
            ->method('isTokenValid')
            ->with(self::callback(
                static fn (CsrfToken $token): bool => 'learning_path_action' === $token->getId()
                    && 'valid-session-csrf' === $token->getValue(),
            ))
            ->willReturn(true)
        ;

        $protection = new LearningPathRuntimeWriteProtection($requestStack, $csrfTokenManager);
        $protection->assertWriteAllowed('valid-session-csrf');
    }

    public function testRejectsMissingCsrfForNonJwtRequest(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager
            ->expects(self::never())
            ->method('isTokenValid')
        ;

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Missing CSRF token.');

        $protection = new LearningPathRuntimeWriteProtection($requestStack, $csrfTokenManager);
        $protection->assertWriteAllowed('');
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
        $this->expectExceptionMessage('Invalid CSRF token.');

        $protection = new LearningPathRuntimeWriteProtection($requestStack, $csrfTokenManager);
        $protection->assertWriteAllowed('invalid-session-csrf');
    }
}
