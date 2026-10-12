<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyTemplateConfiguration;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CourseBundle\Entity\CSurveyTemplate;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<SurveyTemplateConfiguration>
 */
final readonly class SurveyTemplateConfigurationProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccessUrlHelper $accessUrlHelper,
        private Security $security,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SurveyTemplateConfiguration
    {
        $templateId = isset($uriVariables['templateId']) ? (int) $uriVariables['templateId'] : 0;
        if ($templateId <= 0) {
            return new SurveyTemplateConfiguration();
        }

        return $this->buildResponse($this->getTemplate($templateId));
    }

    public function buildResponse(CSurveyTemplate $template): SurveyTemplateConfiguration
    {
        $response = new SurveyTemplateConfiguration();
        $response->templateId = (int) $template->getId();
        $response->mode = 'edit';
        $response->title = $template->getTitle();
        $response->subtitle = (string) $template->getSubtitle();
        $response->introduction = (string) $template->getIntro();
        $response->thanks = (string) $template->getSurveyThanks();
        $response->anonymous = '1' === $template->getAnonymous();
        $response->shuffle = $template->getShuffle();
        $response->oneQuestionPerPage = $template->getOneQuestionPerPage();
        $response->displayQuestionNumber = $template->isDisplayQuestionNumber();
        $response->mandatory = $template->isMandatory();
        $response->duration = $template->getDuration();
        $response->visible = $template->isVisible();
        $response->questionCount = $template->getQuestions()->count();
        $response->createdAt = $template->getCreatedAt()->format(DateTimeInterface::ATOM);
        $response->updatedAt = $template->getUpdatedAt()->format(DateTimeInterface::ATOM);

        return $response;
    }

    public function getTemplate(int $templateId): CSurveyTemplate
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl) {
            throw new BadRequestHttpException('The current access URL is required.');
        }

        $template = $this->entityManager->getRepository(CSurveyTemplate::class)->find($templateId);
        if (!$template instanceof CSurveyTemplate) {
            throw new NotFoundHttpException('The requested survey template was not found.');
        }

        if ((int) $template->getAccessUrl()->getId() !== (int) $accessUrl->getId()) {
            throw new AccessDeniedHttpException('The requested survey template does not belong to the current access URL.');
        }

        if (!$this->security->isGranted('ROLE_ADMIN') && !$template->isVisible()) {
            throw new AccessDeniedHttpException('The requested survey template is not available to teachers.');
        }

        return $template;
    }
}
