<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Form\LoginType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Class SecurityController.
 */
class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@ChamiloCore/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            //'form' => $form->createView(),
        ]);
    }

    /**
     * Used in the home page.
     *
     * @return Response
     */
    public function loginSideBarAction()
    {
        $helper = $this->get('security.authentication_utils');

        $form = $this->createForm(LoginType::class, ['_username' => $helper->getLastUsername()]);

        return $this->render('@ChamiloCore/login_sidebar.html.twig', [
            'last_username' => $helper->getLastUsername(),
            'error' => '', // error will be printed in the /login page
            'form' => $form->createView(),
        ]);
    }
}
