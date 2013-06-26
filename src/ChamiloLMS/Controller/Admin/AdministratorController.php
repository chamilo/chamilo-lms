<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Admin;

use ChamiloLMS\Controller\BaseController;
use Silex\Application;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Entity;
use ChamiloLMS\Form\QuestionScoreType;

/**
 * Class Administrator
 * @todo @route and @method function don't work yet
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class AdministratorController extends BaseController
{
    /**
     *
     * @param Application $app
     * @Route("/")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        $template = $this->get('template');
        $response = $template->render_template('admin/administrator/index.tpl');
        return new Response($response, 200, array());
    }

    function getRepository()
    {

    }

    /**
     * This method should return a new entity instance to be used for the "create" action.
     *
     * @abstract
     * @return Object
     */
    function getNewEntity()
    {

    }
}
