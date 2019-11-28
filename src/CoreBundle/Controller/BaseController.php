<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Knp\Menu\FactoryInterface as MenuFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Each entity controller must extends this class.
 *
 * @abstract
 */
abstract class BaseController extends AbstractController
{
    /**
     * @param string $message
     *
     * @return NotFoundHttpException
     */
    public function abort($message = '')
    {
        return new NotFoundHttpException($message);
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
     * Gets the current Chamilo course based in the "_real_cid" session variable.
     *
     * @return Course
     */
    public function getCourse()
    {
        $request = $this->getRequest();
        if ($request) {
            $courseId = $request->getSession()->get('cid', 0);
        }

        if (empty($courseId)) {
            return null;
        }

        return $this->getDoctrine()->getManager()->find('ChamiloCoreBundle:Course', $courseId);
    }

    /**
     * Gets the current Chamilo session based in the "sid" $_SESSION variable.
     *
     * @return Session|null
     */
    public function getCourseSession()
    {
        $request = $this->getRequest();

        if ($request) {
            $sessionId = $request->getSession()->get('sid', 0);
        }

        if (empty($sessionId)) {
            return null;
        }

        return $this->getDoctrine()->getManager()->find('ChamiloCoreBundle:Session', $sessionId);
    }

    public function getCourseUrlQuery(): string
    {
        $url = '';
        $course = $this->getCourse();
        if ($course) {
            $url = 'cid='.$course->getId();
        }
        $session = $this->getCourseSession();

        if ($session) {
            $url .= '&sid='.$session->getId();
        } else {
            $url .= '&sid=0';
        }

        return $url;
    }

    public function getCourseParams(): array
    {
        $routeParams = ['cid' => $this->getCourse()->getId()];
        $session = $this->getCourseSession();
        $sessionId = $session ? $session->getId() : 0;
        $routeParams['sid'] = $sessionId;

        return $routeParams;
    }

    /**
     * @return Request|null
     */
    public function getRequest()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        return $request;
    }
}
