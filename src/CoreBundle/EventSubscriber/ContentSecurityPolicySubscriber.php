<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Security\ContentSecurityPolicy;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ContentSecurityPolicySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ContentSecurityPolicy $policy
    ) {}

    public static function getSubscribedEvents(): array
    {
        // Finalize CSP and nonce cache headers after session and cookie listeners.
        return [KernelEvents::RESPONSE => ['onResponse', -4096]];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if ($event->isMainRequest()) {
            $this->policy->apply($event->getRequest(), $event->getResponse());
        }
    }
}
