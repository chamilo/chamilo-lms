<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Security\LoginCaptchaManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SecurityCaptchaController extends AbstractController
{
    #[Route('/login/captcha/status', name: 'login_captcha_status', methods: ['GET'])]
    public function status(Request $request, LoginCaptchaManager $loginCaptchaManager): JsonResponse
    {
        $username = trim((string) $request->query->get('username', ''));

        return $this->json([
            'enabled' => $loginCaptchaManager->isEnabled(),
            'blocked' => '' !== $username && $loginCaptchaManager->isBlocked($username),
            'remainingSeconds' => '' !== $username
                ? $loginCaptchaManager->getRemainingBlockedSeconds($username)
                : 0,
            'imageUrl' => $loginCaptchaManager->isEnabled()
                ? '/login/captcha/image?ts='.time()
                : null,
        ]);
    }

    #[Route('/login/captcha/image', name: 'login_captcha_image', methods: ['GET'])]
    public function image(Request $request, LoginCaptchaManager $loginCaptchaManager): Response
    {
        $code = $loginCaptchaManager->generateCaptchaCode($request->getSession());
        $svg = $loginCaptchaManager->buildSvg($code);

        $response = new Response($svg);
        $response->headers->set('Content-Type', 'image/svg+xml; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $response;
    }
}
