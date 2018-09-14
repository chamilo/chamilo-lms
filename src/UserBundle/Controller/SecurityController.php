<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Controller;

use Chamilo\UserBundle\Form\LoginType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SecurityController.
 *
 * @package Chamilo\UserBundle\Controller
 */
class SecurityController extends Controller
{
    /**
     *
     * @Route("/login", name="login")
     *
     * @return Response
     */
    public function loginAction()
    {
        $helper = $this->get('security.authentication_utils');
        $error = $helper->getLastAuthenticationError();

        $form = $this->createForm(LoginType::class, ['_username' => $helper->getLastUsername()]);

        return $this->render('@ChamiloUser/login.html.twig', [
            'last_username' => $helper->getLastUsername(),
            'error' => $error,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Used in the home page
     * @return Response
     */
    public function loginSideBarAction()
    {
        $helper = $this->get('security.authentication_utils');

        $form = $this->createForm(LoginType::class, ['_username' => $helper->getLastUsername()]);

        return $this->render('@ChamiloUser/login_sidebar.html.twig', [
            'last_username' => $helper->getLastUsername(),
            'error' => '', // error will be printed in the /login page
            'form' => $form->createView(),
        ]);
    }
}
