<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Controller\Curriculum;

use Chamilo\CoreBundle\Controller\BaseController;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use Entity;
use Chamilo\CoreBundle\Form\CurriculumItemType;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CurriculumController
 * @package Chamilo\CoreBundle\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 * @Route("/curriculum")
 */
class CurriculumController extends BaseController
{
    /**
     *
     * @Route("/")
     * @Method({"GET"})
     */
    public function indexAction(Request $request)
    {
        // Redirecting to curriculum user
        // @todo Fix redirection
        return $this->redirect(
                $this->generateUrl(
                    'curriculum_user.controller:indexAction',
                    array('course' => $this->getCourse())
                )
                .'?'.$this->getRequest()->getQueryString()
        );

    }
}
