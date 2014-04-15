<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\App\SessionPath;

use Silex\Application;
use ChamiloLMS\Controller\CommonController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use ChamiloLMS\Entity\SessionPath;
use ChamiloLMS\Form\SessionTreeType;

/**
 * @package ChamiloLMS.Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class SessionTreeController extends CommonController
{
    /**
     * @Route("/")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        return $this->listingAction();
    }

    /**
     * @Route("/add_item")
     * @Method({"GET"})
     */
    public function addTreeItemAction()
    {
        return $this->addAction();
    }

    protected function getControllerAlias()
    {
        return 'session_tree.controller';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplatePath()
    {
        return 'app/session_path/session_tree/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRepository()
    {
        return $this->get('orm.em')->getRepository('ChamiloLMS\Entity\SessionTree');
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewEntity()
    {
        return new SessionTree();
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return new SessionTreeType();
    }

}
