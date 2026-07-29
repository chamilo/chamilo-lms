<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Service\Mcp\McpAccessPolicy;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class McpServerAccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private McpAccessPolicy $mcpAccessPolicy,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 2048]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $path = rtrim($event->getRequest()->getPathInfo(), '/');
        if (!\in_array($path, ['/mcp', '/.well-known/oauth-protected-resource/mcp'], true)) {
            return;
        }

        if (!$this->mcpAccessPolicy->isEnabled()) {
            throw new NotFoundHttpException();
        }
    }
}
