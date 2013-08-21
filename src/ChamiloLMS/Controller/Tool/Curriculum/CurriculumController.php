<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Tool\Curriculum;

use ChamiloLMS\Controller\CommonController;
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
class CurriculumController extends CommonController
{
    /**
     *
     * @Route("/")
     * @Method({"GET"})
     */
    public function indexAction($courseCode)
    {
        // Redirecting to curriculum user
        return $this->redirect($this->generateUrl('curriculum_user.controller:indexAction', array('courseCode' => $courseCode)));

        /*
        $template = $this->get('template');
        $response = $template->render_template($this->getTemplatePath().'index.tpl');
        return new Response($response, 200, array());
        */
    }

    protected function getTemplatePath()
    {
        return 'tool/curriculum/';
    }
}
