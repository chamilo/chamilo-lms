<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Controller\User;

use Silex\Application;

use Symfony\Component\HttpFoundation\Response;
use ChamiloLMS\CoreBundle\Controller\BaseController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;


/**
 * Class UserController
 * @package ChamiloLMS\CoreBundle\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class UserController extends BaseController
{
    /**
     * @Route("/me")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        $userInfo = api_get_user_info($this->getUser()->getUserId());

        $this->getTemplate()->assign('user', $userInfo);
        $response = $this->getTemplate()->renderTemplate($this->getTemplatePath().'me.tpl');
        return new Response($response, 200, array());
    }

    /**
     * @Route("/online")
     * @Method({"GET"})
     */
    public function onlineAction(Application $app)
    {
        $response = $app['template']->renderLayout('layout_1_col.tpl');
        return new Response($response, 200, array());
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplatePath()
    {
        return 'user/';
    }
}
