<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Security\Upload\AntivirusGuard;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class AntivirusUploadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AntivirusGuard $guard
    ) {}

    public static function getSubscribedEvents(): array
    {
        // After CSRF, authentication and contextual role resolution; before deserialization.
        return [KernelEvents::REQUEST => ['onKernelRequest', 3]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || 0 === $event->getRequest()->files->count() || !$this->guard->isEnabled()) {
            return;
        }
        $files = $event->getRequest()->files->all();
        array_walk_recursive($files, function (mixed $file): void {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $this->guard->assertPath($file->getPathname(), $file->getClientOriginalName(), 'http');
            }
        });
    }
}
