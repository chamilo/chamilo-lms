<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\Controller;

use Chamilo\ThemeBundle\Form\FormDemoModelType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class DefaultController.
 */
class DefaultController extends AbstractController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardAction()
    {
        return $this->render('ChamiloThemeBundle:Default:index.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function uiGeneralAction()
    {
        return $this->render('ChamiloThemeBundle:Default:index.html.twig');
    }

    public function uiIconsAction()
    {
        return $this->render('ChamiloThemeBundle:Default:index.html.twig');
    }

    public function formAction()
    {
        $form = $this->createForm(new FormDemoModelType());

        return $this->render('ChamiloThemeBundle:Default:form.html.twig', [
                'form' => $form->createView(),
            ]);
    }
}
