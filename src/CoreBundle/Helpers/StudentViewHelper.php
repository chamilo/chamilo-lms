<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\EventListener\LegacyListener;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Reads the student view state instead of the isStudentView query parameter.
 *
 * The parameter is interpreted once per request by LegacyListener, which honours
 * course.student_view_enabled and stores the outcome in the session. Reading the
 * query parameter again bypasses that setting.
 *
 * @see LegacyListener::__invoke()
 */
class StudentViewHelper
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly SettingsManager $settingsManager,
    ) {}

    /**
     * Whether the current user browses the platform as a student.
     */
    public function isActive(): bool
    {
        $session = $this->getSessionHandler();

        return null !== $session && 'studentview' === $session->get('studentview');
    }

    private function getSessionHandler(): ?SessionInterface
    {
        if ('true' !== $this->settingsManager->getSetting('course.student_view_enabled')) {
            return null;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$request->hasSession()) {
            return null;
        }

        return $request->getSession();
    }
}
