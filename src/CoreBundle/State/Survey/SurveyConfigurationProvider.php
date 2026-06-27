<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyConfiguration;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<SurveyConfiguration>
 */
final readonly class SurveyConfigurationProvider implements ProviderInterface
{
    use SurveyPersonalitySupportTrait;
    use SurveyProfileFieldsTrait;

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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SurveyConfiguration
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        if (!$this->canManageSurveys()) {
            throw new AccessDeniedHttpException('You are not allowed to manage surveys in this context.');
        }

        $surveyId = isset($uriVariables['surveyId']) ? (int) $uriVariables['surveyId'] : 0;
        if ($surveyId > 0) {
            return $this->buildEditConfiguration($surveyId, $course, $session);
        }

        return $this->buildCreateConfiguration($course, $session);
    }

    private function buildCreateConfiguration(Course $course, ?Session $session): SurveyConfiguration
    {
        $configuration = new SurveyConfiguration();
        $configuration->mode = 'create';
        $configuration->surveyLanguage = $this->getCourseLanguage($course);
        $configuration->availableFrom = (new DateTime('today'))->format(DateTimeInterface::ATOM);
        $configuration->availableUntil = (new DateTime('today 23:59:59'))
            ->add(new DateInterval('P10D'))
            ->format(DateTimeInterface::ATOM)
        ;
        $configuration->anonymous = false;
        $configuration->visibleResults = self::VISIBLE_TUTOR;
        $configuration->displayQuestionNumber = true;
        $configuration->canCreate = true;
        $configuration->canEdit = true;
        $configuration->csrfToken = (string) $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID);
        $configuration->settings = $this->getSettings();
        $configuration->options = $this->getOptions($course, $session, null);

        return $configuration;
    }

    private function buildEditConfiguration(int $surveyId, Course $course, ?Session $session): SurveyConfiguration
    {
        $survey = $this->getSurveyFromCurrentContext($surveyId, $course, $session);
        if ($this->isSurveyEditionHidden($survey)) {
            throw new AccessDeniedHttpException('This survey cannot be edited because edition is disabled by configuration.');
        }

        $configuration = new SurveyConfiguration();
        $configuration->surveyId = $surveyId;
        $configuration->mode = 'edit';
        $configuration->code = (string) $survey->getCode();
        $configuration->title = $survey->getTitle();
        $configuration->subtitle = (string) $survey->getSubtitle();
        $configuration->surveyLanguage = (string) $survey->getLang();
        $configuration->resourceLanguage = $this->getResourceLanguage($survey);
        $configuration->availableFrom = $this->formatDate($survey->getAvailFrom());
        $configuration->availableUntil = $this->formatDate($survey->getAvailTill());
        $configuration->anonymous = $this->isTruthy($survey->getAnonymous());
        $configuration->visibleResults = (int) ($survey->getVisibleResults() ?? self::VISIBLE_TUTOR);
        $configuration->introduction = (string) $survey->getIntro();
        $configuration->thanks = (string) $survey->getSurveythanks();
        $configuration->surveyType = $survey->getSurveyType();
        $configuration->parentId = $survey->getSurveyParent()?->getIid();
        $configuration->oneQuestionPerPage = $survey->getOneQuestionPerPage();
        $configuration->shuffle = $survey->getShuffle();
        $configuration->displayQuestionNumber = $survey->isDisplayQuestionNumber();
        $configuration->showFormProfile = 1 === $survey->getShowFormProfile();
        $configuration->selectedProfileFields = $this->getSelectedSurveyProfileFields($survey);
        $configuration->duration = $survey->getDuration();
        $configuration->canCreate = true;
        $configuration->canEdit = true;
        $configuration->csrfToken = (string) $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID);
        $configuration->settings = $this->getSettings();
        $configuration->options = $this->getOptions($course, $session, $survey);
        $configuration->questionUrl = $this->buildModernQuestionsUrl($survey, $course, $session);

        $gradebookLink = $this->findGradebookLink($course, $surveyId);
        if (null !== $gradebookLink) {
            $configuration->gradebookEnabled = true;
            $configuration->gradebookCategoryId = $gradebookLink->getCategory()->getId();
            $configuration->gradebookWeight = $gradebookLink->getWeight();
        }

        return $configuration;
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

    /**
     * @return array<string, mixed>
     */
    private function getSettings(): array
    {
        return [
            'hideReportingButton' => $this->isSettingEnabled('survey.hide_survey_reporting_button'),
            'hideEdition' => $this->settingsManager->getSetting('survey.hide_survey_edition', true) ?: '',
            'showProfileFormSupported' => true,
            'skillsSupported' => false,
            'extraGroupSupported' => false,
            'personalitySupported' => $this->isPersonalitySurveySupported(),
            'personalityUnsupportedReason' => $this->isPersonalitySurveySupported() ? '' : $this->getUnsupportedPersonalitySurveyMessage(),
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function getOptions(Course $course, ?Session $session, ?CSurvey $currentSurvey): array
    {
        return [
            'visibleResults' => [
                ['value' => self::VISIBLE_TUTOR, 'label' => 'Coach'],
                ['value' => self::VISIBLE_TUTOR_STUDENT, 'label' => 'Coach and student'],
                ['value' => self::VISIBLE_PUBLIC, 'label' => 'Everyone'],
            ],
            'surveyTypes' => $this->getSurveyTypeOptions(),
            'languages' => $this->getLanguageOptions(),
            'parentSurveys' => $this->getParentSurveyOptions($course, $session, $currentSurvey),
            'gradebookCategories' => $this->getGradebookCategoryOptions($course, $session),
            'profileFields' => $this->getAvailableSurveyProfileFieldOptions(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getSurveyTypeOptions(): array
    {
        $types = [
            ['value' => 0, 'label' => 'Normal'],
        ];

        if ($this->isPersonalitySurveySupported()) {
            $types[] = ['value' => 1, 'label' => 'Conditional'];
        }

        return $types;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getLanguageOptions(): array
    {
        $items = [
            ['value' => '', 'label' => 'No specific language'],
        ];

        $languages = $this->entityManager
            ->getRepository(Language::class)
            ->findBy(['available' => true], ['englishName' => 'ASC'])
        ;

        foreach ($languages as $language) {
            if (!$language instanceof Language) {
                continue;
            }

            $items[] = [
                'value' => $language->getIsocode(),
                'label' => $language->getOriginalName() ?: $language->getEnglishName(),
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getParentSurveyOptions(Course $course, ?Session $session, ?CSurvey $currentSurvey): array
    {
        $items = [
            ['value' => 0, 'label' => ''],
        ];

        $queryBuilder = $this->surveyRepository->getResourcesByCourse(
            $course,
            $session,
            null,
            null,
            false,
            true,
        );
        $queryBuilder->orderBy('resource.title', 'ASC');

        foreach ($queryBuilder->getQuery()->getResult() as $survey) {
            if (!$survey instanceof CSurvey || null === $survey->getIid()) {
                continue;
            }

            if (null !== $currentSurvey && $survey->getIid() === $currentSurvey->getIid()) {
                continue;
            }

            $title = trim(strip_tags(html_entity_decode((string) $survey->getTitle(), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            $code = trim((string) $survey->getCode());

            $items[] = [
                'value' => (int) $survey->getIid(),
                'label' => '' !== $title ? $title : $code,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getGradebookCategoryOptions(Course $course, ?Session $session): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('category')
            ->from(GradebookCategory::class, 'category')
            ->andWhere('IDENTITY(category.course) = :courseId')
            ->andWhere('category.gradeModel IS NULL')
            ->orderBy('category.id', 'ASC')
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

        $items = [];
        foreach ($queryBuilder->getQuery()->getResult() as $category) {
            if (!$category instanceof GradebookCategory || null === $category->getId()) {
                continue;
            }

            $items[] = [
                'value' => (int) $category->getId(),
                'label' => $this->getGradebookCategoryLabel($category),
            ];
        }

        return $items;
    }

    private function getGradebookCategoryLabel(GradebookCategory $category): string
    {
        $title = trim((string) $category->getTitle());
        $title = '' !== $title && !ctype_digit($title) ? $title : 'Default';

        $parent = $category->getParent();
        if (!$parent instanceof GradebookCategory) {
            return $title;
        }

        $parentTitle = trim((string) $parent->getTitle());
        $parentTitle = '' !== $parentTitle && !ctype_digit($parentTitle) ? $parentTitle : 'Default';

        return $parentTitle.' / '.$title;
    }

    private function findGradebookLink(Course $course, int $surveyId): ?GradebookLink
    {
        $link = $this->entityManager->createQueryBuilder()
            ->select('link', 'category')
            ->from(GradebookLink::class, 'link')
            ->innerJoin('link.category', 'category')
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

    private function getCourseLanguage(Course $course): string
    {
        if (method_exists($course, 'getCourseLanguage')) {
            return (string) $course->getCourseLanguage();
        }

        return '';
    }

    private function getResourceLanguage(CSurvey $survey): string
    {
        if (!method_exists($survey, 'getResourceNode') || null === $survey->getResourceNode()) {
            return '';
        }

        $language = $survey->getResourceNode()->getLanguage();

        return $language instanceof Language ? $language->getIsocode() : '';
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

    private function isTruthy(mixed $value): bool
    {
        if (true === $value) {
            return true;
        }

        $normalizedValue = strtolower((string) $value);

        return '1' === $normalizedValue || 'true' === $normalizedValue || 'yes' === $normalizedValue;
    }

    private function formatDate(?DateTimeInterface $date): ?string
    {
        return $date?->format(DateTimeInterface::ATOM);
    }
}
