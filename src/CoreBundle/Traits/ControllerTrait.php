<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Traits;

use Chamilo\CoreBundle\Component\Utils\Glide;
use Chamilo\CoreBundle\Manager\SettingsManager;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\ResourceFactory;
use Chamilo\CourseBundle\Repository\CAnnouncementAttachmentRepository;
use Chamilo\CourseBundle\Repository\CAnnouncementRepository;
use Chamilo\CourseBundle\Repository\CAttendanceRepository;
use Chamilo\CourseBundle\Repository\CBlogRepository;
use Chamilo\CourseBundle\Repository\CCalendarEventAttachmentRepository;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CForumAttachmentRepository;
use Chamilo\CourseBundle\Repository\CForumForumRepository;
use Chamilo\CourseBundle\Repository\CLpCategoryRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\CourseBundle\Repository\CQuizQuestionCategoryRepository;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Knp\Menu\FactoryInterface as MenuFactoryInterface;
use Sylius\Bundle\SettingsBundle\Form\Factory\SettingsFormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

trait ControllerTrait
{
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['translator'] = TranslatorInterface::class;
        $services['resource_factory'] = ResourceFactory::class;
        $services['glide'] = Glide::class;
        $services['chamilo.settings.manager'] = SettingsManager::class;
        $services['chamilo_settings.form_factory.settings'] = SettingsFormFactory::class;

        /*
            The following classes are needed in order to load the resources files when using the /r/ path
            For example: http://my.chamilomaster.net/r/agenda/event_attachments/96/download?cid=1&sid=0&gid=0
        */

        $services[] = CAnnouncementRepository::class;
        $services[] = CAnnouncementAttachmentRepository::class;
        $services[] = CAttendanceRepository::class;
        $services[] = CBlogRepository::class;
        $services[] = CCalendarEventAttachmentRepository::class;
        $services[] = CDocumentRepository::class;
        $services[] = CQuizQuestionRepository::class;
        $services[] = CQuizQuestionCategoryRepository::class;
        $services[] = CForumForumRepository::class;
        $services[] = CForumAttachmentRepository::class;

        $services[] = CLpRepository::class;
        $services[] = CLpCategoryRepository::class;

        $services[] = IllustrationRepository::class;

        /*$services[] = CAttendanceRepository::class;
        $services[] = CDocumentRepository::class;
        $services[] = CDocumentRepository::class;
        $services[] = CDocumentRepository::class;
        $services[] = CDocumentRepository::class;
        $services[] = CDocumentRepository::class;
        $services[] = CDocumentRepository::class;
        $services[] = CDocumentRepository::class;*/

        return $services;
    }

    /**
     * @return Request|null
     */
    public function getRequest()
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }

    /*public function getBreadCrumb(): BreadcrumbBlockService
    {
        return $this->container->get('breadcrumb');
    }*/

    /**
     * @return MenuFactoryInterface
     */
    public function getMenuFactory()
    {
        return $this->container->get('knp_menu.factory');
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
     * @return Glide
     */
    public function getGlide()
    {
        return $this->container->get('glide');
    }

    /**
     * @return SettingsManager
     */
    protected function getSettingsManager()
    {
        return $this->get('chamilo.settings.manager');
    }

    protected function getSettingsFormFactory()
    {
        return $this->get('chamilo_settings.form_factory.settings');
    }
}
