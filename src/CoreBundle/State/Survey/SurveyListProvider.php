<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyList;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CGroupRelTutor;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use ExtraFieldValue;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

use const ENT_HTML5;
use const ENT_QUOTES;

/**
 * @implements ProviderInterface<SurveyList>
 */
final readonly class SurveyListProvider implements ProviderInterface
{
    use SurveyPersonalitySupportTrait;

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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SurveyList
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $user = $this->getCurrentUser();
        $canManage = $this->canManageSurveys();
        $search = $this->normalizeSearchTerm($request->query->get('search', ''));

        $surveyList = new SurveyList();
        $surveyList->settings = $this->getSettings();
        $surveyList->canManage = $canManage;
        $surveyList->canCreate = $this->canCreateSurveys();
        $surveyList->items = $canManage
            ? $this->getTeacherItems($course, $session, $search)
            : $this->getLearnerItems($course, $session, $user, $search);
        $surveyList->totalItems = \count($surveyList->items);

        return $surveyList;
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

    private function getCurrentUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function getSettings(): array
    {
        $additionalTeacherActions = $this->settingsManager->getSetting(
            'survey.survey_additional_teacher_modify_actions',
            true,
        ) ?: '';

        return [
            'hideReportingButton' => $this->isSettingEnabled('survey.hide_survey_reporting_button'),
            'hideEdition' => $this->settingsManager->getSetting('survey.hide_survey_edition', true) ?: '',
            'markQuestionAsRequired' => $this->isSettingEnabled('survey.survey_mark_question_as_required'),
            'anonymousShowAnswered' => $this->isSettingEnabled('survey.survey_anonymous_show_answered'),
            'allowAnsweredQuestionEdit' => $this->isSettingEnabled('survey.survey_allow_answered_question_edit'),
            'duplicateOrderByName' => $this->isSettingEnabled('survey.survey_duplicate_order_by_name'),
            'backwardsEnabled' => $this->isSettingEnabled('survey.survey_backwards_enable'),
            'showSurveysBaseInSessions' => $this->isSettingEnabled('survey.show_surveys_base_in_sessions'),
            'showPendingSurveyInMenu' => $this->isSettingEnabled('survey.show_pending_survey_in_menu'),
            'hasAdditionalTeacherActions' => '' !== trim((string) $additionalTeacherActions),
            'personalitySupported' => $this->isPersonalitySurveySupported(),
            'personalityUnsupportedReason' => $this->isPersonalitySurveySupported() ? '' : $this->getUnsupportedPersonalitySurveyMessage(),
        ];
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
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

    private function canCreateSurveys(): bool
    {
        if (!$this->canManageSurveys()) {
            return false;
        }

        $value = $this->settingsManager->getSetting('survey.hide_survey_edition', true);

        return true !== $value && 'true' !== $value && '*' !== $value;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getTeacherItems(Course $course, ?Session $session, string $search): array
    {
        $surveys = $this->getSurveysForTeacher($course, $session);
        $questionCounts = $this->getQuestionCounts($surveys);
        $items = [];

        foreach ($surveys as $survey) {
            if (!$this->surveyMatchesSearch($survey, $search)) {
                continue;
            }

            $surveyId = (int) $survey->getIid();
            $items[] = $this->normalizeTeacherSurvey(
                $survey,
                $course,
                $session,
                $questionCounts[$surveyId] ?? 0,
            );
        }

        return $items;
    }

    /**
     * @return array<int, CSurvey>
     */
    private function getSurveysForTeacher(Course $course, ?Session $session): array
    {
        $items = [];
        $registered = [];
        $showBaseCourseSurveys = null !== $session && $this->isSettingEnabled('survey.show_surveys_base_in_sessions');
        $sessions = $showBaseCourseSurveys ? [null, $session] : [$session];

        foreach ($sessions as $currentSession) {
            $queryBuilder = $this->surveyRepository->getResourcesByCourse(
                $course,
                $currentSession,
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

                $surveyId = (int) $survey->getIid();
                if (isset($registered[$surveyId])) {
                    continue;
                }

                $registered[$surveyId] = true;
                $items[] = $survey;
            }
        }

        return $items;
    }

    private function normalizeSearchTerm(mixed $value): string
    {
        $search = trim((string) $value);
        if ('' === $search) {
            return '';
        }

        return $this->normalizeSearchableText($search);
    }

    private function surveyMatchesSearch(CSurvey $survey, string $search): bool
    {
        if ('' === $search) {
            return true;
        }

        $haystack = $this->normalizeSearchableText(implode(' ', [
            (string) $survey->getTitle(),
            (string) $survey->getSubtitle(),
            (string) $survey->getCode(),
            $this->getSurveyTypeLabel($survey->getSurveyType()),
        ]));

        return str_contains($haystack, $search);
    }

    private function normalizeSearchableText(string $value): string
    {
        $decodedValue = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decodedValue = preg_replace('/\s+/', ' ', $decodedValue) ?? $decodedValue;

        $decodedValue = trim($decodedValue);

        return \function_exists('mb_strtolower') ? mb_strtolower($decodedValue) : strtolower($decodedValue);
    }

    /**
     * @param array<int, CSurvey> $surveys
     *
     * @return array<int, int>
     */
    private function getQuestionCounts(array $surveys): array
    {
        $surveyIds = [];
        foreach ($surveys as $survey) {
            if (null !== $survey->getIid()) {
                $surveyIds[] = (int) $survey->getIid();
            }
        }

        if ([] === $surveyIds) {
            return [];
        }

        $rows = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(question.survey) AS surveyId')
            ->addSelect('COUNT(question.iid) AS questionCount')
            ->from(CSurveyQuestion::class, 'question')
            ->andWhere('IDENTITY(question.survey) IN (:surveyIds)')
            ->groupBy('question.survey')
            ->setParameter('surveyIds', $surveyIds, ArrayParameterType::INTEGER)
            ->getQuery()
            ->getArrayResult()
        ;

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['surveyId']] = (int) $row['questionCount'];
        }

        return $counts;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeTeacherSurvey(CSurvey $survey, Course $course, ?Session $session, int $questionCount): array
    {
        $surveyId = (int) $survey->getIid();
        $isEditionHidden = $this->isSurveyEditionHidden($survey);
        $hideReportingButton = $this->isSettingEnabled('survey.hide_survey_reporting_button');
        $surveyType = $survey->getSurveyType();
        $isUnsupportedPersonality = $this->isUnsupportedPersonalitySurvey($survey);
        $canEdit = !$isEditionHidden && !$isUnsupportedPersonality;
        $canReport = !$hideReportingButton && !$isUnsupportedPersonality;

        return [
            '@id' => '/api/surveys/'.$surveyId,
            '@type' => 'Survey',
            'iid' => $surveyId,
            'code' => $survey->getCode(),
            'title' => $survey->getTitle(),
            'subtitle' => $survey->getSubtitle(),
            'language' => $survey->getLang(),
            'availableFrom' => $this->formatDate($survey->getAvailFrom()),
            'availableUntil' => $this->formatDate($survey->getAvailTill()),
            'availabilityStatus' => $this->getAvailabilityStatus($survey),
            'anonymous' => $this->isTruthy($survey->getAnonymous()),
            'invited' => $survey->getInvited(),
            'answered' => $survey->getAnswered(),
            'questionCount' => $questionCount,
            'surveyType' => $surveyType,
            'surveyTypeLabel' => $this->getSurveyTypeLabel($surveyType),
            'shuffle' => $survey->getShuffle(),
            'oneQuestionPerPage' => $survey->getOneQuestionPerPage(),
            'visibleResults' => $survey->getVisibleResults(),
            'mandatory' => $survey->isMandatory(),
            'visible' => $this->isVisible($survey, $course, $session),
            'canEdit' => $canEdit,
            'canConfigure' => $canEdit,
            'canCopy' => $canEdit && 3 !== $surveyType,
            'canDuplicate' => $canEdit && 3 !== $surveyType,
            'canMultiplicate' => $canEdit && 3 !== $surveyType,
            'canSendToTutors' => $canEdit && 3 !== $surveyType && $this->hasSurveyGroupForTutors($survey, $course),
            'canEmpty' => $canEdit && 3 !== $surveyType,
            'canDelete' => !$isEditionHidden,
            'canInvite' => $canEdit,
            'isUnsupportedPersonality' => $isUnsupportedPersonality,
            'unsupportedReason' => $isUnsupportedPersonality ? $this->getUnsupportedPersonalitySurveyMessage() : '',
            'actionCsrfToken' => (string) $this->csrfTokenManager->getToken(SurveyActionProcessor::CSRF_TOKEN_ID),
            'canPreview' => 3 !== $surveyType,
            'canReport' => $canReport,
            'canAnswer' => false,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getLearnerItems(Course $course, ?Session $session, User $user, string $search): array
    {
        $now = new DateTime();
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('invitation', 'survey')
            ->from(CSurveyInvitation::class, 'invitation')
            ->innerJoin('invitation.survey', 'survey')
            ->andWhere('IDENTITY(invitation.user) = :userId')
            ->andWhere('IDENTITY(invitation.course) = :courseId')
            ->andWhere('survey.availFrom <= :now')
            ->andWhere('survey.availTill >= :now')
            ->orderBy('survey.title', 'ASC')
            ->setParameter('userId', (int) $user->getId())
            ->setParameter('courseId', (int) $course->getId())
            ->setParameter('now', $now, Types::DATETIME_MUTABLE)
        ;

        if (null === $session) {
            $queryBuilder->andWhere('invitation.session IS NULL');
        } else {
            $queryBuilder
                ->andWhere('IDENTITY(invitation.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId())
            ;
        }

        $items = [];
        $registered = [];

        foreach ($queryBuilder->getQuery()->getResult() as $invitation) {
            if (!$invitation instanceof CSurveyInvitation) {
                continue;
            }

            $survey = $invitation->getSurvey();
            if (null === $survey->getIid()) {
                continue;
            }

            if (!$this->surveyMatchesSearch($survey, $search)) {
                continue;
            }

            $surveyId = (int) $survey->getIid();
            if (isset($registered[$surveyId])) {
                continue;
            }

            $registered[$surveyId] = true;
            $items[] = $this->normalizeLearnerSurvey($survey, $invitation, $course, $session);
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeLearnerSurvey(
        CSurvey $survey,
        CSurveyInvitation $invitation,
        Course $course,
        ?Session $session
    ): array {
        $isAnswered = 1 === (int) $invitation->getAnswered();
        $isMeetingPoll = 3 === $survey->getSurveyType();
        $isUnsupportedPersonality = $this->isUnsupportedPersonalitySurvey($survey);

        return [
            '@id' => '/api/surveys/'.(int) $survey->getIid(),
            '@type' => 'Survey',
            'iid' => (int) $survey->getIid(),
            'code' => $survey->getCode(),
            'title' => $survey->getTitle(),
            'subtitle' => $survey->getSubtitle(),
            'language' => $survey->getLang(),
            'availableFrom' => $this->formatDate($survey->getAvailFrom()),
            'availableUntil' => $this->formatDate($survey->getAvailTill()),
            'availabilityStatus' => $this->getAvailabilityStatus($survey),
            'anonymous' => $this->isTruthy($survey->getAnonymous()),
            'invited' => $survey->getInvited(),
            'answered' => $survey->getAnswered(),
            'questionCount' => null,
            'surveyType' => $survey->getSurveyType(),
            'surveyTypeLabel' => $this->getSurveyTypeLabel($survey->getSurveyType()),
            'shuffle' => $survey->getShuffle(),
            'oneQuestionPerPage' => $survey->getOneQuestionPerPage(),
            'visibleResults' => $survey->getVisibleResults(),
            'mandatory' => $survey->isMandatory(),
            'visible' => true,
            'canEdit' => false,
            'canConfigure' => false,
            'canCopy' => false,
            'canDuplicate' => false,
            'canMultiplicate' => false,
            'canSendToTutors' => false,
            'canEmpty' => false,
            'canDelete' => false,
            'canInvite' => false,
            'actionCsrfToken' => '',
            'canPreview' => false,
            'canReport' => false,
            'canAnswer' => !$isUnsupportedPersonality && (!$isAnswered || $isMeetingPoll),
            'isUnsupportedPersonality' => $isUnsupportedPersonality,
            'unsupportedReason' => $isUnsupportedPersonality ? $this->getUnsupportedPersonalitySurveyMessage() : '',
            'answerUrl' => $this->buildAnswerUrl($survey, $invitation, $course, $session),
            'invitationCode' => $invitation->getInvitationCode(),
            'invitationAnswered' => $isAnswered,
        ];
    }

    private function buildAnswerUrl(CSurvey $survey, CSurveyInvitation $invitation, Course $course, ?Session $session): string
    {
        $nodeId = method_exists($survey, 'getResourceNode') && null !== $survey->getResourceNode()
            ? (int) $survey->getResourceNode()->getId()
            : (int) $course->getId();
        $route = 3 === $survey->getSurveyType() ? 'meeting' : 'answer';

        return \sprintf(
            '/resources/survey/%d/%d/%s?%s',
            $nodeId,
            (int) $survey->getIid(),
            $route,
            http_build_query([
                'cid' => (int) $course->getId(),
                'sid' => (int) ($session?->getId() ?? 0),
                'invitationCode' => $invitation->getInvitationCode(),
            ]),
        );
    }

    private function hasSurveyGroupForTutors(CSurvey $survey, Course $course): bool
    {
        $groupId = $this->getSurveyExtraFieldIntegerValue((int) $survey->getIid(), 'group_id');
        if (null === $groupId) {
            return false;
        }

        $group = $this->entityManager->getRepository(CGroup::class)->find($groupId);
        if (!$group instanceof CGroup) {
            return false;
        }

        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(relation.iid)')
            ->from(CGroupRelTutor::class, 'relation')
            ->andWhere('relation.cId = :courseId')
            ->andWhere('relation.group = :group')
            ->setParameter('courseId', (int) $course->getId())
            ->setParameter('group', (int) $group->getIid())
            ->getQuery()
            ->getSingleScalarResult() > 0
        ;
    }

    private function getSurveyExtraFieldIntegerValue(int $surveyId, string $variable): ?int
    {
        $legacyValue = $this->getLegacyExtraFieldValue('survey', $surveyId, $variable);
        if (null !== $legacyValue && (int) $legacyValue > 0) {
            return (int) $legacyValue;
        }

        $connection = $this->entityManager->getConnection();

        try {
            $value = $connection->fetchOne(
                'SELECT efv.field_value
                   FROM extra_field_values efv
             INNER JOIN extra_field ef ON ef.id = efv.field_id
                  WHERE ef.variable = :variable
                    AND efv.item_id = :itemId
               ORDER BY efv.id DESC
                  LIMIT 1',
                [
                    'variable' => $variable,
                    'itemId' => $surveyId,
                ],
            );
        } catch (Throwable) {
            return null;
        }

        $groupId = (int) $value;

        return $groupId > 0 ? $groupId : null;
    }

    private function getLegacyExtraFieldValue(string $itemType, int $itemId, string $variable): mixed
    {
        if (!class_exists('ExtraFieldValue')) {
            return null;
        }

        try {
            $extraFieldValue = new ExtraFieldValue($itemType);
            $value = $extraFieldValue->get_values_by_handler_and_field_variable($itemId, $variable);
        } catch (Throwable) {
            return null;
        }

        if (!\is_array($value)) {
            return null;
        }

        return $value['value'] ?? null;
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

    private function isTruthy(string $value): bool
    {
        return '1' === $value || 'true' === strtolower($value) || 'yes' === strtolower($value);
    }

    private function isVisible(CSurvey $survey, Course $course, ?Session $session): bool
    {
        if (!method_exists($survey, 'isVisible')) {
            return true;
        }

        return (bool) $survey->isVisible($course, $session);
    }

    private function getAvailabilityStatus(CSurvey $survey): string
    {
        $now = new DateTime();
        $availableFrom = $survey->getAvailFrom();
        $availableUntil = $survey->getAvailTill();

        if (null !== $availableFrom && $availableFrom > $now) {
            return 'not_started';
        }

        if (null !== $availableUntil && $availableUntil < $now) {
            return 'closed';
        }

        return 'open';
    }

    private function getSurveyTypeLabel(int $type): string
    {
        return match ($type) {
            1 => 'Personality test',
            3 => 'Meeting poll',
            default => 'Regular survey',
        };
    }

    private function formatDate(?DateTimeInterface $date): ?string
    {
        return $date?->format(DateTimeInterface::ATOM);
    }
}
