<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyTemplateConfiguration;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CourseBundle\Entity\CSurveyTemplate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<SurveyTemplateConfiguration, SurveyTemplateConfiguration|null>
 */
final readonly class SurveyTemplateConfigurationProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccessUrlHelper $accessUrlHelper,
        private Security $security,
        private SurveyTemplateConfigurationProvider $provider,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?SurveyTemplateConfiguration
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Only administrators can manage survey templates.');
        }

        $operationName = (string) $operation->getName();
        $templateId = isset($uriVariables['templateId']) ? (int) $uriVariables['templateId'] : 0;

        if ('delete_survey_template_configuration' === $operationName) {
            if ($templateId <= 0) {
                throw new BadRequestHttpException('A valid survey template id is required.');
            }

            $template = $this->provider->getTemplate($templateId);
            $this->entityManager->remove($template);
            $this->entityManager->flush();

            return null;
        }

        if (!$data instanceof SurveyTemplateConfiguration) {
            throw new BadRequestHttpException('Invalid survey template payload.');
        }

        $this->validatePayload($data);
        if ($templateId > 0) {
            $template = $this->provider->getTemplate($templateId);
        } else {
            $accessUrl = $this->accessUrlHelper->getCurrent();
            if (null === $accessUrl) {
                throw new BadRequestHttpException('The current access URL is required.');
            }

            $user = $this->security->getUser();
            if (!$user instanceof User) {
                throw new AccessDeniedHttpException('A valid administrator user is required.');
            }

            $template = (new CSurveyTemplate())
                ->setAccessUrl($accessUrl)
                ->setCreatedBy($user)
            ;
        }

        $template
            ->setTitle(trim($data->title))
            ->setSubtitle($data->subtitle)
            ->setIntro($data->introduction)
            ->setSurveyThanks($data->thanks)
            ->setAnonymous($data->anonymous ? '1' : '0')
            ->setShuffle($data->shuffle)
            ->setOneQuestionPerPage($data->oneQuestionPerPage)
            ->setDisplayQuestionNumber($data->displayQuestionNumber)
            ->setIsMandatory($data->mandatory)
            ->setDuration($data->duration)
            ->setVisible($data->visible)
            ->touch()
        ;

        $this->entityManager->persist($template);
        $this->entityManager->flush();

        return $this->provider->buildResponse($template);
    }

    private function validatePayload(SurveyTemplateConfiguration $data): void
    {
        $title = trim(strip_tags($data->title));
        if ('' === $title) {
            throw new BadRequestHttpException('The survey template title is required.');
        }

        if (mb_strlen($title) > 255) {
            throw new BadRequestHttpException('The survey template title cannot be longer than 255 characters.');
        }

        if (null !== $data->duration && $data->duration < 0) {
            throw new BadRequestHttpException('The survey template duration cannot be negative.');
        }
    }
}
