<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Knp\Menu\FactoryInterface as MenuFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Each entity controller must extends this class.
 *
 * @abstract
 */
abstract class BaseController extends Controller
{
    /**
     * @return NotFoundHttpException
     */
    public function abort()
    {
        return new NotFoundHttpException();
    }

    /**
     * Translator shortcut.
     *
     * @param string $variable
     *
     * @return string
     */
    public function trans($variable)
    {
        return $this->container->get('translator')->trans($variable);
    }

    /**
     * @return MenuFactoryInterface
     */
    public function getMenuFactory()
    {
        return $this->container->get('knp_menu.factory');
    }

    /**
     * @return Course
     */
    public function getCourse()
    {
        return $this->getRequest()->getSession()->get('course');
    }
}
