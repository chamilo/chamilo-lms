<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyTemplateInstantiate;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\SurveyHelper;
use Chamilo\CoreBundle\Service\Survey\SurveyTemplateInstantiationService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CSurveyTemplate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<SurveyTemplateInstantiate, SurveyTemplateInstantiate>
 */
final readonly class SurveyTemplateInstantiateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CidReqHelper $cidReqHelper,
        private AccessUrlHelper $accessUrlHelper,
        private Security $security,
        private SurveyHelper $surveyHelper,
        private SettingsManager $settingsManager,
        private SurveyTemplateInstantiationService $instantiationService,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SurveyTemplateInstantiate
    {
        if (!$data instanceof SurveyTemplateInstantiate) {
            throw new BadRequestHttpException('Invalid survey template instantiation payload.');
        }

        if ($this->isSurveyCreationDisabled()) {
            throw new AccessDeniedHttpException('Survey creation is disabled by platform configuration.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        $templateId = isset($uriVariables['templateId']) ? (int) $uriVariables['templateId'] : 0;
        if ($templateId <= 0) {
            throw new BadRequestHttpException('A valid survey template id is required.');
        }

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

        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        if (!$isAdmin && !$template->isVisible()) {
            throw new AccessDeniedHttpException('The requested survey template is not available to teachers.');
        }

        [$course, $session] = $this->resolveTarget($data, $isAdmin);
        $this->assertCourseBelongsToAccessUrl($course, (int) $accessUrl->getId());

        $survey = $this->instantiationService->instantiate($template, $course, $session, $user);

        $response = new SurveyTemplateInstantiate();
        $response->templateId = $templateId;
        $response->surveyId = (int) $survey->getIid();
        $response->questionCount = $template->getQuestions()->count();
        $response->questionUrl = '/resources/survey/'
            .(int) $course->getResourceNode()?->getId()
            .'/'.(int) $survey->getIid()
            .'/questions?cid='.(int) $course->getId()
            .(null !== $session ? '&sid='.(int) $session->getId() : '');
        $response->message = 'Survey created from template.';

        return $response;
    }

    /**
     * @return array{0: Course, 1: Session|null}
     */
    private function resolveTarget(SurveyTemplateInstantiate $data, bool $isAdmin): array
    {
        if ($isAdmin && (int) ($data->targetCourseId ?? 0) > 0) {
            $course = $this->entityManager->getRepository(Course::class)->find((int) $data->targetCourseId);
            if (!$course instanceof Course) {
                throw new BadRequestHttpException('The target course was not found.');
            }

            $session = $this->resolveSession($course, (int) ($data->targetSessionId ?? 0));

            return [$course, $session];
        }

        if (!$this->surveyHelper->canManage()) {
            throw new AccessDeniedHttpException('You are not allowed to create surveys in this context.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();

        if (!$isAdmin && null !== $data->targetCourseId && (int) $data->targetCourseId !== (int) $course->getId()) {
            throw new AccessDeniedHttpException('Teachers cannot instantiate a survey template into another course.');
        }
        if (!$isAdmin && null !== $data->targetSessionId && (int) $data->targetSessionId !== (int) ($session?->getId() ?? 0)) {
            throw new AccessDeniedHttpException('Teachers cannot instantiate a survey template into another session.');
        }

        return [$course, $session];
    }

    private function resolveSession(Course $course, int $sessionId): ?Session
    {
        if ($sessionId <= 0) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The target session was not found.');
        }

        $belongs = (int) $this->entityManager->getConnection()->fetchOne(
            'SELECT COUNT(1) FROM session_rel_course WHERE session_id = :sessionId AND c_id = :courseId',
            [
                'sessionId' => $sessionId,
                'courseId' => (int) $course->getId(),
            ],
        );
        if ($belongs <= 0) {
            throw new BadRequestHttpException('The target session does not contain the selected course.');
        }

        return $session;
    }

    private function assertCourseBelongsToAccessUrl(Course $course, int $accessUrlId): void
    {
        $belongs = false !== $this->entityManager->getConnection()->fetchOne(
            'SELECT 1 FROM access_url_rel_course WHERE c_id = :courseId AND access_url_id = :accessUrlId',
            [
                'courseId' => (int) $course->getId(),
                'accessUrlId' => $accessUrlId,
            ],
        );
        if (!$belongs) {
            throw new AccessDeniedHttpException('The target course does not belong to the current access URL.');
        }
    }

    private function isSurveyCreationDisabled(): bool
    {
        $value = $this->settingsManager->getSetting('survey.hide_survey_edition', true);

        return true === $value || 'true' === $value || '*' === $value;
    }
}
