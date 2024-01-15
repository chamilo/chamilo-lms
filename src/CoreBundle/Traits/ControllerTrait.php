<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Traits;

use Chamilo\CoreBundle\Component\Utils\Glide;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\MessageAttachmentRepository;
use Chamilo\CoreBundle\Repository\ResourceFactory;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Repository\CAnnouncementAttachmentRepository;
use Chamilo\CourseBundle\Repository\CAnnouncementRepository;
use Chamilo\CourseBundle\Repository\CAttendanceRepository;
use Chamilo\CourseBundle\Repository\CCalendarEventAttachmentRepository;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CForumAttachmentRepository;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Chamilo\CourseBundle\Repository\CLpCategoryRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\CourseBundle\Repository\CQuizQuestionCategoryRepository;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationCommentRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationCorrectionRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Chamilo\CourseBundle\Repository\CToolRepository;
use Chamilo\LtiBundle\Repository\ExternalToolRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sylius\Bundle\SettingsBundle\Form\Factory\SettingsFormFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

trait ControllerTrait
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public static function getSubscribedServices(): array
    {
        $services = AbstractController::getSubscribedServices();
        $services['translator'] = TranslatorInterface::class;
        $services['glide'] = Glide::class;
        // $services['chamilo_settings.form_factory.settings'] = SettingsFormFactory::class;

        $services[] = SettingsManager::class;
        $services[] = MessageAttachmentRepository::class;
        $services[] = ResourceFactory::class;
        $services[] = ResourceNodeRepository::class;
        $services[] = SettingsFormFactory::class;

        /*
            The following classes are needed in order to load the resources files when using the /r/ path
            For example: http://my.chamilomaster.net/r/agenda/event_attachments/96/download?cid=1&sid=0&gid=0
            Then the repository CCalendarEventAttachmentRepository need to be added here,
            because it was set in the tools.yml like this:
            chamilo_core.tool.agenda:
                (...)
                event_attachments:
                    repository: Chamilo\CourseBundle\Repository\CCalendarEventAttachmentRepository
        */
        $services[] = CAnnouncementRepository::class;
        $services[] = CAnnouncementAttachmentRepository::class;
        $services[] = CAttendanceRepository::class;
        $services[] = CCalendarEventAttachmentRepository::class;
        $services[] = CDocumentRepository::class;
        $services[] = CForumRepository::class;
        $services[] = CForumAttachmentRepository::class;
        $services[] = CLpRepository::class;
        $services[] = CLpCategoryRepository::class;
        $services[] = CToolRepository::class;
        $services[] = CQuizQuestionRepository::class;
        $services[] = CQuizQuestionCategoryRepository::class;
        $services[] = CStudentPublicationRepository::class;
        $services[] = CStudentPublicationCommentRepository::class;
        $services[] = CStudentPublicationCorrectionRepository::class;
        $services[] = ExternalToolRepository::class;
        $services[] = IllustrationRepository::class;

        return $services;
    }

    public function getRequest(): ?Request
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }

    public function abort(string $message = ''): void
    {
        throw new NotFoundHttpException($message);
    }

    /**
     * Translator shortcut.
     */
    public function trans(string $variable): string
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->container->get('translator');

        return $translator->trans($variable);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getGlide(): Glide
    {
        return $this->container->get('glide');
    }

    public function getAccessUrl(): ?AccessUrl
    {
        $urlId = $this->getRequest()->getSession()->get('access_url_id');

        return $this->container->get('doctrine')->getRepository(AccessUrl::class)->find($urlId);
    }

    protected function getSettingsManager(): SettingsManager
    {
        return $this->container->get(SettingsManager::class);
    }

    protected function getSettingsFormFactory()
    {
        return $this->container->get(SettingsFormFactory::class);
    }
}
