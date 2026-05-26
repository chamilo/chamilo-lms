<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This subscriber unlocks the session after an API call to reduce the
 * likeliness of a session lock that would slow down or block other API calls.
 * See GH#6858.
 */
class ApiSessionUnlockSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            // We listen on KernelEvents::REQUEST
            // Priority 4 is usually after Security but before API Platform
            // starts its logic
            KernelEvents::REQUEST => [['unlockSession', 4]],
        ];
    }

    public function unlockSession(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Target only API calls
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        // Target only GET requests (safe to read-only)
        if ($request->isMethod('GET')) {
            if ($request->hasSession() && $request->getSession()->isStarted()) {
                // This writes and closes the session file handle
                $request->getSession()->save();
            }
        }
    }
}
