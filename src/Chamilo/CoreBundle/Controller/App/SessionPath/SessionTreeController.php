<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\App\SessionPath;

use Silex\Application;
use Chamilo\CoreBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Chamilo\CoreBundle\Entity\SessionPath;
use Chamilo\CoreBundle\Form\SessionTreeType;

/**
 * @package Chamilo.Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class SessionTreeController extends BaseController
{
    public function getClass()
    {
        return 'Chamilo\CoreBundle\Entity\SessionTree';
    }

    public function getType()
    {
        return 'Chamilo\CoreBundle\Form\SessionTreeType';
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
