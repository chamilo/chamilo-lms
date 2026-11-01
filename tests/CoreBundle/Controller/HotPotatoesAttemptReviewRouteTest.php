<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Controller\HotPotatoesController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\Routing\Attribute\Route;

final class HotPotatoesAttemptReviewRouteTest extends TestCase
{
    public function testTeacherReviewUsesStateChangingRoute(): void
    {
        $method = new ReflectionMethod(HotPotatoesController::class, 'reviewAttempt');
        $routes = $method->getAttributes(Route::class);
        self::assertCount(1, $routes);
        $route = $routes[0]->newInstance();
        self::assertSame(['POST'], $route->getMethods());
        self::assertSame('chamilo_hotpotatoes_attempt_review', $route->getName());
    }
}
