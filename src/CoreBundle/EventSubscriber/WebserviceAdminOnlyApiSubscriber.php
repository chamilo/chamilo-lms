<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class WebserviceAdminOnlyApiSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SettingsManager $settingsManager
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 0],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        /*
         * Only API-key webservice calls are affected.
         * Normal Vue/JWT/session API Platform calls must keep their current behavior.
         */
        if (true !== $request->attributes->get('_chamilo_webservice_api_key')) {
            return;
        }

        $operation = $request->attributes->get('_api_operation');

        if (!$operation instanceof Operation) {
            return;
        }

        if (!$this->isAdminOnlyOperation($operation)) {
            return;
        }

        if ($this->isSettingEnabled($this->settingsManager->getSetting('webservice.webservice_enable_adminonly_api', true))) {
            return;
        }

        throw new AccessDeniedHttpException('Admin-only API access by API key is disabled.');
    }

    private function isAdminOnlyOperation(Operation $operation): bool
    {
        $securityExpressions = [
            (string) $operation->getSecurity(),
            (string) $operation->getSecurityPostDenormalize(),
        ];

        foreach ($securityExpressions as $expression) {
            if (
                str_contains($expression, 'ROLE_ADMIN')
                || str_contains($expression, 'ROLE_GLOBAL_ADMIN')
            ) {
                return true;
            }
        }

        return false;
    }

    private function isSettingEnabled(mixed $value): bool
    {
        if (true === $value || 1 === $value) {
            return true;
        }

        $normalized = strtolower(trim((string) $value));

        return 'true' === $normalized || '1' === $normalized;
    }
}
