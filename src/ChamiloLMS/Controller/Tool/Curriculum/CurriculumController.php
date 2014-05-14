<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Tool\Curriculum;

use ChamiloLMS\Controller\BaseController;
use Silex\Application;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use Entity;
use ChamiloLMS\Form\CurriculumItemType;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class CurriculumController
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
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
