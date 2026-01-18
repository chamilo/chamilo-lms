<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use const PHP_SESSION_ACTIVE;

final class AiHelper
{
    public const SESSION_CURRENT_EXERCISES = 'current_exercises';
    public const SESSION_IS_IN_A_TEST = 'is_in_a_test';
    public const SESSION_IN_TEST_LP = 'ai_in_test_lp';

    public function __construct(
        private readonly RequestStack $requestStack
    ) {}

    private function getSession(): ?SessionInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request || !$request->hasSession()) {
            return null;
        }

        return $request->getSession();
    }

    /**
     * Returns true when the user is in any test context:
     * - at least one exercise attempt marked as active (current_exercises)
     * - or inside a quiz item in LP (ai_in_test_lp)
     */
    public function isUserInTest(): bool
    {
        $map = $this->readCurrentExercisesMap();
        if (!empty($map)) {
            return true;
        }

        return $this->readLpInTestFlag();
    }

    /**
     * Marks an exercise attempt as active.
     */
    public function markUserInTest(int $exeId): void
    {
        if ($exeId <= 0) {
            return;
        }

        $map = $this->readCurrentExercisesMap();
        $map[$exeId] = true;

        $this->writeCurrentExercisesMap($map);
        $this->syncAggregateInTestFlag($map, null);
    }

    /**
     * Unmarks a specific attempt. If no attempts remain active, removes the map.
     */
    public function clearUserInTest(int $exeId): void
    {
        if ($exeId <= 0) {
            return;
        }

        $map = $this->readCurrentExercisesMap();

        if (isset($map[$exeId])) {
            unset($map[$exeId]);
        }

        if (empty($map)) {
            $this->removeCurrentExercisesMap();
            $this->syncAggregateInTestFlag([], null);

            return;
        }

        $this->writeCurrentExercisesMap($map);
        $this->syncAggregateInTestFlag($map, null);
    }

    /**
     * LP hook: sets/unsets "in test" when the current LP item is a quiz.
     * This does NOT require an exercise exe_id.
     */
    public function setUserInTestFromLearningPath(bool $inTest): void
    {
        $this->writeLpInTestFlag($inTest);

        // Keep the aggregate flag in sync with both sources.
        $map = $this->readCurrentExercisesMap();
        $this->syncAggregateInTestFlag($map, $inTest);
    }

    /**
     * Clears all attempts and LP test flag (e.g. on logout).
     */
    public function clearAllTests(): void
    {
        $this->removeCurrentExercisesMap();
        $this->writeLpInTestFlag(false);
        $this->removeAggregateInTestFlag();
    }

    // ------------------------------------------------------------------
    // Internal session read/write (Symfony session first, then $_SESSION)
    // ------------------------------------------------------------------

    private function readCurrentExercisesMap(): array
    {
        $session = $this->getSession();
        if (null !== $session) {
            $value = $session->get(self::SESSION_CURRENT_EXERCISES, []);

            return \is_array($value) ? $value : [];
        }

        if (PHP_SESSION_ACTIVE !== session_status()) {
            @session_start();
        }

        $value = $_SESSION[self::SESSION_CURRENT_EXERCISES] ?? [];

        return \is_array($value) ? $value : [];
    }

    private function writeCurrentExercisesMap(array $map): void
    {
        $session = $this->getSession();
        if (null !== $session) {
            $session->set(self::SESSION_CURRENT_EXERCISES, $map);

            return;
        }

        if (PHP_SESSION_ACTIVE !== session_status()) {
            @session_start();
        }

        $_SESSION[self::SESSION_CURRENT_EXERCISES] = $map;
    }

    private function removeCurrentExercisesMap(): void
    {
        $session = $this->getSession();
        if (null !== $session) {
            $session->remove(self::SESSION_CURRENT_EXERCISES);

            return;
        }

        if (PHP_SESSION_ACTIVE !== session_status()) {
            @session_start();
        }

        unset($_SESSION[self::SESSION_CURRENT_EXERCISES]);
    }

    private function readLpInTestFlag(): bool
    {
        $session = $this->getSession();
        if (null !== $session) {
            return (bool) $session->get(self::SESSION_IN_TEST_LP, false);
        }

        if (PHP_SESSION_ACTIVE !== session_status()) {
            @session_start();
        }

        return !empty($_SESSION[self::SESSION_IN_TEST_LP]);
    }

    private function writeLpInTestFlag(bool $inTest): void
    {
        $session = $this->getSession();
        if (null !== $session) {
            if ($inTest) {
                $session->set(self::SESSION_IN_TEST_LP, true);
            } else {
                $session->remove(self::SESSION_IN_TEST_LP);
            }

            return;
        }

        if (PHP_SESSION_ACTIVE !== session_status()) {
            @session_start();
        }

        if ($inTest) {
            $_SESSION[self::SESSION_IN_TEST_LP] = true;
        } else {
            unset($_SESSION[self::SESSION_IN_TEST_LP]);
        }
    }

    /**
     * Writes the legacy-like aggregate flag "is_in_a_test" as:
     * (current_exercises not empty) OR (lp flag true).
     */
    private function syncAggregateInTestFlag(array $map, ?bool $lpFlagOverride): void
    {
        $lpFlag = $lpFlagOverride ?? $this->readLpInTestFlag();
        $inTest = (!empty($map)) || $lpFlag;

        $session = $this->getSession();
        if (null !== $session) {
            if ($inTest) {
                $session->set(self::SESSION_IS_IN_A_TEST, 1);
            } else {
                $session->remove(self::SESSION_IS_IN_A_TEST);
            }

            return;
        }

        if (PHP_SESSION_ACTIVE !== session_status()) {
            @session_start();
        }

        if ($inTest) {
            $_SESSION[self::SESSION_IS_IN_A_TEST] = 1;
        } else {
            unset($_SESSION[self::SESSION_IS_IN_A_TEST]);
        }
    }

    private function removeAggregateInTestFlag(): void
    {
        $session = $this->getSession();
        if (null !== $session) {
            $session->remove(self::SESSION_IS_IN_A_TEST);

            return;
        }

        if (PHP_SESSION_ACTIVE !== session_status()) {
            @session_start();
        }

        unset($_SESSION[self::SESSION_IS_IN_A_TEST]);
    }
}
