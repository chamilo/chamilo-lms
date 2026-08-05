<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class RedirectionLoginEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PluginHelper $pluginHelper,
        private TokenStorageInterface $tokenStorage,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -255],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->pluginHelper->isPluginEnabled('Redirection')) {
            return;
        }

        $request = $event->getRequest();

        if ('/login_json' !== $request->getPathInfo() || 'POST' !== $request->getMethod()) {
            return;
        }

        $response = $event->getResponse();

        if (!$response instanceof JsonResponse) {
            return;
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return;
        }

        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user instanceof User || null === $user->getId()) {
            return;
        }

        require_once dirname(__DIR__, 2).'/RedirectionPlugin.php';

        $targetUrl = RedirectionPlugin::getRedirectUrlForUser((int) $user->getId());

        if (!is_string($targetUrl) || '' === $targetUrl || !RedirectionPlugin::isAllowedRedirectUrl($targetUrl)) {
            return;
        }

        $content = $response->getContent();

        if (!is_string($content) || '' === $content) {
            return;
        }

        $payload = json_decode($content, true);

        if (!is_array($payload)) {
            return;
        }

        /*
         * Do not bypass mandatory login workflows.
         * These responses must keep their own redirect target.
         */
        if (
            !empty($payload['error'])
            || !empty($payload['requires2FA'])
            || !empty($payload['load_terms'])
            || !empty($payload['force_password_change'])
        ) {
            return;
        }

        $payload['redirect'] = $targetUrl;

        $response->setData($payload);
    }
}
