<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\EventListener;

use Chamilo\CoreBundle\EventListener\HTTPExceptionListener;
use Chamilo\CoreBundle\Exception\NotAllowedException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Throwable;

/**
 * Ensures HttpExceptions on XHR endpoints (API + legacy model.ajax.php) degrade to JSON
 * instead of being turned into an HTML redirect that a jqGrid cannot parse.
 */
final class HTTPExceptionListenerTest extends TestCase
{
    public function testModelAjaxForbiddenReturnsJson(): void
    {
        $event = $this->makeEvent('/main/inc/ajax/model.ajax.php?a=get_sessions', new NotAllowedException('Nope'));

        (new HTTPExceptionListener())($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(403, $response->getStatusCode());
        self::assertSame(['error' => 'Nope'], json_decode((string) $response->getContent(), true));
    }

    public function testApiForbiddenStillReturnsJson(): void
    {
        $event = $this->makeEvent('/api/sessions', new HttpException(403, 'No'));

        (new HTTPExceptionListener())($event);

        self::assertInstanceOf(JsonResponse::class, $event->getResponse());
    }

    public function testNonAjaxHtmlPageIsNotHandled(): void
    {
        // A regular legacy page must keep the HTML redirect handling of ExceptionListener.
        $event = $this->makeEvent('/main/my_space/session.php', new NotAllowedException('Nope'));

        (new HTTPExceptionListener())($event);

        self::assertNull($event->getResponse());
    }

    public function testNonHttpExceptionIsIgnoredOnModelAjax(): void
    {
        // Real bugs (non-HttpException) must keep propagating so the profiler/handler sees them.
        $event = $this->makeEvent('/main/inc/ajax/model.ajax.php', new RuntimeException('boom'));

        (new HTTPExceptionListener())($event);

        self::assertNull($event->getResponse());
    }

    private function makeEvent(string $uri, Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create($uri),
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
    }
}
