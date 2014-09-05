<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\User;

use Symfony\Component\HttpFoundation\Response;
use Chamilo\CoreBundle\Controller\BaseController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Class UserController
 * @package Chamilo\CoreBundle\Controller
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
     * @Route("/{username}")
     * @Method({"GET"})
     * @Template("ChamiloCoreBundle:User:profile.html.twig")
     */
    public function profileAction($username)
    {
        $userId = \UserManager::get_user_id_from_username($username);
        $userInfo = api_get_user_info($userId);

        return array(
            'user' =>  $userInfo,
            'form_send_message' => \MessageManager::generate_message_form('send_message'),
            'form_send_invitation' => \MessageManager::generate_invitation_form('send_invitation')
        );
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
    public function getTemplatePath()
    {
        return 'user/';
    }
}
