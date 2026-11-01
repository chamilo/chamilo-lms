<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Controller\HotPotatoesController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\Routing\Attribute\Route;

final class HotPotatoesAttemptRoutesTest extends TestCase
{
    /**
     * @dataProvider routeMethods
     */
    public function testAttemptRoutesUseProperHttpMethods(string $method, array $verbs): void
    {
        $attributes = (new ReflectionMethod(HotPotatoesController::class, $method))->getAttributes(Route::class);
        self::assertCount(1, $attributes);
        self::assertSame($verbs, $attributes[0]->newInstance()->getMethods());
    }

    public static function routeMethods(): array
    {
        return [
            'start' => ['startAttempt', ['POST']],
            'list' => ['listAttempts', ['GET']],
            'complete' => ['completeAttempt', ['POST']],
        ];
    }
}
