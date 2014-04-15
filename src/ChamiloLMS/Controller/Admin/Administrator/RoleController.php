<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Admin\Administrator;

use ChamiloLMS\Controller\CommonController;
use Silex\Application;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use ChamiloLMS\Entity;
use ChamiloLMS\Form\RoleType;
use ChamiloLMS\Entity\Role;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class RoleController
 * @todo @route and @method function don't work yet
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class RoleController extends CommonController
{
    /**
     *
     * @Route("/")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        return parent::listingAction();
    }

    /**
    *
    * @Route("/{id}", requirements={"id" = "\d+"})
    * @Method({"GET"})
    */
    public function readAction($id)
    {
        return parent::readAction($id);
    }

    /**
    * @Route("/add")
    * @Method({"GET"})
    */
    public function addAction()
    {
        return parent::addAction();
    }

    /**
    *
    * @Route("/{id}/edit", requirements={"id" = "\d+"})
    * @Method({"GET"})
    */
    public function editAction($id)
    {
        return parent::editAction($id);
    }

    /**
    *
    * @Route("/{id}/delete", requirements={"id" = "\d+"})
    * @Method({"GET"})
    */
    public function deleteAction($id)
    {
        return parent::deleteAction($id);
    }

    protected function getControllerAlias()
    {
        return 'role.controller';
    }

    /**
    * {@inheritdoc}
    */
    protected function getTemplatePath()
    {
        return 'admin/administrator/role/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRepository()
    {
        return $this->get('orm.em')->getRepository('ChamiloLMS\Entity\Role');
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewEntity()
    {
        return new Role();
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return new RoleType();
    }
}
