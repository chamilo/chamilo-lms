<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyConfiguration;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

/**
 * @implements ProcessorInterface<SurveyConfiguration, SurveyConfiguration>
 */
final readonly class SurveyConfigurationProcessor implements ProcessorInterface
{
    use SurveyPersonalitySupportTrait;
    use SurveyProfileFieldsTrait;
    use SurveyCsrfTokenValidationTrait;

    private const VISIBLE_TUTOR = 0;
    private const VISIBLE_TUTOR_STUDENT = 1;
    private const VISIBLE_PUBLIC = 2;
    private const GRADEBOOK_LINK_TYPE_SURVEY = 8;
    private const CSRF_TOKEN_ID = 'survey_configuration';

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CSurveyRepository $surveyRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
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

        $this->validateSubmittedCsrfToken($request, $this->csrfTokenManager, self::CSRF_TOKEN_ID, $data, $data->csrfToken);

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        if (!$this->canManageSurveys()) {
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
        if ($this->surveyCodeExists($code, $language, null)) {
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
        if ($this->surveyCodeExists((string) $survey->getCode(), $language, $surveyId)) {
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

    private function surveyCodeExists(string $code, string $language, ?int $exceptSurveyId): bool
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('COUNT(survey.iid)')
            ->from(CSurvey::class, 'survey')
            ->andWhere('survey.code = :code')
            ->andWhere('survey.lang = :language')
            ->setParameter('code', $code)
            ->setParameter('language', $language)
        ;

        if (null !== $exceptSurveyId) {
            $queryBuilder
                ->andWhere('survey.iid <> :surveyId')
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
        $existingLink = $this->findGradebookLink($course, $surveyId);

        if (!$data->gradebookEnabled) {
            if (null !== $existingLink) {
                $this->entityManager->remove($existingLink);
            }

            return;
        }

        if (null === $data->gradebookCategoryId || $data->gradebookCategoryId <= 0) {
            throw new BadRequestHttpException('A gradebook category is required.');
        }

        $category = $this->getGradebookCategory($data->gradebookCategoryId, $course, $session);
        $link = $existingLink ?? new GradebookLink();
        $link
            ->setType(self::GRADEBOOK_LINK_TYPE_SURVEY)
            ->setVisible(1)
            ->setWeight($data->gradebookWeight)
            ->setRefId($surveyId)
            ->setCategory($category)
            ->setCourse($course)
            ->setMinScore(0.0)
        ;

        if (null === $link->getId()) {
            $link->setCreatedAt(new DateTime());
        }

        $this->entityManager->persist($link);
    }

    private function getGradebookCategory(int $categoryId, Course $course, ?Session $session): GradebookCategory
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('category')
            ->from(GradebookCategory::class, 'category')
            ->andWhere('category.id = :categoryId')
            ->andWhere('IDENTITY(category.course) = :courseId')
            ->setParameter('categoryId', $categoryId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
        ;

        if (null === $session) {
            $queryBuilder->andWhere('category.session IS NULL');
        } else {
            $queryBuilder
                ->andWhere('IDENTITY(category.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        }

        $category = $queryBuilder->getQuery()->getOneOrNullResult();
        if (!$category instanceof GradebookCategory) {
            throw new BadRequestHttpException('The selected gradebook category is invalid.');
        }

        return $category;
    }

    private function findGradebookLink(Course $course, int $surveyId): ?GradebookLink
    {
        $link = $this->entityManager->createQueryBuilder()
            ->select('link')
            ->from(GradebookLink::class, 'link')
            ->andWhere('IDENTITY(link.course) = :courseId')
            ->andWhere('link.type = :type')
            ->andWhere('link.refId = :surveyId')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('type', self::GRADEBOOK_LINK_TYPE_SURVEY, Types::INTEGER)
            ->setParameter('surveyId', $surveyId, Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $link instanceof GradebookLink ? $link : null;
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

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if ($courseId <= 0) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }

    private function getSession(Request $request): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if ($sessionId <= 0) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
    }

    private function canManageSurveys(): bool
    {
        if ($this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')) {
            return true;
        }

        if (!$this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')) {
            return false;
        }

        return $this->isSettingEnabled('survey.extend_rights_for_coach_on_survey');
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
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
        $configuration->csrfToken = (string) $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID);

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
