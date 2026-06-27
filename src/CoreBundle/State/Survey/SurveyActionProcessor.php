<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyAction;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CGroupRelTutor;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyAnswer;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use ExtraFieldValue;
use MessageManager;
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
 * @implements ProcessorInterface<SurveyAction, SurveyAction>
 */
final readonly class SurveyActionProcessor implements ProcessorInterface
{
    use SurveyPersonalitySupportTrait;
    use SurveyCsrfTokenValidationTrait;

    public const CSRF_TOKEN_ID = 'survey_action';

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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SurveyAction
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

        $payload = $this->getPayload($request, $data);
        $this->validateSubmittedCsrfToken($request, $this->csrfTokenManager, self::CSRF_TOKEN_ID, $payload);

        $operationName = (string) $operation->getName();
        if ('post_survey_action_bulk_delete' === $operationName) {
            $surveyIds = $this->getNormalizedSurveyIds($payload);
            $deletedCount = $this->bulkDeleteSurveys($surveyIds, $course, $session);

            $response = new SurveyAction();
            $response->surveyId = reset($surveyIds) ?: null;
            $response->success = true;
            $response->deletedCount = $deletedCount;
            $response->message = 'Selected surveys deleted.';
            $this->entityManager->flush();

            return $response;
        }

        $surveyId = isset($uriVariables['surveyId']) ? (int) $uriVariables['surveyId'] : 0;
        if ($surveyId <= 0) {
            throw new BadRequestHttpException('A valid survey id is required.');
        }

        $survey = $this->getSurveyFromCurrentContext($surveyId, $course, $session);
        if ('post_survey_action_delete' !== $operationName) {
            $this->assertPersonalitySurveySupported($survey);
        }
        $this->assertCanWriteSurvey($survey);

        $response = new SurveyAction();
        $response->surveyId = $surveyId;
        $response->success = true;

        if ('post_survey_action_duplicate' === $operationName) {
            $newSurvey = $this->duplicateSurvey($survey, $course, $session);
            $response->newSurveyId = (int) $newSurvey->getIid();
            $response->message = 'Survey duplicated.';
            $this->entityManager->flush();

            return $response;
        }

        if ('post_survey_action_empty' === $operationName) {
            $this->emptySurvey($survey, $session);
            $response->message = 'Survey answers deleted.';
            $this->entityManager->flush();

            return $response;
        }

        if ('post_survey_action_multiplicate' === $operationName) {
            $count = $this->multiplicateQuestions($survey, $course);
            $response->message = 'Updated';
            $this->entityManager->flush();

            return $response;
        }

        if ('post_survey_action_remove_multiplicate' === $operationName) {
            $count = $this->removeMultiplicatedQuestions($survey);
            $response->message = 'Updated';
            $this->entityManager->flush();

            return $response;
        }

        if ('post_survey_action_send_to_tutors' === $operationName) {
            $result = $this->sendToTutors($survey, $course, $session);
            $response->message = \sprintf(
                'The invitation has been sent. Created: %d. Messages sent: %d.',
                $result['created'],
                $result['sent'],
            );
            $this->entityManager->flush();

            return $response;
        }

        if ('post_survey_action_delete' === $operationName) {
            $this->deleteSurvey($survey);
            $response->message = 'Survey deleted.';
            $this->entityManager->flush();

            return $response;
        }

        throw new BadRequestHttpException('Unsupported survey action.');
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
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

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
                ->setParameter('surveyId', (int) $survey->getIid())
            ;

            if (null !== $queryBuilder->getQuery()->getOneOrNullResult()) {
                return true;
            }
        }

        return false;
    }

    private function assertCanWriteSurvey(CSurvey $survey): void
    {
        if ($this->isSurveyEditionHidden($survey)) {
            throw new AccessDeniedHttpException('This survey cannot be edited because edition is disabled by configuration.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getPayload(Request $request, mixed $data): array
    {
        $content = trim($request->getContent());
        if ('' !== $content) {
            $payload = json_decode($content, true);
            if (!\is_array($payload)) {
                throw new BadRequestHttpException('The request payload is invalid.');
            }

            return $payload;
        }

        if ($data instanceof SurveyAction) {
            return [
                'csrfToken' => $data->csrfToken,
                'surveyIds' => $data->surveyIds,
            ];
        }

        return [];
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }

    private function duplicateSurvey(CSurvey $source, Course $course, ?Session $session): CSurvey
    {
        if (3 === $source->getSurveyType()) {
            throw new BadRequestHttpException('Meeting polls must be managed from the meeting poll view.');
        }

        $survey = new CSurvey();
        $survey
            ->setCode($this->generateUniqueCode((string) $source->getCode(), (string) $source->getLang()))
            ->setTitle($source->getTitle().' Copy')
            ->setSubtitle((string) $source->getSubtitle())
            ->setLang((string) $source->getLang())
            ->setAvailFrom($this->cloneDate($source->getAvailFrom()))
            ->setAvailTill($this->cloneDate($source->getAvailTill()))
            ->setIsShared((string) ($source->getIsShared() ?? '0'))
            ->setTemplate((string) ($source->getTemplate() ?? 'template'))
            ->setIntro((string) $source->getIntro())
            ->setSurveythanks((string) $source->getSurveythanks())
            ->setInviteMail((string) $source->getInviteMail())
            ->setReminderMail((string) $source->getReminderMail())
            ->setMailSubject((string) $source->getMailSubject())
            ->setAnonymous((string) $source->getAnonymous())
            ->setShuffle($source->getShuffle())
            ->setOneQuestionPerPage($source->getOneQuestionPerPage())
            ->setSurveyVersion((string) $source->getSurveyVersion())
            ->setSurveyType($source->getSurveyType())
            ->setShowFormProfile($source->getShowFormProfile())
            ->setFormFields((string) $source->getFormFields())
            ->setVisibleResults((int) ($source->getVisibleResults() ?? 0))
            ->setIsMandatory($source->isMandatory())
            ->setDisplayQuestionNumber($source->isDisplayQuestionNumber())
            ->setDuration($source->getDuration())
            ->setInvited(0)
            ->setAnswered(0)
            ->setSurveyParent($source->getSurveyParent())
            ->setParent($course)
            ->addCourseLink($course, $session)
        ;

        $this->surveyRepository->create($survey);
        $this->entityManager->flush();
        $this->copyQuestions($source, $survey);

        return $survey;
    }

    private function cloneDate(?DateTime $date): DateTime
    {
        if (null === $date) {
            return new DateTime();
        }

        return clone $date;
    }

    private function generateUniqueCode(string $sourceCode, string $language): string
    {
        $baseCode = trim($sourceCode);
        if ('' === $baseCode) {
            $baseCode = 'survey';
        }

        $baseCode = substr($baseCode, 0, 33);
        $candidate = $baseCode.'-copy';
        $counter = 2;

        while ($this->surveyCodeExists($candidate, $language)) {
            $suffix = '-copy-'.$counter;
            $candidate = substr($baseCode, 0, 40 - \strlen($suffix)).$suffix;
            $counter++;
        }

        return $candidate;
    }

    private function surveyCodeExists(string $code, string $language): bool
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(survey.iid)')
            ->from(CSurvey::class, 'survey')
            ->andWhere('survey.code = :code')
            ->andWhere('survey.lang = :language')
            ->setParameter('code', $code)
            ->setParameter('language', $language)
            ->getQuery()
            ->getSingleScalarResult() > 0
        ;
    }

    private function copyQuestions(CSurvey $sourceSurvey, CSurvey $targetSurvey): void
    {
        $questionMap = [];
        $optionMap = [];

        foreach ($this->getOrderedQuestions($sourceSurvey) as $sourceQuestion) {
            $question = new CSurveyQuestion();
            $question
                ->setSurvey($targetSurvey)
                ->setSurveyQuestion($sourceQuestion->getSurveyQuestion())
                ->setSurveyQuestionComment((string) $sourceQuestion->getSurveyQuestionComment())
                ->setType($sourceQuestion->getType())
                ->setDisplay($sourceQuestion->getDisplay())
                ->setSort($sourceQuestion->getSort())
                ->setSharedQuestionId((int) ($sourceQuestion->getSharedQuestionId() ?? 0))
                ->setMaxValue((int) ($sourceQuestion->getMaxValue() ?? 0))
                ->setSurveyGroupPri($sourceQuestion->getSurveyGroupPri())
                ->setSurveyGroupSec1($sourceQuestion->getSurveyGroupSec1())
                ->setSurveyGroupSec2($sourceQuestion->getSurveyGroupSec2())
                ->setIsMandatory($sourceQuestion->isMandatory())
            ;

            $this->entityManager->persist($question);
            $this->entityManager->flush();

            if (null !== $sourceQuestion->getIid()) {
                $questionMap[(int) $sourceQuestion->getIid()] = $question;
            }

            foreach ($sourceQuestion->getOptions() as $sourceOption) {
                if (!$sourceOption instanceof CSurveyQuestionOption) {
                    continue;
                }

                $option = new CSurveyQuestionOption();
                $option
                    ->setSurvey($targetSurvey)
                    ->setQuestion($question)
                    ->setOptionText($sourceOption->getOptionText())
                    ->setSort($sourceOption->getSort())
                    ->setValue($sourceOption->getValue())
                ;
                $this->entityManager->persist($option);
                $this->entityManager->flush();

                if (null !== $sourceOption->getIid()) {
                    $optionMap[(int) $sourceOption->getIid()] = $option;
                }
            }
        }

        foreach ($this->getOrderedQuestions($sourceSurvey) as $sourceQuestion) {
            if (null === $sourceQuestion->getIid()) {
                continue;
            }

            $question = $questionMap[(int) $sourceQuestion->getIid()] ?? null;
            if (!$question instanceof CSurveyQuestion) {
                continue;
            }

            $sourceParent = $sourceQuestion->getParent();
            if ($sourceParent instanceof CSurveyQuestion && null !== $sourceParent->getIid()) {
                $question->setParent($questionMap[(int) $sourceParent->getIid()] ?? null);
            }

            $sourceParentOption = $sourceQuestion->getParentOption();
            if ($sourceParentOption instanceof CSurveyQuestionOption && null !== $sourceParentOption->getIid()) {
                $question->setParentOption($optionMap[(int) $sourceParentOption->getIid()] ?? null);
            }

            $this->entityManager->persist($question);
        }
    }

    /**
     * @return array<int, CSurveyQuestion>
     */
    private function getOrderedQuestions(CSurvey $survey): array
    {
        return $this->entityManager->getRepository(CSurveyQuestion::class)->findBy(
            ['survey' => $survey],
            ['sort' => 'ASC'],
        );
    }

    private function multiplicateQuestions(CSurvey $survey, Course $course): int
    {
        if (3 === $survey->getSurveyType()) {
            throw new BadRequestHttpException('Meeting polls must be managed from the meeting poll view.');
        }

        $sourceQuestions = $this->getMultiplicableSourceQuestions($survey);
        if ([] === $sourceQuestions) {
            throw new BadRequestHttpException('This survey does not contain questions with {{class_name}} or {{student_full_name}} placeholders.');
        }

        $groups = $this->getCourseUserGroups($course);
        if ([] === $groups) {
            throw new BadRequestHttpException('No class or group users were found for this course.');
        }

        $nextSort = $this->getNextQuestionSort($survey);
        $generated = 0;
        $lastGroupIndex = \count($groups) - 1;

        foreach ($groups as $groupIndex => $group) {
            $groupGenerated = 0;
            foreach ($sourceQuestions as $sourceQuestion) {
                $questionText = $sourceQuestion->getSurveyQuestion();
                $hasClassTag = str_contains($questionText, '{{class_name}}');
                $hasStudentTag = str_contains($questionText, '{{student_full_name}}');

                if (!$hasClassTag && !$hasStudentTag) {
                    continue;
                }

                if ($hasStudentTag) {
                    foreach ($group['users'] as $user) {
                        $this->createGeneratedQuestion(
                            $survey,
                            $sourceQuestion,
                            [
                                '{{class_name}}' => $group['name'],
                                '{{student_full_name}}' => $user->getFullName(),
                            ],
                            $nextSort++
                        );
                        $generated++;
                        $groupGenerated++;
                    }

                    continue;
                }

                $this->createGeneratedQuestion(
                    $survey,
                    $sourceQuestion,
                    ['{{class_name}}' => $group['name']],
                    $nextSort++
                );
                $generated++;
                $groupGenerated++;
            }

            if ($groupGenerated > 0 && $groupIndex < $lastGroupIndex) {
                $this->createGeneratedPageBreak($survey, $nextSort++);
                $generated++;
            }
        }

        if (0 === $generated) {
            throw new BadRequestHttpException('No multiplied questions were generated.');
        }

        return $generated;
    }

    /**
     * @return array<int, CSurveyQuestion>
     */
    private function getMultiplicableSourceQuestions(CSurvey $survey): array
    {
        $questions = $this->entityManager->getRepository(CSurveyQuestion::class)->findBy(
            ['survey' => $survey],
            ['sort' => 'ASC'],
        );

        $sourceQuestions = [];
        foreach ($questions as $question) {
            if (!$question instanceof CSurveyQuestion) {
                continue;
            }

            if ('generated' === (string) $question->getSurveyQuestionComment()) {
                continue;
            }

            $text = $question->getSurveyQuestion();
            if (str_contains($text, '{{class_name}}') || str_contains($text, '{{student_full_name}}')) {
                $sourceQuestions[] = $question;
            }
        }

        return $sourceQuestions;
    }

    /**
     * @return array<int, array{name: string, users: array<int, User>}>
     */
    private function getCourseUserGroups(Course $course): array
    {
        $groups = $this->getCourseUsergroupsFromUsergroupTables((int) $course->getId());
        if ([] !== $groups) {
            return $groups;
        }

        return $this->getCourseGroupsFromCourseGroupTables((int) $course->getId());
    }

    /**
     * @return array<int, array{name: string, users: array<int, User>}>
     */
    private function getCourseUsergroupsFromUsergroupTables(int $courseId): array
    {
        $connection = $this->entityManager->getConnection();

        try {
            $rows = $connection->fetchAllAssociative(
                'SELECT ug.id AS group_id, ug.title AS group_title, uru.user_id AS user_id
                   FROM usergroup ug
             INNER JOIN usergroup_rel_course urc ON urc.usergroup_id = ug.id
             INNER JOIN usergroup_rel_user uru ON uru.usergroup_id = ug.id
                  WHERE urc.course_id = :courseId
               ORDER BY ug.title ASC, uru.user_id ASC',
                ['courseId' => $courseId],
            );
        } catch (Throwable) {
            return [];
        }

        return $this->buildUserGroupListFromRows($rows);
    }

    /**
     * @return array<int, array{name: string, users: array<int, User>}>
     */
    private function getCourseGroupsFromCourseGroupTables(int $courseId): array
    {
        $relations = $this->entityManager->createQueryBuilder()
            ->select('relation', 'courseGroup', 'user')
            ->from(CGroupRelUser::class, 'relation')
            ->innerJoin('relation.group', 'courseGroup')
            ->innerJoin('relation.user', 'user')
            ->andWhere('relation.cId = :courseId')
            ->orderBy('courseGroup.title', 'ASC')
            ->addOrderBy('user.lastname', 'ASC')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        $groups = [];
        foreach ($relations as $relation) {
            if (!$relation instanceof CGroupRelUser) {
                continue;
            }

            $group = $relation->getGroup();
            $user = $relation->getUser();
            if (null === $group->getIid() || !$user instanceof User) {
                continue;
            }

            $groupId = (int) $group->getIid();
            if (!isset($groups[$groupId])) {
                $groups[$groupId] = [
                    'name' => $group->getTitle(),
                    'users' => [],
                ];
            }
            $groups[$groupId]['users'][(int) $user->getId()] = $user;
        }

        return $this->normalizeUserGroupList($groups);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, array{name: string, users: array<int, User>}>
     */
    private function buildUserGroupListFromRows(array $rows): array
    {
        $groups = [];
        $userRepository = $this->entityManager->getRepository(User::class);

        foreach ($rows as $row) {
            $groupId = (int) ($row['group_id'] ?? 0);
            $userId = (int) ($row['user_id'] ?? 0);
            if ($groupId <= 0 || $userId <= 0) {
                continue;
            }

            $user = $userRepository->find($userId);
            if (!$user instanceof User) {
                continue;
            }

            if (!isset($groups[$groupId])) {
                $groups[$groupId] = [
                    'name' => (string) ($row['group_title'] ?? 'Group'),
                    'users' => [],
                ];
            }
            $groups[$groupId]['users'][$userId] = $user;
        }

        return $this->normalizeUserGroupList($groups);
    }

    /**
     * @param array<int, array{name: string, users: array<int, User>}> $groups
     *
     * @return array<int, array{name: string, users: array<int, User>}>
     */
    private function normalizeUserGroupList(array $groups): array
    {
        $orderByName = $this->isSettingEnabled('survey.survey_duplicate_order_by_name');
        $normalized = [];

        foreach ($groups as $group) {
            if (empty($group['users'])) {
                continue;
            }

            $users = array_values($group['users']);
            if ($orderByName) {
                usort(
                    $users,
                    static fn (User $first, User $second): int => strcmp(
                        (string) $first->getLastname().' '.(string) $first->getFirstname(),
                        (string) $second->getLastname().' '.(string) $second->getFirstname(),
                    ),
                );
            }

            $normalized[] = [
                'name' => (string) $group['name'],
                'users' => $users,
            ];
        }

        return $normalized;
    }

    private function createGeneratedQuestion(
        CSurvey $survey,
        CSurveyQuestion $sourceQuestion,
        array $replacements,
        int $sort
    ): CSurveyQuestion {
        $question = new CSurveyQuestion();
        $question
            ->setSurvey($survey)
            ->setSurveyQuestion(strtr($sourceQuestion->getSurveyQuestion(), $replacements))
            ->setSurveyQuestionComment('generated')
            ->setType($sourceQuestion->getType())
            ->setDisplay($sourceQuestion->getDisplay())
            ->setSort($sort)
            ->setSharedQuestionId((int) ($sourceQuestion->getSharedQuestionId() ?? 0))
            ->setMaxValue((int) ($sourceQuestion->getMaxValue() ?? 0))
            ->setSurveyGroupPri($sourceQuestion->getSurveyGroupPri())
            ->setSurveyGroupSec1($sourceQuestion->getSurveyGroupSec1())
            ->setSurveyGroupSec2($sourceQuestion->getSurveyGroupSec2())
            ->setIsMandatory($sourceQuestion->isMandatory())
        ;

        $this->entityManager->persist($question);
        $this->entityManager->flush();

        foreach ($sourceQuestion->getOptions() as $sourceOption) {
            if (!$sourceOption instanceof CSurveyQuestionOption) {
                continue;
            }

            $option = new CSurveyQuestionOption();
            $option
                ->setSurvey($survey)
                ->setQuestion($question)
                ->setOptionText(strtr($sourceOption->getOptionText(), $replacements))
                ->setSort($sourceOption->getSort())
                ->setValue($sourceOption->getValue())
            ;
            $this->entityManager->persist($option);
        }

        return $question;
    }

    private function createGeneratedPageBreak(CSurvey $survey, int $sort): CSurveyQuestion
    {
        $question = new CSurveyQuestion();
        $question
            ->setSurvey($survey)
            ->setSurveyQuestion('Question for next class')
            ->setSurveyQuestionComment('generated')
            ->setType('pagebreak')
            ->setDisplay('horizontal')
            ->setSort($sort)
            ->setSharedQuestionId(0)
            ->setMaxValue(0)
            ->setSurveyGroupPri(0)
            ->setSurveyGroupSec1(0)
            ->setSurveyGroupSec2(0)
            ->setIsMandatory(false)
        ;
        $this->entityManager->persist($question);

        return $question;
    }

    private function getNextQuestionSort(CSurvey $survey): int
    {
        $maxSort = $this->entityManager->createQueryBuilder()
            ->select('MAX(question.sort)')
            ->from(CSurveyQuestion::class, 'question')
            ->andWhere('question.survey = :survey')
            ->setParameter('survey', (int) $survey->getIid())
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return ((int) $maxSort) + 1;
    }

    private function removeMultiplicatedQuestions(CSurvey $survey): int
    {
        $questions = $this->entityManager->getRepository(CSurveyQuestion::class)->findBy([
            'survey' => $survey,
            'surveyQuestionComment' => 'generated',
        ]);

        $count = 0;
        foreach ($questions as $question) {
            if (!$question instanceof CSurveyQuestion) {
                continue;
            }

            $this->entityManager->remove($question);
            $count++;
        }

        return $count;
    }

    /**
     * @return array{created: int, sent: int}
     */
    private function sendToTutors(CSurvey $survey, Course $course, ?Session $session): array
    {
        if (3 === $survey->getSurveyType()) {
            throw new BadRequestHttpException('Meeting polls must be managed from the meeting poll view.');
        }

        $groupId = $this->getSurveyExtraFieldIntegerValue((int) $survey->getIid(), 'group_id');
        if (null === $groupId) {
            throw new BadRequestHttpException('This survey is not linked to a group.');
        }

        $group = $this->entityManager->getRepository(CGroup::class)->find($groupId);
        if (!$group instanceof CGroup) {
            throw new BadRequestHttpException('The linked group was not found.');
        }

        $tutors = $this->getGroupTutors($course, $group);
        if ([] === $tutors) {
            throw new BadRequestHttpException('No group tutors were found for this survey.');
        }

        $survey
            ->setInviteMail(' ')
            ->setMailSubject(' ')
        ;

        $existingInvitations = $this->getExistingInvitationsByUser($survey, $course, $session);
        $created = 0;
        $sent = 0;

        foreach ($tutors as $tutor) {
            $userId = $tutor->getId();
            if (null === $userId) {
                continue;
            }

            $invitation = $existingInvitations[(int) $userId] ?? null;
            $isNewInvitation = !$invitation instanceof CSurveyInvitation;
            if ($isNewInvitation) {
                $invitation = $this->createInvitation($tutor, $survey, $course, $session, $group);
                $existingInvitations[(int) $userId] = $invitation;
                ++$created;
            } elseif (null === $invitation->getGroup()) {
                $invitation->setGroup($group);
            }

            if ($isNewInvitation && $this->sendTutorInvitationMessage($invitation, $survey, $course, $session, $group)) {
                ++$sent;
            }
        }

        $this->updateSurveyCounters($survey, $course, $session);

        return [
            'created' => $created,
            'sent' => $sent,
        ];
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

    /**
     * @return array<int, User>
     */
    private function getGroupTutors(Course $course, CGroup $group): array
    {
        $relations = $this->entityManager->createQueryBuilder()
            ->select('relation', 'user')
            ->from(CGroupRelTutor::class, 'relation')
            ->innerJoin('relation.user', 'user')
            ->andWhere('relation.cId = :courseId')
            ->andWhere('relation.group = :group')
            ->andWhere('user.active = :active')
            ->orderBy('user.lastname', 'ASC')
            ->addOrderBy('user.firstname', 'ASC')
            ->setParameter('courseId', (int) $course->getId())
            ->setParameter('group', (int) $group->getIid())
            ->setParameter('active', User::ACTIVE, Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        $tutors = [];
        foreach ($relations as $relation) {
            if (!$relation instanceof CGroupRelTutor) {
                continue;
            }

            $user = $relation->getUser();
            if (!$user instanceof User || null === $user->getId()) {
                continue;
            }

            $tutors[(int) $user->getId()] = $user;
        }

        return array_values($tutors);
    }

    /**
     * @return array<int, CSurveyInvitation>
     */
    private function getExistingInvitationsByUser(CSurvey $survey, Course $course, ?Session $session): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('invitation', 'user')
            ->from(CSurveyInvitation::class, 'invitation')
            ->innerJoin('invitation.user', 'user')
            ->andWhere('invitation.survey = :survey')
            ->andWhere('invitation.course = :course')
            ->setParameter('survey', (int) $survey->getIid())
            ->setParameter('course', (int) $course->getId())
        ;

        if (null === $session) {
            $queryBuilder->andWhere('invitation.session IS NULL');
        } else {
            $queryBuilder
                ->andWhere('invitation.session = :session')
                ->setParameter('session', (int) $session->getId())
            ;
        }

        $items = [];
        foreach ($queryBuilder->getQuery()->getResult() as $invitation) {
            if (!$invitation instanceof CSurveyInvitation) {
                continue;
            }

            $user = $invitation->getUser();
            if (null !== $user->getId()) {
                $items[(int) $user->getId()] = $invitation;
            }
        }

        return $items;
    }

    private function createInvitation(
        User $user,
        CSurvey $survey,
        Course $course,
        ?Session $session,
        CGroup $group,
    ): CSurveyInvitation {
        $invitation = new CSurveyInvitation();
        $invitation
            ->setUser($user)
            ->setSurvey($survey)
            ->setCourse($course)
            ->setSession($session)
            ->setGroup($group)
            ->setInvitationCode($this->generateInvitationCode((int) $user->getId()))
            ->setInvitationDate(new DateTime())
            ->setReminderDate(new DateTime())
            ->setAnswered(0)
            ->setLpItemId(0)
        ;

        $this->entityManager->persist($invitation);

        return $invitation;
    }

    private function generateInvitationCode(int $userId): string
    {
        return md5($userId.microtime(true).bin2hex(random_bytes(8)));
    }

    private function sendTutorInvitationMessage(
        CSurveyInvitation $invitation,
        CSurvey $survey,
        Course $course,
        ?Session $session,
        CGroup $group,
    ): bool {
        if (!class_exists('MessageManager')) {
            return false;
        }

        $user = $invitation->getUser();
        $userId = $user->getId();
        if (null === $userId) {
            return false;
        }

        $subject = \sprintf('Group survey for %s', $group->getTitle());
        $content = \sprintf(
            'Hi %s <br/><br/>As group tutor for the group %s you are invited to participate at the following survey:',
            $user->getFullName(),
            $group->getTitle(),
        );

        $link = $this->buildModernAnswerLink($survey, $invitation->getInvitationCode(), $course, $session);
        $body = $this->buildMessageBody($content, $link);

        try {
            $messageId = MessageManager::send_message_simple((int) $userId, $subject, $body);
        } catch (Throwable) {
            return false;
        }

        return false !== $messageId && (int) $messageId > 0;
    }

    private function buildMessageBody(string $content, string $link): string
    {
        $linkHtml = '<a href="'.$link.'">Click here to answer the survey</a><br><br>'.$link;
        $replaceCount = 0;
        $body = str_ireplace('**link**', $linkHtml, $content, $replaceCount);

        if ($replaceCount < 1) {
            $body .= '<br><br>'.$linkHtml;
        }

        return $body;
    }

    private function buildModernAnswerLink(CSurvey $survey, string $invitationCode, Course $course, ?Session $session): string
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
                'invitationCode' => $invitationCode,
            ]),
        );
    }

    private function updateSurveyCounters(CSurvey $survey, Course $course, ?Session $session): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('COUNT(invitation.iid) AS invitedCount')
            ->addSelect('SUM(CASE WHEN invitation.answered = 1 THEN 1 ELSE 0 END) AS answeredCount')
            ->from(CSurveyInvitation::class, 'invitation')
            ->andWhere('IDENTITY(invitation.survey) = :surveyId')
            ->andWhere('IDENTITY(invitation.course) = :courseId')
            ->setParameter('surveyId', (int) $survey->getIid())
            ->setParameter('courseId', (int) $course->getId())
        ;

        if (null === $session) {
            $queryBuilder->andWhere('invitation.session IS NULL');
        } else {
            $queryBuilder
                ->andWhere('IDENTITY(invitation.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId())
            ;
        }

        $row = $queryBuilder->getQuery()->getSingleResult();
        $survey->setInvited((int) ($row['invitedCount'] ?? 0));
        $survey->setAnswered((int) ($row['answeredCount'] ?? 0));
        $this->entityManager->persist($survey);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, int>
     */
    private function getNormalizedSurveyIds(array $payload): array
    {
        $surveyIds = $payload['surveyIds'] ?? [];
        if (!\is_array($surveyIds)) {
            throw new BadRequestHttpException('The selected survey ids are invalid.');
        }

        $normalizedSurveyIds = [];
        foreach ($surveyIds as $surveyId) {
            $surveyId = (int) $surveyId;
            if ($surveyId > 0) {
                $normalizedSurveyIds[$surveyId] = $surveyId;
            }
        }

        if ([] === $normalizedSurveyIds) {
            throw new BadRequestHttpException('At least one survey must be selected.');
        }

        return $normalizedSurveyIds;
    }

    /**
     * @param array<int, int> $surveyIds
     */
    private function bulkDeleteSurveys(array $surveyIds, Course $course, ?Session $session): int
    {
        $deletedCount = 0;
        foreach ($surveyIds as $surveyId) {
            $survey = $this->getSurveyFromCurrentContext($surveyId, $course, $session);
            $this->assertCanWriteSurvey($survey);
            $this->deleteSurvey($survey);
            ++$deletedCount;
        }

        return $deletedCount;
    }

    private function emptySurvey(CSurvey $survey, ?Session $session): void
    {
        $answerQueryBuilder = $this->entityManager->createQueryBuilder()
            ->delete(CSurveyAnswer::class, 'answer')
            ->andWhere('answer.survey = :survey')
            ->setParameter('survey', (int) $survey->getIid())
        ;

        $invitationQueryBuilder = $this->entityManager->createQueryBuilder()
            ->delete(CSurveyInvitation::class, 'invitation')
            ->andWhere('invitation.survey = :survey')
            ->setParameter('survey', (int) $survey->getIid())
        ;

        if (null !== $session) {
            $answerQueryBuilder
                ->andWhere('answer.sessionId = :sessionId')
                ->setParameter('sessionId', (int) $session->getId())
            ;
            $invitationQueryBuilder
                ->andWhere('IDENTITY(invitation.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId())
            ;
        }

        $answerQueryBuilder->getQuery()->execute();
        $invitationQueryBuilder->getQuery()->execute();

        $survey
            ->setInvited(0)
            ->setAnswered(0)
        ;
        $this->entityManager->persist($survey);
    }

    private function deleteSurvey(CSurvey $survey): void
    {
        $this->emptySurvey($survey, null);
        $this->entityManager->remove($survey);
    }

    private function isSurveyEditionHidden(CSurvey $survey): bool
    {
        $value = $this->settingsManager->getSetting('survey.hide_survey_edition', true);
        if (true === $value || 'true' === $value) {
            return true;
        }

        if ('*' === $value) {
            return true;
        }

        $surveyCode = (string) $survey->getCode();
        if ('' === $surveyCode) {
            return false;
        }

        $hiddenCodes = array_filter(array_map('trim', explode(',', (string) $value)));

        return \in_array($surveyCode, $hiddenCodes, true);
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }
}
