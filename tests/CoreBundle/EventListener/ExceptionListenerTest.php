<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\EventListener\ExceptionListener;
use Chamilo\CoreBundle\Helpers\UserHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * A denial on a page opened in the browser is redirected with an explanation, but an XHR
 * caller follows that redirect silently and receives a 200 with HTML, which it cannot tell
 * apart from a success. JSON callers must keep the 403 status.
 */
final class ExceptionListenerTest extends TestCase
{
    public function testDenialOfAJsonRequestIsNotRedirected(): void
    {
        $request = Request::create('/attendance/validate-self', 'POST');
        $request->headers->set('Accept', 'application/json, text/plain, */*');
        $event = $this->makeEvent($request);

        $this->makeListener()($event);

        // No response: the error controller renders the 403 in the requested format.
        self::assertNull($event->getResponse());
    }

    public function testDenialOfAnXhrRequestIsNotRedirected(): void
    {
        $request = Request::create('/attendance/1/student-dates');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $event = $this->makeEvent($request);

        $this->makeListener()($event);

        self::assertNull($event->getResponse());
    }

    public function testDenialOfAPageIsStillRedirected(): void
    {
        $request = Request::create('/main/my_space/session.php');
        $request->headers->set('Accept', 'text/html,application/xhtml+xml');
        $event = $this->makeEvent($request);

        $this->makeListener()($event);

        self::assertInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    private function makeListener(): ExceptionListener
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(new User());

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('http://localhost/');

        return new ExceptionListener('prod', new UserHelper($security), $router);
    }

    private function makeEvent(Request $request): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new AccessDeniedHttpException('Denied')
        );
    }
}
