<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CourseBundle\Controller\Curriculum;

use ChamiloLMS\CoreBundle\Controller\BaseController;
use Silex\Application;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use Entity;
use ChamiloLMS\CoreBundle\Form\CurriculumItemType;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class CurriculumController
 * @package ChamiloLMS\CoreBundle\Controller
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
    public function indexAction()
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
