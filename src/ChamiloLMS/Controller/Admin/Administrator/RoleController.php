<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Admin\Administrator;

use ChamiloLMS\Controller\CommonController;
use Silex\Application;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use Entity;
use ChamiloLMS\Form\RoleType;

/**
 * Class RoleController
 * @todo @route and @method function don't work yet
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class RoleController extends CommonController
{
    public function indexAction()
    {
        return parent::indexAction();
    }

    public function readAction($id)
    {
        return parent::readAction($id);
    }

    public function addAction()
    {
        return parent::addAction();
    }

    public function editAction($id)
    {
        return parent::editAction($id);
    }

    public function deleteAction($id)
    {
        return parent::deleteAction($id);
    }

    /**
     * Return an array with the string that are going to be generating by twig.
     * @return array
     */
    protected function generateLinks()
    {
        return array(
            'create_link' => 'admin_administrator_roles_add',
            'read_link' => 'admin_administrator_roles_read',
            'update_link' => 'admin_administrator_roles_edit',
            'delete_link' => 'admin_administrator_roles_delete',
            'list_link' => 'admin_administrator_roles'
        );
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
        return $this->get('orm.em')->getRepository('Entity\Role');
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewEntity()
    {
        return new Entity\Role();
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return new RoleType();
    }
}
