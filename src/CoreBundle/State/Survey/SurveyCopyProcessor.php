<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyCopy;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use DateTime;
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

/**
 * @implements ProcessorInterface<SurveyCopy, SurveyCopy>
 */
final readonly class SurveyCopyProcessor implements ProcessorInterface
{
    use SurveyPersonalitySupportTrait;
    use SurveyCsrfTokenValidationTrait;

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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SurveyCopy
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $sourceCourse = $this->getCourse($request);
        $sourceSession = $this->getSession($request);
        $user = $this->getCurrentUser();

        if (!$this->canManageSurveys()) {
            throw new AccessDeniedHttpException('You are not allowed to manage surveys in this context.');
        }

        $payload = $this->getPayload($request, $data);
        $this->validateSubmittedCsrfToken($request, $this->csrfTokenManager, SurveyActionProcessor::CSRF_TOKEN_ID, $payload);

        $surveyId = isset($uriVariables['surveyId']) ? (int) $uriVariables['surveyId'] : 0;
        if ($surveyId <= 0) {
            throw new BadRequestHttpException('A valid survey id is required.');
        }

        $sourceSurvey = $this->getSurveyFromCurrentContext($surveyId, $sourceCourse, $sourceSession);
        $this->assertPersonalitySurveySupported($sourceSurvey);
        $this->assertCanCopySurvey($sourceSurvey);

        $targetCourseId = (int) ($payload['targetCourseId'] ?? $payload['target_course_id'] ?? 0);
        $targetSessionId = (int) ($payload['targetSessionId'] ?? $payload['target_session_id'] ?? 0);
        if ($targetCourseId <= 0) {
            throw new BadRequestHttpException('A target course is required.');
        }

        if ((int) $sourceCourse->getId() === $targetCourseId && (null === $sourceSession ? 0 : (int) $sourceSession->getId()) === $targetSessionId) {
            throw new BadRequestHttpException('Use Duplicate survey to create a copy in the same course context.');
        }

        $targetCourse = $this->entityManager->getRepository(Course::class)->find($targetCourseId);
        if (!$targetCourse instanceof Course) {
            throw new BadRequestHttpException('The target course was not found.');
        }

        $targetSession = $this->getTargetSession($targetCourse, $targetSessionId);
        if (!$this->canCopyToTarget($user, $targetCourse, $targetSession)) {
            throw new AccessDeniedHttpException('You are not allowed to copy this survey to the selected target.');
        }

        $appendCopyToTitle = (int) $sourceCourse->getId() === (int) $targetCourse->getId();
        $newSurvey = $this->copySurvey($sourceSurvey, $targetCourse, $targetSession, $appendCopyToTitle);
        $this->entityManager->flush();

        $response = new SurveyCopy();
        $response->surveyId = $surveyId;
        $response->success = true;
        $response->canCopy = true;
        $response->newSurveyId = (int) $newSurvey->getIid();
        $response->message = 'Survey copied.';

        return $response;
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

    private function getTargetSession(Course $targetCourse, int $targetSessionId): ?Session
    {
        if ($targetSessionId <= 0) {
            return null;
        }

        $targetSession = $this->entityManager->getRepository(Session::class)->find($targetSessionId);
        if (!$targetSession instanceof Session) {
            throw new BadRequestHttpException('The target session was not found.');
        }

        $exists = (int) $this->entityManager->getConnection()->fetchOne(
            'SELECT COUNT(1) FROM session_rel_course WHERE session_id = :sessionId AND c_id = :courseId',
            [
                'sessionId' => $targetSessionId,
                'courseId' => (int) $targetCourse->getId(),
            ],
        );
        if ($exists <= 0) {
            throw new BadRequestHttpException('The target session does not contain the selected course.');
        }

        return $targetSession;
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
                ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
            ;

            if (null !== $queryBuilder->getQuery()->getOneOrNullResult()) {
                return true;
            }
        }

        return false;
    }

    private function assertCanCopySurvey(CSurvey $survey): void
    {
        if (3 === $survey->getSurveyType()) {
            throw new BadRequestHttpException('Meeting polls must be managed from the meeting poll view.');
        }

        if ($this->isSurveyEditionHidden($survey)) {
            throw new AccessDeniedHttpException('This survey cannot be copied because edition is disabled by configuration.');
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

        if ($data instanceof SurveyCopy) {
            return [
                'csrfToken' => $data->csrfToken,
                'targetCourseId' => $data->targetCourseId,
                'targetSessionId' => $data->targetSessionId,
            ];
        }

        return [];
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(SurveyActionProcessor::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }

    private function canCopyToTarget(User $user, Course $targetCourse, ?Session $targetSession): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($this->isTargetCourseTeacher((int) $user->getId(), (int) $targetCourse->getId())) {
            return true;
        }

        if (null === $targetSession) {
            return false;
        }

        return $this->isTargetSessionCourseCoach(
            (int) $user->getId(),
            (int) $targetCourse->getId(),
            (int) $targetSession->getId(),
        );
    }

    private function isTargetCourseTeacher(int $userId, int $courseId): bool
    {
        return (int) $this->entityManager->getConnection()->fetchOne(
            'SELECT COUNT(1) FROM course_rel_user WHERE user_id = :userId AND c_id = :courseId AND status = :status',
            [
                'userId' => $userId,
                'courseId' => $courseId,
                'status' => $this->getCourseManagerStatus(),
            ],
        ) > 0;
    }

    private function isTargetSessionCourseCoach(int $userId, int $courseId, int $sessionId): bool
    {
        return (int) $this->entityManager->getConnection()->fetchOne(
            'SELECT COUNT(1) FROM session_rel_course_rel_user WHERE user_id = :userId AND c_id = :courseId AND session_id = :sessionId AND status = :status',
            [
                'userId' => $userId,
                'courseId' => $courseId,
                'sessionId' => $sessionId,
                'status' => Session::COURSE_COACH,
            ],
        ) > 0;
    }

    private function copySurvey(CSurvey $source, Course $targetCourse, ?Session $targetSession, bool $appendCopyToTitle): CSurvey
    {
        $title = $source->getTitle();
        if ($appendCopyToTitle) {
            $title .= ' Copy';
        }

        $survey = new CSurvey();
        $survey
            ->setCode($this->generateUniqueCode((string) $source->getCode(), (string) $source->getLang()))
            ->setTitle($title)
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
            ->setParent($targetCourse)
            ->addCourseLink($targetCourse, $targetSession)
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

    private function getCourseManagerStatus(): int
    {
        return \defined('COURSEMANAGER') ? (int) \constant('COURSEMANAGER') : 1;
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
