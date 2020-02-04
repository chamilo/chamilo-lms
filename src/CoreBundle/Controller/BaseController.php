<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Block\BreadcrumbBlockService;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Knp\Menu\FactoryInterface as MenuFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Each entity controller must extends this class.
 *
 * @abstract
 */
abstract class BaseController extends AbstractController
{
    protected $translator;

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['translator'] = TranslatorInterface::class;
        $services['breadcrumb'] = BreadcrumbBlockService::class;

        return $services;
    }

    public function getBreadCrumb(): BreadcrumbBlockService
    {
        return $this->container->get('breadcrumb');
    }

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
        /** @var TranslatorInterface $translator */
        $translator = $this->container->get('translator');

        return $translator->trans($variable);
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

    public function hasCourse()
    {
        $request = $this->getRequest();
        if ($request) {
            $courseId = $request->getSession()->get('cid', 0);
            if (!empty($courseId)) {
                return true;
            }
        }

        return false;
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

    public function getGroup()
    {
        $request = $this->getRequest();

        if ($request) {
            $groupId = $request->getSession()->get('gid', 0);
        }

        if (empty($groupId)) {
            return null;
        }

        return $this->getDoctrine()->getManager()->find('ChamiloCourseBundle:CGroupInfo', $groupId);
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

        $group = $this->getGroup();
        if ($group) {
            $url .= '&gid='.$group->getIid();
        } else {
            $url .= '&gid=0';
        }

        return $url;
    }

    public function getCourseUrlQueryToArray(): array
    {
        $url = [];
        $course = $this->getCourse();
        $url['cid'] = 0;
        if ($course) {
            $url['cid'] = $course->getId();
        }
        $session = $this->getCourseSession();

        $url['sid'] = 0;
        if ($session) {
            $url['sid'] = $session->getId();
        }

        return $url;
    }

    public function getResourceParams(Request $request): array
    {
        $tool = $request->get('tool');
        $type = $request->get('type');
        $id = (int) $request->get('id');

        $courseId = null;
        $sessionId = null;

        if ($this->hasCourse()) {
            $courseId = $this->getCourse()->getId();
            $session = $this->getCourseSession();
            $sessionId = $session ? $session->getId() : 0;
        }

        return [
            'id' => $id,
            'tool' => $tool,
            'type' => $type,
            'cid' => $courseId,
            'sid' => $sessionId,
        ];
    }

    /**
     * @return Request|null
     */
    public function getRequest()
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }
}
