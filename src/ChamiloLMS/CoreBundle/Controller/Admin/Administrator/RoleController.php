<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Controller\Admin\Administrator;

use ChamiloLMS\CoreBundle\Controller\CrudController;
use ChamiloLMS\CoreBundle\Entity;

/**
 * Class RoleController
 * @package ChamiloLMS\CoreBundle\Controller\Admin\Administrator
 * @author Julio Montoya <gugli100@gmail.com>
 */
class RoleController extends CrudController
{
    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return 'ChamiloLMS\CoreBundle\Entity\Role';
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'ChamiloLMS\CoreBundle\Form\RoleType';
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
