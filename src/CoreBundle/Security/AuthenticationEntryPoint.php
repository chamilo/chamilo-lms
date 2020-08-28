<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    private $urlGenerator;
    private $session;

    public function __construct(UrlGeneratorInterface $urlGenerator, SessionInterface $session)
    {
        $this->urlGenerator = $urlGenerator;
        $this->session = $session;
    }

    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
    {
        $message = $authException->getMessage();
        if ($authException->getPrevious()) {
            $message = $authException->getPrevious()->getMessage();
        }

        $this->session->getFlashBag()->add('warning', $message);

        return new RedirectResponse($this->urlGenerator->generate('login'));
    }
}
