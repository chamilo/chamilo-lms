<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller;

/**
 * Interface CrudControllerInterface
 * @package ChamiloLMS\Controller
 */
interface CrudControllerInterface
{
    /**
     * Returns the entity class example: 'ChamiloLMS\Entity\Role'
     * @return string
     */
    public function getClass();

    /**
     * Returns the form type name example: 'ChamiloLMS\Form\RoleType'
     * @return string
     */
    public function getType();

    /**
     * Returns the controller alias loaded in the routes ex : 'role.controller'
     * @return string
     */
    public function getControllerAlias();

    /**
     * Returns the path of the templates located in
     * src/ChamiloLMS/Resources/views/default
     * example : admin/administrator/role/
     * @return string
     */
    public function getTemplatePath();
}
