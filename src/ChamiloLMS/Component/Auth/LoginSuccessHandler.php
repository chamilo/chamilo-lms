<?php

namespace ChamiloLMS\Component\Auth;

use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class LoginSuccessHandler
 */
class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    protected $router;
    protected $security;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param SecurityContext $security
     */
    public function __construct(UrlGeneratorInterface $urlGenerator, SecurityContext $security)
    {
        $this->router = $urlGenerator;
        $this->security = $security;
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @return null|RedirectResponse|\Symfony\Component\Security\Http\Authentication\Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $response = null;
        //$session = $request->getSession();
        /* Possible values: index.php, user_portal.php, main/auth/courses.php */
        $pageAfterLogin = api_get_setting('page_after_login');

        if ($this->security->isGranted('ROLE_STUDENT') && !empty($pageAfterLogin)) {
            $url = null;
            switch($pageAfterLogin) {
                case 'index.php':
                    $url = $this->router->generate('index');
                    break;
                case 'user_portal.php':
                    $url = $this->router->generate('userportal');
                    break;
                case 'main/auth/courses.php':
                    $url = api_get_path(WEB_PUBLIC_PATH).$pageAfterLogin;
                    break;
            }
            if (!empty($url)) {
                $response = new RedirectResponse($url);
            }
        }

        // Redirect the user to where they were before the login process begun.
        if (empty($response)) {
            $refererUrl = $request->headers->get('referer');
            $response = new RedirectResponse($refererUrl);
        }

        return $response;
    }
}
