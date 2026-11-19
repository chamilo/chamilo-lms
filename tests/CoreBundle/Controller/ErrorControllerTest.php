<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Controller\ErrorController;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Twig\Environment;

/**
 * In prod, a JSON caller that is denied must receive the error as JSON with its
 * Content-Type, so a strict client can parse it instead of seeing an HTML page.
 */
final class ErrorControllerTest extends KernelTestCase
{
    public function testJsonErrorKeepsItsContentTypeInProd(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $controller = new ErrorController(
            $container->get(Environment::class),
            'prod',
            $container->get('error_renderer'),
        );

        $request = Request::create('/attendance/validate-self', 'POST');
        $request->headers->set('Accept', 'application/json');
        // The serializer renderer reads the format from the current request.
        $container->get('request_stack')->push($request);

        $response = $controller->show($request, new AccessDeniedHttpException('Attendance date not available in this course'));

        self::assertSame(403, $response->getStatusCode());
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
