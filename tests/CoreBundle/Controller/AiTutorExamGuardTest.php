<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Controller\ChatController;
use Chamilo\CoreBundle\Helpers\AiHelper;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class AiTutorExamGuardTest extends TestCase
{
    public function testActiveExerciseBlocksTheTutorAndCompletionUnblocksIt(): void
    {
        [$controller, $helper] = $this->buildController();
        $guard = new ReflectionMethod(ChatController::class, 'isAiTutorBlockedInExam');

        self::assertFalse($guard->invoke($controller));

        $helper->markUserInTest(42);
        self::assertTrue($guard->invoke($controller));

        $helper->clearUserInTest(42);
        self::assertFalse($guard->invoke($controller));
    }

    public function testLearningPathExamBlocksTheTutor(): void
    {
        [$controller, $helper] = $this->buildController();
        $guard = new ReflectionMethod(ChatController::class, 'isAiTutorBlockedInExam');

        $helper->setUserInTestFromLearningPath(true);
        self::assertTrue($guard->invoke($controller));

        $helper->setUserInTestFromLearningPath(false);
        self::assertFalse($guard->invoke($controller));
    }

    public function testLegacySessionFlagStillBlocksTheTutor(): void
    {
        [$controller] = $this->buildController();
        $guard = new ReflectionMethod(ChatController::class, 'isAiTutorBlockedInExam');
        $hadSession = isset($_SESSION);
        $oldSession = $_SESSION ?? [];

        try {
            $_SESSION['is_in_a_test'] = 1;
            self::assertTrue($guard->invoke($controller));
        } finally {
            if ($hadSession) {
                $_SESSION = $oldSession;
            } else {
                unset($_SESSION);
            }
        }
    }

    /**
     * @return array{ChatController,AiHelper}
     */
    private function buildController(): array
    {
        $requestStack = new RequestStack();
        $request = Request::create('/account/chat/api/send', 'POST');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack->push($request);

        $helper = new AiHelper($requestStack);
        $controller = (new ReflectionClass(ChatController::class))->newInstanceWithoutConstructor();
        (new ReflectionClass(ChatController::class))->getProperty('aiHelper')->setValue($controller, $helper);

        return [$controller, $helper];
    }
}
