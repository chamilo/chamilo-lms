<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    private UrlGeneratorInterface $urlGenerator;
    private RequestStack $requestStack;

    public function __construct(UrlGeneratorInterface $urlGenerator, RequestStack $requestStack)
    {
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
    }

    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
    {
        /*error_log('start');
        $message = $authException->getMessage();
        if (null !== $authException->getPrevious()) {
            $message = $authException->getPrevious()->getMessage();
        }*/

        //$session = $this->requestStack->getSession();
        //$session->getFlashBag()->add('warning', $message);

        /*$data = [
            // you might translate this message
            'message' => 'Authentication Required',
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);*/

        return new RedirectResponse($this->urlGenerator->generate('login'));
    }
}
