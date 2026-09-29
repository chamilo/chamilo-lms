<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyConfiguration;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\SurveyHelper;
use Chamilo\CoreBundle\Service\Gradebook\GradebookLinkManager;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\Gradebook\GradebookLinkResourceResolver;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * @implements ProcessorInterface<SurveyConfiguration, SurveyConfiguration>
 */
final readonly class SurveyConfigurationProcessor implements ProcessorInterface
{
    use SurveyPersonalitySupportTrait;
    use SurveyProfileFieldsTrait;

    private const int VISIBLE_TUTOR = 0;
    private const int VISIBLE_TUTOR_STUDENT = 1;
    private const int VISIBLE_PUBLIC = 2;

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CSurveyRepository $surveyRepository,
        private GradebookLinkManager $gradebookLinkManager,
        private SettingsManager $settingsManager,
        private SurveyHelper $surveyHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SurveyConfiguration
    {
        if (!$data instanceof SurveyConfiguration) {
            throw new BadRequestHttpException('Invalid survey configuration payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $this->gradebookLinkManager->assertSessionBelongsToCourse($course, $session);
        if (!$this->surveyHelper->canManage()) {
            throw new AccessDeniedHttpException('You are not allowed to manage surveys in this context.');
        }

        $surveyId = isset($uriVariables['surveyId']) ? (int) $uriVariables['surveyId'] : (int) ($data->surveyId ?? 0);
        if ($surveyId > 0) {
            $survey = $this->updateSurvey($surveyId, $data, $course, $session);
        } else {
            $survey = $this->createSurvey($data, $course, $session);
        }

        $this->applyResourceLanguage($survey, $data->resourceLanguage);
        $this->updateGradebookLink($survey, $data, $course, $session);
        $this->entityManager->flush();

        return $this->buildResponse($survey, $course, $session);
    }

    private function createSurvey(SurveyConfiguration $data, Course $course, ?Session $session): CSurvey
    {
        if ($this->isSurveyEditionGloballyHidden()) {
            throw new AccessDeniedHttpException('Survey creation is disabled by configuration.');
        }

        $this->validatePayload($data, false);

        $code = $this->normalizeSurveyCode($data->code);
        if ('' === $code) {
            throw new BadRequestHttpException('The survey code is invalid.');
        }

        $language = $this->getSurveyLanguage($data, $course);
        if ($this->surveyCodeExists($code, $language, null, $course, $session)) {
            throw new BadRequestHttpException('This survey code already exists in this language.');
        }

        if (1 === $data->surveyType) {
            throw new BadRequestHttpException($this->getUnsupportedPersonalitySurveyMessage());
        }

        $survey = new CSurvey();
        $this->applyCommonFields($survey, $data, $language);
        $survey
            ->setCode($code)
            ->setLang($language)
            ->setIsShared('0')
            ->setTemplate('template')
            ->setSurveyType(0)
            ->setParent($course)
            ->addCourseLink($course, $session)
        ;

        $this->surveyRepository->create($survey);

        return $survey;
    }

    private function updateSurvey(int $surveyId, SurveyConfiguration $data, Course $course, ?Session $session): CSurvey
    {
        $survey = $this->getSurveyFromCurrentContext($surveyId, $course, $session);
        if (3 === $survey->getSurveyType()) {
            throw new BadRequestHttpException('Meeting polls must be managed from the meeting poll view.');
        }

        if ($this->isSurveyEditionHidden($survey)) {
            throw new AccessDeniedHttpException('This survey cannot be edited because edition is disabled by configuration.');
        }

        $this->validatePayload($data, true);
        $language = $this->getSurveyLanguage($data, $course);
        if ($this->surveyCodeExists((string) $survey->getCode(), $language, $surveyId, $course, $session)) {
            throw new BadRequestHttpException('This survey code already exists in this language.');
        }

        $this->applyCommonFields($survey, $data, $language);
        $survey->setIsShared('0');
        $this->entityManager->persist($survey);

        return $survey;
    }

    private function applyCommonFields(CSurvey $survey, SurveyConfiguration $data, string $language): void
    {
        $availableFrom = $this->parseDate($data->availableFrom, 'availableFrom');
        $availableUntil = $this->parseDate($data->availableUntil, 'availableUntil');

        if ($availableFrom > $availableUntil) {
            throw new BadRequestHttpException('The first date should be before the end date.');
        }

        $visibleResults = $data->visibleResults;
        if (!\in_array($visibleResults, [self::VISIBLE_TUTOR, self::VISIBLE_TUTOR_STUDENT, self::VISIBLE_PUBLIC], true)) {
            $visibleResults = self::VISIBLE_TUTOR;
        }

        if ($this->isSettingEnabled('survey.hide_survey_reporting_button')) {
            $visibleResults = self::VISIBLE_TUTOR;
        }

        $survey
            ->setTitle(trim($data->title))
            ->setSubtitle((string) $data->subtitle)
            ->setLang($language)
            ->setAvailFrom($availableFrom)
            ->setAvailTill($availableUntil)
            ->setIntro((string) $data->introduction)
            ->setSurveythanks((string) $data->thanks)
            ->setAnonymous($data->anonymous ? '1' : '0')
            ->setVisibleResults($visibleResults)
            ->setDisplayQuestionNumber($data->displayQuestionNumber)
            ->setOneQuestionPerPage($data->oneQuestionPerPage)
            ->setShuffle($data->shuffle)
            ->setDuration($data->duration)
        ;

        if ($data->anonymous) {
            $survey
                ->setShowFormProfile(0)
                ->setFormFields('')
            ;

            return;
        }

        $formFields = $this->buildSurveyProfileFormFieldsString($data->selectedProfileFields);
        $survey->setShowFormProfile($data->showFormProfile && '' !== $formFields ? 1 : 0);
        $survey->setFormFields($data->showFormProfile ? $formFields : '');
    }

    private function validatePayload(SurveyConfiguration $data, bool $isEdit): void
    {
        if (!$isEdit && '' === trim($data->code)) {
            throw new BadRequestHttpException('The survey code is required.');
        }

        if ('' === trim(strip_tags($data->title))) {
            throw new BadRequestHttpException('The survey title is required.');
        }

        if (null === $data->availableFrom || '' === trim($data->availableFrom)) {
            throw new BadRequestHttpException('The start date is required.');
        }

        if (null === $data->availableUntil || '' === trim($data->availableUntil)) {
            throw new BadRequestHttpException('The end date is required.');
        }
    }

    private function parseDate(?string $value, string $field): DateTime
    {
        if (null === $value || '' === trim($value)) {
            throw new BadRequestHttpException('The '.$field.' field is required.');
        }

        try {
            $date = new DateTime($value);
        } catch (Throwable) {
            throw new BadRequestHttpException('The '.$field.' field contains an invalid date.');
        }

        $date->setTimezone(new DateTimeZone('UTC'));

        return $date;
    }

    private function getSurveyLanguage(SurveyConfiguration $data, Course $course): string
    {
        $language = trim($data->surveyLanguage);
        if ('' !== $language) {
            return $language;
        }

        if (method_exists($course, 'getCourseLanguage')) {
            return (string) $course->getCourseLanguage();
        }

        return '';
    }

    private function normalizeSurveyCode(string $code): string
    {
        $normalized = strtolower(trim($code));
        $normalized = preg_replace('/[^a-z0-9_\-]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_-');

        return substr($normalized, 0, 40);
    }

    private function surveyCodeExists(
        string $code,
        string $language,
        ?int $exceptSurveyId,
        Course $course,
        ?Session $session
    ): bool {
        $queryBuilder = $this->surveyRepository
            ->getResourcesByCourse($course, $session, null, null, false, false)
            ->select('COUNT(DISTINCT resource.iid)')
            ->andWhere('resource.code = :code')
            ->andWhere('resource.lang = :language')
            ->setParameter('code', $code)
            ->setParameter('language', $language)
        ;

        if (null !== $exceptSurveyId) {
            $queryBuilder
                ->andWhere('resource.iid <> :surveyId')
                ->setParameter('surveyId', $exceptSurveyId, Types::INTEGER)
            ;
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }

    private function applyResourceLanguage(CSurvey $survey, string $rawLanguage): void
    {
        if (!method_exists($survey, 'getResourceNode') || null === $survey->getResourceNode()) {
            return;
        }

        $languageCode = trim($rawLanguage);
        $language = null;
        if ('' !== $languageCode) {
            $language = $this->entityManager
                ->getRepository(Language::class)
                ->findOneBy([
                    'isocode' => $languageCode,
                    'available' => true,
                ])
            ;

            if (!$language instanceof Language) {
                throw new BadRequestHttpException('The selected resource language is invalid.');
            }
        }

        $resourceNode = $survey->getResourceNode();
        $resourceNode->setLanguage($language);
        $this->entityManager->persist($resourceNode);
    }

    private function updateGradebookLink(
        CSurvey $survey,
        SurveyConfiguration $data,
        Course $course,
        ?Session $session
    ): void {
        $surveyId = (int) $survey->getIid();
        if ($surveyId <= 0) {
            throw new BadRequestHttpException('The survey must be saved before it can be linked to the Gradebook.');
        }

        if (!$data->gradebookEnabled) {
            $this->gradebookLinkManager->removeLinks(
                $course,
                $session,
                GradebookLinkResourceResolver::LINK_SURVEY,
                $surveyId,
            );

            return;
        }

        if (null === $data->gradebookCategoryId || $data->gradebookCategoryId <= 0) {
            throw new BadRequestHttpException('A gradebook category is required.');
        }

        $this->gradebookLinkManager->upsertLink(
            $course,
            $session,
            GradebookLinkResourceResolver::LINK_SURVEY,
            $surveyId,
            (int) $data->gradebookCategoryId,
            (float) $data->gradebookWeight,
        );
    }

    private function getSurveyFromCurrentContext(int $surveyId, Course $course, ?Session $session): CSurvey
    {
        $survey = $this->surveyRepository->find($surveyId);
        if (!$survey instanceof CSurvey) {
            throw new NotFoundHttpException('The requested survey was not found.');
        }

        if ($this->isSurveyInContext($survey, $course, $session)) {
            return $survey;
        }

        throw new AccessDeniedHttpException('The requested survey does not belong to the current course context.');
    }

    private function isSurveyInContext(CSurvey $survey, Course $course, ?Session $session): bool
    {
        $contexts = [$session];
        if (null !== $session && $this->isSettingEnabled('survey.show_surveys_base_in_sessions')) {
            $contexts[] = null;
        }

        foreach ($contexts as $currentSession) {
            $queryBuilder = $this->surveyRepository->getResourcesByCourse(
                $course,
                $currentSession,
                null,
                null,
                false,
                true,
            );

            $queryBuilder
                ->andWhere('resource.iid = :surveyId')
                ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
            ;

            if (null !== $queryBuilder->getQuery()->getOneOrNullResult()) {
                return true;
            }
        }

        return false;
    }

    private function isSurveyEditionGloballyHidden(): bool
    {
        $value = $this->settingsManager->getSetting('survey.hide_survey_edition', true);

        return true === $value || 'true' === $value || '*' === $value;
    }

    private function isSurveyEditionHidden(CSurvey $survey): bool
    {
        $value = $this->settingsManager->getSetting('survey.hide_survey_edition', true);
        if (empty($value) || 'false' === $value) {
            return false;
        }

        if (true === $value || 'true' === $value || '*' === $value) {
            return true;
        }

        $code = (string) $survey->getCode();
        if (\is_array($value)) {
            if (isset($value['codes']) && '*' === $value['codes']) {
                return true;
            }

            $codes = $value['codes'] ?? $value;

            return \is_array($codes) && \in_array($code, $codes, true);
        }

        if (!\is_string($value)) {
            return false;
        }

        $codes = preg_split('/[\s,;]+/', trim($value)) ?: [];

        return \in_array('*', $codes, true) || \in_array($code, $codes, true);
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }

    private function buildResponse(CSurvey $survey, Course $course, ?Session $session): SurveyConfiguration
    {
        $configuration = new SurveyConfiguration();
        $configuration->surveyId = (int) $survey->getIid();
        $configuration->mode = 'edit';
        $configuration->code = (string) $survey->getCode();
        $configuration->title = $survey->getTitle();
        $configuration->questionUrl = $this->buildModernQuestionsUrl($survey, $course, $session);

        return $configuration;
    }

    private function buildModernQuestionsUrl(CSurvey $survey, Course $course, ?Session $session): string
    {
        $nodeId = method_exists($survey, 'getResourceNode') && null !== $survey->getResourceNode()
            ? (int) $survey->getResourceNode()->getId()
            : (int) $course->getId();

        return \sprintf(
            '/resources/survey/%d/%d/questions?%s',
            $nodeId,
            (int) $survey->getIid(),
            http_build_query([
                'cid' => (int) $course->getId(),
                'sid' => (int) ($session?->getId() ?? 0),
            ]),
        );
    }
}
