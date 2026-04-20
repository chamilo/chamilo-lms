<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Security\LoginCaptchaManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LoginCaptchaRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoginCaptchaManager $loginCaptchaManager,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (
            'POST' !== $request->getMethod()
            || !\in_array($request->getPathInfo(), ['/login_json', '/login/ldap/check'], true)
        ) {
            return;
        }

        if (!$this->loginCaptchaManager->isEnabled()) {
            return;
        }

        $data = json_decode((string) $request->getContent(), true);
        if (!\is_array($data)) {
            $data = [];
        }

        $username = trim((string) ($data['username'] ?? ''));
        $captchaCode = trim((string) ($data['captcha_code'] ?? ''));

        if ('' === $username) {
            return;
        }

        if ($this->loginCaptchaManager->isBlocked($username)) {
            $remaining = $this->loginCaptchaManager->getRemainingBlockedSeconds($username);

            $event->setResponse(new JsonResponse([
                'error' => 'Captcha is temporarily blocked for this account.',
                'captchaRequired' => true,
                'captchaBlocked' => true,
                'captchaBlockedSeconds' => $remaining,
            ], Response::HTTP_TOO_MANY_REQUESTS));

            return;
        }

        if (!$this->loginCaptchaManager->validateCaptcha($request, $captchaCode)) {
            $this->loginCaptchaManager->registerCaptchaMistake($username);

            $event->setResponse(new JsonResponse([
                'error' => 'Invalid captcha code.',
                'captchaRequired' => true,
            ], Response::HTTP_UNAUTHORIZED));
        }
    }
}
