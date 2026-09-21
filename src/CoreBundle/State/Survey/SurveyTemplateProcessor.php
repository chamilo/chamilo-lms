<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyTemplate;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\SurveyHelper;
use Chamilo\CoreBundle\Service\Gradebook\GradebookLinkManager;
use Chamilo\CoreBundle\Service\Survey\TrainingSatisfactionSurveyCreator;
use Chamilo\CoreBundle\Settings\SettingsManager;
use InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<SurveyTemplate, SurveyTemplate>
 */
final readonly class SurveyTemplateProcessor implements ProcessorInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private GradebookLinkManager $gradebookLinkManager,
        private Security $security,
        private SurveyHelper $surveyHelper,
        private SettingsManager $settingsManager,
        private TrainingSatisfactionSurveyCreator $surveyCreator,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SurveyTemplate
    {
        if (!$data instanceof SurveyTemplate) {
            throw new BadRequestHttpException('Invalid survey template payload.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $this->gradebookLinkManager->assertSessionBelongsToCourse($course, $session);

        if (!$this->surveyHelper->canManage()) {
            throw new AccessDeniedHttpException('You are not allowed to manage surveys in this context.');
        }

        if ($this->isSurveyCreationDisabled()) {
            throw new AccessDeniedHttpException('Survey creation is disabled by configuration.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        $language = trim($course->getCourseLanguage());
        $title = trim(strip_tags($data->title));
        if ('' === $title) {
            $title = get_lang('Training satisfaction survey', $language);
        }

        try {
            $created = $this->surveyCreator->create(
                $course,
                $user,
                $title,
                $language,
                null,
                false,
                true,
                $session,
                'training-sat',
            );
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        $response = new SurveyTemplate();
        $response->surveyId = (int) ($created['survey_id'] ?? 0);
        $response->questionCount = (int) ($created['question_count'] ?? 0);
        $response->questionUrl = (string) ($created['content_url'] ?? '');
        $response->message = 'Training satisfaction survey created.';

        return $response;
    }

    private function isSurveyCreationDisabled(): bool
    {
        $value = $this->settingsManager->getSetting('survey.hide_survey_edition', true);

        return true === $value || 'true' === $value || '*' === $value;
    }
}
