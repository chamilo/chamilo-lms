<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Admin\Administrator;

use ChamiloLMS\Controller\CrudController;
use ChamiloLMS\Entity;

/**
 * Class RoleController
 * @package ChamiloLMS\Controller\Admin\Administrator
 * @author Julio Montoya <gugli100@gmail.com>
 */
class RoleController extends CrudController
{
    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return 'ChamiloLMS\Entity\Role';
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'ChamiloLMS\Form\RoleType';
    }

    /**
     * {@inheritdoc}
     */
    /*public function getControllerAlias()
    {
        return 'role.controller';
    }*/

    /**
     * {@inheritdoc}
     */
    /*public function getTemplatePath()
    {
        return 'admin/administrator/role/';
    }*/
}
