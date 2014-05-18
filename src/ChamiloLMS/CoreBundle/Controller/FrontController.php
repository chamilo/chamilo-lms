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

    public function showCourseSessionBlockAction()
    {

        return new Response('showCourseSessionBlock');
    }

    public function showCourseBlockAction()
    {
        return new Response('showCourseSessionBlock');
    }

    public function showTeacherBlockAction()
    {
        return new Response('showCourseSessionBlock');
    }

    public function showSessionBlockAction()
    {
        return new Response('showCourseSessionBlock');
    }

    public function showNoticeBlockAction()
    {
        return new Response('showCourseSessionBlock');
    }
    public function showHelpBlockAction()
    {
        return new Response('showCourseSessionBlock');
    }

    public function showNavigationBlockAction()
    {
        return new Response('showCourseSessionBlock');
    }
    public function showSkillsBlockAction()
    {
        return new Response('showCourseSessionBlock');
    }
}
