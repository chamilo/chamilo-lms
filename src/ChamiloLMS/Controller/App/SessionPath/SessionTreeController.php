<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\App\SessionPath;

use Silex\Application;
use ChamiloLMS\Controller\BaseController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use ChamiloLMS\Entity\SessionPath;
use ChamiloLMS\Form\SessionTreeType;

/**
 * @package ChamiloLMS.Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class SessionTreeController extends BaseController
{
    public function getClass()
    {
        return 'ChamiloLMS\Entity\SessionTree';
    }

    public function getType()
    {
        return 'ChamiloLMS\Form\SessionTreeType';
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplatePath()
    {
        return 'app/session_path/session_tree/';
    }

    public function getControllerAlias()
    {
        return 'session_tree.controller';
    }


    /**
     * @Route("/add_item")
     * @Method({"GET"})
     */
    public function addTreeItemAction()
    {
        return $this->addAction();
    }

}
