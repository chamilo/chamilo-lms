<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\User;

use ChamiloLMS\Controller\CommonController;
use Silex\Application;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;


/**
 * Class ProfileController
 * @package ChamiloLMS\Controller
 */
class ProfileController extends CommonController
{
    /**
     * @Route("/{username}")
     * @Method({"GET"})
     */
    public function indexAction($username)
    {
        $userId = \UserManager::get_user_id_from_username($username);
        $userInfo = api_get_user_info($userId);

        $this->getTemplate()->assign('user', $userInfo);
        $this->getTemplate()->assign('form_send_message', \MessageManager::generate_message_form('send_message'));
        $this->getTemplate()->assign('form_send_invitation', \MessageManager::generate_invitation_form('send_invitation'));

        $response = $this->getTemplate()->renderTemplate($this->getTemplatePath().'profile.tpl');
        return new Response($response, 200, array());
    }

    /**
     * My files
     * @Route("/{username}/files")
     * @Method({"GET"})
     */
    public function fileAction($username)
    {
        if ($this->getUser()->getUsername() != $username) {
            return $this->abort(401);
        }

        $userId = \UserManager::get_user_id_from_username($username);
        $userInfo = api_get_user_info($userId);

        $this->getTemplate()->assign('driver_list', 'PersonalDriver,DropBoxDriver');

        $editor = $this->getTemplate()->renderTemplate(
            $this->getHtmlEditor()->getEditorStandAloneTemplate()
        );

        $this->getTemplate()->assign('user', $userInfo);
        $this->getTemplate()->assign('editor', $editor);

        $response = $this->getTemplate()->renderTemplate(
            $this->getTemplatePath().'files.tpl'
        );
        return new Response($response, 200, array());
    }

    /**
     * Gets that rm.wav sound
     * @Route("/{username}/sounds/{file}")
     * @Method({"GET"})
     */
    public function getSoundAction()
    {
        $file = api_get_path(LIBRARY_PATH).'elfinder/rm.wav';
        return $this->app->sendFile($file);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplatePath()
    {
        return 'user/';
    }
}
