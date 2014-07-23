<?php

namespace ChamiloLMS\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;

class FrontController extends Controller
{
    /**
     * @Route("/menu")
     * @Method({"GET"})
     */
    public function showMenuAction()
    {
        return new Response('menu');
    }

    /**
     * @Route("/login")
     * @Method({"GET"})
     */
    public function showLoginAction()
    {
        return $this->render(
            'ChamiloLMSCoreBundle:Security:only_login.html.twig',
            array('error' => null)
        );
    }

}
