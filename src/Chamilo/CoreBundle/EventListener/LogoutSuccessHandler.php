<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class LogoutSuccessHandler
 */
class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    protected $router;
    protected $security;

    /**
     * @param Router $urlGenerator
     * @param SecurityContext $security
     */
    public function __construct($dbConnection, UrlGeneratorInterface $urlGenerator, SecurityContext $security)
    {
        $database  = new \Database($dbConnection, array());
        $this->router = $urlGenerator;
        $this->security = $security;
    }

    /**
     * @param Request $request
     * @return null|RedirectResponse
     */
    public function onLogoutSuccess(Request $request)
    {
        $session = $request->getSession();
        \ChamiloSession::setSession($session);

        // Chamilo logout
        //$userId = api_get_user_id();
        //\Online::logout($userId, false);

        $login = $this->router->generate('home');
        $response = new RedirectResponse($login);
        return $response;
    }
}
