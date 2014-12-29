<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Controller;

use FOS\UserBundle\Controller\SecurityController;
use Sonata\UserBundle\Model\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class SecurityFOSUser1Controller
 *
 * @package Sonata\UserBundle\Controller
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class SecurityFOSUser1Controller extends SecurityController
{
    public function loginAction()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();

        if ($user instanceof UserInterface) {
            $this->container->get('session')->getFlashBag()->set('sonata_user_error', 'sonata_user_already_authenticated');
            $url = $this->container->get('router')->generate('home');

            return new RedirectResponse($url);
        }

        return parent::loginAction();
    }
}
