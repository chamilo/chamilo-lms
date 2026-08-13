<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security;

use Chamilo\CoreBundle\Helpers\RequestExpectsJsonHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $message = $authException?->getMessage() ?? 'Authentication required.';

        // XHR/JSON consumers get a status code they can act on. Redirecting them
        // would answer 200 with the login page, which they cannot tell apart from
        // a valid payload: that is why an expired session used to go unnoticed on
        // /check-session and /session/*. No flash is queued here either, since
        // nothing consumes it in this branch and it would surface later, out of
        // context, on the next HTML navigation.
        if (RequestExpectsJsonHelper::expectsJson($request)) {
            return new JsonResponse(['error' => $message], Response::HTTP_UNAUTHORIZED);
        }

        $session = $request->getSession();

        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('error', $message);
        }

        return new RedirectResponse($this->urlGenerator->generate('login'));
    }
}
