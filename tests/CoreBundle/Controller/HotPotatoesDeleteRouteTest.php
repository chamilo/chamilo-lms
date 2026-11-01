<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Controller\HotPotatoesController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\Routing\Attribute\Route;

final class HotPotatoesDeleteRouteTest extends TestCase
{
    public function testDeleteRequiresAnHttpDeleteRequest(): void
    {
        $attributes = (new ReflectionMethod(HotPotatoesController::class, 'delete'))->getAttributes(Route::class);
        self::assertCount(1, $attributes);
        $route = $attributes[0]->newInstance();
        self::assertSame(['DELETE'], $route->getMethods());
        self::assertSame('/api/hotpotatoes/archives/{id}', $route->getPath());
    }
}
