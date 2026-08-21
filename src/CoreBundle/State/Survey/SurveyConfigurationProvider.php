<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
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
use DateInterval;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use const ENT_HTML5;
use const ENT_QUOTES;

/**
 * @implements ProviderInterface<SurveyConfiguration>
 */
final readonly class SurveyConfigurationProvider implements ProviderInterface
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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SurveyConfiguration
    {
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
        $configuration->settings = $this->getSettings();
        $configuration->options = $this->getOptions($course, $session, $survey);
        $configuration->questionUrl = $this->buildModernQuestionsUrl($survey, $course, $session);

        $gradebookLink = $this->gradebookLinkManager->findLink($course, $session, GradebookLinkResourceResolver::LINK_SURVEY, $surveyId);
        if (null !== $gradebookLink) {
            $configuration->gradebookEnabled = true;
            $configuration->gradebookCategoryId = $gradebookLink->getCategory()->getId();
            $configuration->gradebookWeight = $gradebookLink->getWeight();
        }

        return $configuration;
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
                ['value' => self::VISIBLE_TUTOR, 'label' => 'Tutor'],
                ['value' => self::VISIBLE_TUTOR_STUDENT, 'label' => 'Tutor and student'],
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
        return $this->gradebookLinkManager->getCategoryOptions($course, $session);
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
