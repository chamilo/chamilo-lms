<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyReporting;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyAnswer;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TypeError;
use ZipArchive;

use const ENT_HTML5;
use const ENT_QUOTES;

/**
 * @implements ProviderInterface<SurveyReporting>
 */
final readonly class SurveyReportingProvider implements ProviderInterface
{
    use SurveyPersonalitySupportTrait;

    private const REPORT_OVERVIEW = 'overview';
    private const REPORT_QUESTION = 'question';
    private const REPORT_USER = 'user';
    private const REPORT_COMPLETE = 'complete';

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CSurveyRepository $surveyRepository,
        private Security $security,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SurveyReporting
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $surveyId = isset($uriVariables['surveyId']) ? (int) $uriVariables['surveyId'] : 0;
        if ($surveyId <= 0) {
            throw new BadRequestHttpException('A valid survey id is required.');
        }

        $survey = $this->getSurveyFromCurrentContext($surveyId, $course, $session);
        $this->assertPersonalitySurveySupported($survey);

        return $this->buildResponse($survey, $course, $session, $request);
    }

    public function exportCsv(CSurvey $survey, Course $course, ?Session $session, Request $request, bool $compact = false): StreamedResponse
    {
        $this->assertCanViewReporting($survey);

        $filename = $this->buildSafeExportName($survey).($compact ? '_compact' : '').'.csv';

        $response = new StreamedResponse(function () use ($survey, $course, $session, $request, $compact): void {
            echo $this->buildCsvContent($survey, $course, $session, $request, $compact);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }

    public function exportXlsx(CSurvey $survey, Course $course, ?Session $session, Request $request): BinaryFileResponse
    {
        $this->assertCanViewReporting($survey);

        $filename = $this->buildSafeExportName($survey).'.xlsx';
        $spreadsheet = $this->buildCompleteSpreadsheet($survey, $course, $session, $request);
        $file = $this->writeSpreadsheetToTemporaryFile($spreadsheet);

        $response = new BinaryFileResponse($file);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);

        return $response;
    }

    public function exportByClassXlsx(CSurvey $survey, Course $course, ?Session $session, Request $request): BinaryFileResponse
    {
        $this->assertCanViewReporting($survey);

        $filename = $this->buildSafeExportName($survey).'_by_class.xlsx';
        $spreadsheet = $this->buildClassSpreadsheet($survey, $course, $session, $request);
        $file = $this->writeSpreadsheetToTemporaryFile($spreadsheet);

        $response = new BinaryFileResponse($file);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);

        return $response;
    }

    public function exportPackageZip(CSurvey $survey, Course $course, ?Session $session, Request $request): BinaryFileResponse
    {
        $this->assertCanViewReporting($survey);

        $baseName = $this->buildSafeExportName($survey);
        $zipFile = tempnam(sys_get_temp_dir(), 'survey_export_zip_');
        if (false === $zipFile) {
            throw new BadRequestHttpException('Could not create export package.');
        }

        $zip = new ZipArchive();
        if (true !== $zip->open($zipFile, ZipArchive::OVERWRITE)) {
            @unlink($zipFile);

            throw new BadRequestHttpException('Could not create export package.');
        }

        $zip->addFromString($baseName.'.csv', $this->buildCsvContent($survey, $course, $session, $request, false));
        $zip->addFromString($baseName.'_compact.csv', $this->buildCsvContent($survey, $course, $session, $request, true));

        $temporaryFiles = [];
        $xlsxFile = $this->writeSpreadsheetToTemporaryFile($this->buildCompleteSpreadsheet($survey, $course, $session, $request));
        $temporaryFiles[] = $xlsxFile;
        $zip->addFile($xlsxFile, $baseName.'.xlsx');

        $classFile = $this->writeSpreadsheetToTemporaryFile($this->buildClassSpreadsheet($survey, $course, $session, $request));
        $temporaryFiles[] = $classFile;
        $zip->addFile($classFile, $baseName.'_by_class.xlsx');

        $zip->close();

        foreach ($temporaryFiles as $temporaryFile) {
            @unlink($temporaryFile);
        }

        $response = new BinaryFileResponse($zipFile);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $baseName.'_exports.zip');
        $response->deleteFileAfterSend(true);

        return $response;
    }

    public function getCourse(Request $request): Course
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

    public function getSession(Request $request): ?Session
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

    public function getSurveyFromCurrentContext(int $surveyId, Course $course, ?Session $session): CSurvey
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

    public function buildResponse(CSurvey $survey, Course $course, ?Session $session, Request $request): SurveyReporting
    {
        $this->assertCanViewReporting($survey);

        $questions = $this->getReportableQuestions($survey);
        $optionsByQuestion = $this->getOptionsByQuestion($survey);
        $answers = $this->getAnswers($survey, $session);
        $invitations = $this->getInvitations($survey, $course, $session);
        $users = $this->buildUsers($survey, $invitations, $answers);
        $selectedUserKey = trim((string) $request->query->get('user', ''));

        $response = new SurveyReporting();
        $response->surveyId = (int) $survey->getIid();
        $response->canView = true;
        $response->canExport = true;
        $response->survey = $this->normalizeSurvey($survey, $course, $session);
        $response->settings = $this->getSettings();
        $response->counts = $this->buildCounts($survey, $invitations, $answers);
        $response->reportTypes = $this->getReportTypes();
        $response->questionReports = $this->buildQuestionReports($questions, $optionsByQuestion, $answers);
        $response->users = array_values($users);
        $response->selectedUser = $this->buildSelectedUser($selectedUserKey, $users);
        $response->userAnswers = $this->buildUserAnswers($selectedUserKey, $questions, $optionsByQuestion, $answers);
        $response->completeRows = $this->buildCompleteRows($survey, $session, $users, $questions, $optionsByQuestion, $answers);
        $response->exportUrls = $this->buildExportUrls($survey);

        return $response;
    }

    private function assertCanViewReporting(CSurvey $survey): void
    {
        if (3 === $survey->getSurveyType()) {
            throw new BadRequestHttpException('Meeting poll reporting must be opened in the meeting view.');
        }

        if ($this->isReportingHidden() && !$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Survey reporting is disabled by platform settings.');
        }

        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')) {
            return;
        }

        if ($this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER') && $this->isSettingEnabled('survey.extend_rights_for_coach_on_survey')) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to view survey reporting in this context.');
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
     * @return array<int, CSurveyQuestion>
     */
    private function getReportableQuestions(CSurvey $survey): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('question')
            ->from(CSurveyQuestion::class, 'question')
            ->andWhere('IDENTITY(question.survey) = :surveyId')
            ->andWhere('question.type != :pageBreak')
            ->orderBy('question.sort', 'ASC')
            ->addOrderBy('question.iid', 'ASC')
            ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
            ->setParameter('pageBreak', 'pagebreak')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<int, array<int, CSurveyQuestionOption>>
     */
    private function getOptionsByQuestion(CSurvey $survey): array
    {
        $options = $this->entityManager->createQueryBuilder()
            ->select('optionItem', 'question')
            ->from(CSurveyQuestionOption::class, 'optionItem')
            ->innerJoin('optionItem.question', 'question')
            ->andWhere('IDENTITY(optionItem.survey) = :surveyId')
            ->orderBy('question.sort', 'ASC')
            ->addOrderBy('optionItem.sort', 'ASC')
            ->addOrderBy('optionItem.iid', 'ASC')
            ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        $items = [];
        foreach ($options as $option) {
            if (!$option instanceof CSurveyQuestionOption) {
                continue;
            }

            $questionId = (int) $option->getQuestion()->getIid();
            $items[$questionId][] = $option;
        }

        return $items;
    }

    /**
     * @return array<int, CSurveyAnswer>
     */
    private function getAnswers(CSurvey $survey, ?Session $session): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('answer', 'question')
            ->from(CSurveyAnswer::class, 'answer')
            ->innerJoin('answer.question', 'question')
            ->andWhere('IDENTITY(answer.survey) = :surveyId')
            ->orderBy('question.sort', 'ASC')
            ->addOrderBy('answer.iid', 'ASC')
            ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
        ;

        if (null === $session) {
            $queryBuilder->andWhere('answer.sessionId IS NULL');
        } else {
            $queryBuilder
                ->andWhere('answer.sessionId = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return array<int, CSurveyInvitation>
     */
    private function getInvitations(CSurvey $survey, Course $course, ?Session $session): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('invitation', 'user')
            ->from(CSurveyInvitation::class, 'invitation')
            ->innerJoin('invitation.user', 'user')
            ->andWhere('IDENTITY(invitation.survey) = :surveyId')
            ->andWhere('IDENTITY(invitation.course) = :courseId')
            ->orderBy('user.lastname', 'ASC')
            ->addOrderBy('user.firstname', 'ASC')
            ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
        ;

        if (null === $session) {
            $queryBuilder->andWhere('invitation.session IS NULL');
        } else {
            $queryBuilder
                ->andWhere('IDENTITY(invitation.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param array<int, CSurveyInvitation> $invitations
     * @param array<int, CSurveyAnswer>     $answers
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildUsers(CSurvey $survey, array $invitations, array $answers): array
    {
        $users = [];
        $isAnonymous = $this->isAnonymous($survey);
        $anonymousIndex = 1;

        foreach ($invitations as $invitation) {
            $user = $invitation->getUser();
            $userKey = (string) $user->getId();
            $users[$userKey] = [
                'key' => $userKey,
                'label' => $isAnonymous ? 'Anonymous '.$anonymousIndex : $user->getFullNameWithUsername(),
                'answered' => 1 === (int) $invitation->getAnswered(),
                'invitationDate' => $invitation->getInvitationDate()->format('Y-m-d H:i:s'),
                'answeredAt' => $this->formatAnsweredAt($invitation),
            ];
            $anonymousIndex++;
        }

        foreach ($answers as $answer) {
            $userKey = (string) $answer->getUser();
            if (isset($users[$userKey])) {
                continue;
            }

            $users[$userKey] = [
                'key' => $userKey,
                'label' => $isAnonymous ? 'Anonymous '.$anonymousIndex : $this->resolveUserLabel($userKey),
                'answered' => true,
                'invitationDate' => null,
                'answeredAt' => null,
            ];
            $anonymousIndex++;
        }

        return $users;
    }

    private function formatAnsweredAt(CSurveyInvitation $invitation): ?string
    {
        if (1 !== (int) $invitation->getAnswered()) {
            return null;
        }

        try {
            $answeredAt = $invitation->getAnsweredAt();
        } catch (TypeError) {
            return null;
        }

        return $answeredAt->format('Y-m-d H:i:s');
    }

    private function resolveUserLabel(string $userKey): string
    {
        if (ctype_digit($userKey)) {
            $user = $this->entityManager->getRepository(User::class)->find((int) $userKey);
            if ($user instanceof User) {
                return $user->getFullNameWithUsername();
            }
        }

        return $userKey;
    }

    /**
     * @param array<int, CSurveyInvitation> $invitations
     * @param array<int, CSurveyAnswer>     $answers
     *
     * @return array<string, int>
     */
    private function buildCounts(CSurvey $survey, array $invitations, array $answers): array
    {
        $answeredUsers = [];
        foreach ($answers as $answer) {
            $answeredUsers[(string) $answer->getUser()] = true;
        }

        $invited = \count($invitations);
        $answered = \count(array_filter($invitations, static fn (CSurveyInvitation $invitation): bool => 1 === (int) $invitation->getAnswered()));

        if (0 === $answered && [] !== $answeredUsers) {
            $answered = \count($answeredUsers);
        }

        return [
            'invited' => $invited,
            'answered' => $answered,
            'pending' => max(0, $invited - $answered),
            'questions' => \count($this->getReportableQuestions($survey)),
            'answers' => \count($answers),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getReportTypes(): array
    {
        return [
            ['key' => self::REPORT_OVERVIEW, 'label' => 'Overview', 'description' => 'Summary of invitations, answers and question results.'],
            ['key' => self::REPORT_QUESTION, 'label' => 'Detailed report by question', 'description' => 'Results grouped by each survey question.'],
            ['key' => self::REPORT_USER, 'label' => 'Detailed report by user', 'description' => 'Answers submitted by a selected user.'],
            ['key' => self::REPORT_COMPLETE, 'label' => 'Complete report', 'description' => 'All answers in a table suitable for export.'],
        ];
    }

    /**
     * @param array<int, CSurveyQuestion>                   $questions
     * @param array<int, array<int, CSurveyQuestionOption>> $optionsByQuestion
     * @param array<int, CSurveyAnswer>                     $answers
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildQuestionReports(array $questions, array $optionsByQuestion, array $answers): array
    {
        $answersByQuestion = [];
        foreach ($answers as $answer) {
            $answersByQuestion[(int) $answer->getQuestion()->getIid()][] = $answer;
        }

        $reports = [];
        foreach ($questions as $question) {
            $questionId = (int) $question->getIid();
            $type = $question->getType();
            $questionAnswers = $answersByQuestion[$questionId] ?? [];
            $options = $optionsByQuestion[$questionId] ?? [];

            $reports[] = [
                'id' => $questionId,
                'title' => $this->cleanText($question->getSurveyQuestion()),
                'comment' => $this->cleanText((string) $question->getSurveyQuestionComment()),
                'type' => $type,
                'typeLabel' => $this->getQuestionTypeLabel($type),
                'totalAnswers' => $this->countQuestionAnswerUsers($questionAnswers),
                'options' => $this->buildOptionResults($question, $options, $questionAnswers),
                'textAnswers' => $this->buildTextAnswers($question, $questionAnswers),
                'scoreRows' => $this->buildScoreRows($question, $options, $questionAnswers),
            ];
        }

        return $reports;
    }

    /**
     * @param array<int, CSurveyAnswer> $answers
     */
    private function countQuestionAnswerUsers(array $answers): int
    {
        $users = [];
        foreach ($answers as $answer) {
            $users[(string) $answer->getUser()] = true;
        }

        return \count($users);
    }

    /**
     * @param array<int, CSurveyQuestionOption> $options
     * @param array<int, CSurveyAnswer>         $answers
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildOptionResults(CSurveyQuestion $question, array $options, array $answers): array
    {
        $type = $question->getType();
        if (\in_array($type, ['open', 'comment', 'score'], true)) {
            return [];
        }

        $counts = [];
        foreach ($answers as $answer) {
            $optionId = $this->getBaseOptionId($answer->getOptionId());
            if ($optionId <= 0) {
                continue;
            }

            $counts[$optionId] = ($counts[$optionId] ?? 0) + 1;
        }

        $total = array_sum($counts);
        $items = [];
        foreach ($options as $option) {
            $optionId = (int) $option->getIid();
            $count = $counts[$optionId] ?? 0;
            if ('percentage' === $type && 0 === $count) {
                continue;
            }

            $items[] = [
                'id' => $optionId,
                'label' => $this->cleanOptionText($option->getOptionText()),
                'count' => $count,
                'percentage' => $total > 0 ? round($count / $total * 100, 2) : 0,
            ];
        }

        return $items;
    }

    /**
     * @param array<int, CSurveyAnswer> $answers
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildTextAnswers(CSurveyQuestion $question, array $answers): array
    {
        if (!\in_array($question->getType(), ['open', 'comment', 'multiplechoiceother'], true)) {
            return [];
        }

        $items = [];
        foreach ($answers as $answer) {
            $text = $answer->getOptionId();
            if ('multiplechoiceother' === $question->getType()) {
                $text = $this->getOtherText($text);
            }

            $text = trim($this->cleanText($text));
            if ('' === $text) {
                continue;
            }

            $items[] = [
                'user' => (string) $answer->getUser(),
                'text' => $text,
            ];
        }

        return $items;
    }

    /**
     * @param array<int, CSurveyQuestionOption> $options
     * @param array<int, CSurveyAnswer>         $answers
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildScoreRows(CSurveyQuestion $question, array $options, array $answers): array
    {
        if ('score' !== $question->getType()) {
            return [];
        }

        $data = [];
        foreach ($answers as $answer) {
            $optionId = $this->getBaseOptionId($answer->getOptionId());
            $score = (int) $answer->getValue();
            if ($optionId <= 0 || $score <= 0) {
                continue;
            }

            $data[$optionId][$score] = ($data[$optionId][$score] ?? 0) + 1;
        }

        $rows = [];
        foreach ($options as $option) {
            $distribution = [];
            for ($score = 1; $score <= max(1, (int) $question->getMaxValue()); $score++) {
                $distribution[] = [
                    'score' => $score,
                    'count' => $data[(int) $option->getIid()][$score] ?? 0,
                ];
            }

            $rows[] = [
                'optionId' => (int) $option->getIid(),
                'optionLabel' => $this->cleanOptionText($option->getOptionText()),
                'distribution' => $distribution,
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, array<string, mixed>> $users
     *
     * @return array<string, mixed>
     */
    private function buildSelectedUser(string $selectedUserKey, array $users): array
    {
        if ('' === $selectedUserKey || !isset($users[$selectedUserKey])) {
            return [];
        }

        return $users[$selectedUserKey];
    }

    /**
     * @param array<int, CSurveyQuestion>                   $questions
     * @param array<int, array<int, CSurveyQuestionOption>> $optionsByQuestion
     * @param array<int, CSurveyAnswer>                     $answers
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildUserAnswers(string $selectedUserKey, array $questions, array $optionsByQuestion, array $answers): array
    {
        if ('' === $selectedUserKey) {
            return [];
        }

        $answersByQuestion = [];
        foreach ($answers as $answer) {
            if ((string) $answer->getUser() !== $selectedUserKey) {
                continue;
            }

            $answersByQuestion[(int) $answer->getQuestion()->getIid()][] = $answer;
        }

        $items = [];
        foreach ($questions as $question) {
            $questionId = (int) $question->getIid();
            $items[] = [
                'questionId' => $questionId,
                'question' => $this->cleanText($question->getSurveyQuestion()),
                'type' => $question->getType(),
                'answer' => $this->formatAnswerText($question, $optionsByQuestion[$questionId] ?? [], $answersByQuestion[$questionId] ?? []),
            ];
        }

        return $items;
    }

    /**
     * @param array<string, array<string, mixed>>|null           $users
     * @param array<int, CSurveyQuestion>|null                   $questions
     * @param array<int, array<int, CSurveyQuestionOption>>|null $optionsByQuestion
     * @param array<int, CSurveyAnswer>|null                     $answers
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildCompleteRows(
        CSurvey $survey,
        ?Session $session,
        ?array $users = null,
        ?array $questions = null,
        ?array $optionsByQuestion = null,
        ?array $answers = null
    ): array {
        $questions ??= $this->getReportableQuestions($survey);
        $optionsByQuestion ??= $this->getOptionsByQuestion($survey);
        $answers ??= $this->getAnswers($survey, $session);
        $users ??= $this->buildUsers($survey, [], $answers);

        $answersByUserQuestion = [];
        foreach ($answers as $answer) {
            $answersByUserQuestion[(string) $answer->getUser()][(int) $answer->getQuestion()->getIid()][] = $answer;
        }

        $rows = [];
        foreach ($users as $userKey => $user) {
            $rowAnswers = [];
            foreach ($questions as $question) {
                $questionId = (int) $question->getIid();
                $rowAnswers[$questionId] = $this->formatAnswerText(
                    $question,
                    $optionsByQuestion[$questionId] ?? [],
                    $answersByUserQuestion[(string) $userKey][$questionId] ?? [],
                );
            }

            $rows[] = [
                'userKey' => (string) $userKey,
                'userLabel' => $user['label'] ?? (string) $userKey,
                'answers' => $rowAnswers,
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, CSurveyQuestionOption> $options
     * @param array<int, CSurveyAnswer>         $answers
     */
    private function formatAnswerText(CSurveyQuestion $question, array $options, array $answers): string
    {
        if ([] === $answers) {
            return '';
        }

        $optionLabels = [];
        foreach ($options as $option) {
            $optionLabels[(int) $option->getIid()] = $this->cleanOptionText($option->getOptionText());
        }

        $items = [];
        foreach ($answers as $answer) {
            $type = $question->getType();
            if ('open' === $type || 'comment' === $type) {
                $items[] = $this->cleanText($answer->getOptionId());

                continue;
            }

            if ('score' === $type) {
                $optionId = $this->getBaseOptionId($answer->getOptionId());
                $items[] = ($optionLabels[$optionId] ?? (string) $optionId).': '.(int) $answer->getValue();

                continue;
            }

            if ('multiplechoiceother' === $type && '' !== $this->getOtherText($answer->getOptionId())) {
                $items[] = $this->getOtherText($answer->getOptionId());

                continue;
            }

            $optionId = $this->getBaseOptionId($answer->getOptionId());
            $items[] = $optionLabels[$optionId] ?? $this->cleanText($answer->getOptionId());
        }

        return implode(', ', array_filter($items, static fn (string $item): bool => '' !== trim($item)));
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeSurvey(CSurvey $survey, Course $course, ?Session $session): array
    {
        return [
            'id' => (int) $survey->getIid(),
            'code' => $survey->getCode(),
            'title' => $this->cleanText($survey->getTitle()),
            'subtitle' => $this->cleanText((string) $survey->getSubtitle()),
            'anonymous' => $this->isAnonymous($survey),
            'visibleResults' => $survey->getVisibleResults(),
            'surveyType' => $survey->getSurveyType(),
            'courseId' => (int) $course->getId(),
            'sessionId' => null !== $session ? (int) $session->getId() : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getSettings(): array
    {
        return [
            'hideReportingButton' => $this->isReportingHidden(),
            'anonymousShowAnswered' => $this->isSettingEnabled('survey.survey_anonymous_show_answered'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function buildExportUrls(CSurvey $survey): array
    {
        $baseUrl = '/api/survey/reporting/'.(int) $survey->getIid();

        return [
            'csv' => $baseUrl.'/export.csv',
            'csvCompact' => $baseUrl.'/export.csv?compact=1',
            'xlsx' => $baseUrl.'/export.xlsx',
            'xlsxByClass' => $baseUrl.'/export-by-class.xlsx',
            'zip' => $baseUrl.'/export.zip',
        ];
    }

    private function buildCsvContent(CSurvey $survey, Course $course, ?Session $session, Request $request, bool $compact): string
    {
        $matrix = $compact
            ? $this->buildCompactExportMatrix($survey, $session, $request)
            : $this->buildExpandedExportMatrix($survey, $course, $session, $request);

        $handle = fopen('php://temp', 'w+');
        if (false === $handle) {
            return '';
        }

        fwrite($handle, "\xEF\xBB\xBF");
        foreach ($matrix as $row) {
            fputcsv($handle, $row, ';');
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return false === $content ? '' : $content;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function buildCompactExportMatrix(CSurvey $survey, ?Session $session, Request $request): array
    {
        $questions = $this->getExportQuestions($survey);
        $rows = $this->filterRowsByRequestedUser($this->buildCompleteRows($survey, $session), $request);

        $matrix = [];
        $header = ['User'];
        foreach ($questions as $question) {
            $header[] = $this->cleanText($question->getSurveyQuestion());
        }
        $matrix[] = $header;

        foreach ($rows as $row) {
            $line = [$row['userLabel'] ?? ''];
            foreach ($questions as $question) {
                $line[] = (string) ($row['answers'][(int) $question->getIid()] ?? '');
            }
            $matrix[] = $line;
        }

        return $matrix;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function buildExpandedExportMatrix(CSurvey $survey, Course $course, ?Session $session, Request $request): array
    {
        $questions = $this->getExportQuestions($survey);
        $optionsByQuestion = $this->getOptionsByQuestion($survey);
        $answers = $this->getAnswers($survey, $session);
        $users = $this->buildUsers($survey, $this->getInvitations($survey, $course, $session), $answers);
        $rows = $this->filterRowsByRequestedUser($this->buildExpandedAnswerRows($questions, $optionsByQuestion, $answers, $users), $request);

        $questionHeader = ['User'];
        $optionHeader = [''];
        foreach ($questions as $question) {
            $questionId = (int) $question->getIid();
            $options = $optionsByQuestion[$questionId] ?? [];
            $columnCount = $this->getExpandedColumnCount($question, $options);
            for ($index = 0; $index < $columnCount; $index++) {
                $questionHeader[] = $this->cleanText($question->getSurveyQuestion());
            }

            if ($this->questionUsesSingleExportColumn($question, $options)) {
                $optionHeader[] = '';

                continue;
            }

            foreach ($options as $option) {
                $optionHeader[] = $this->cleanOptionText($option->getOptionText());
            }
        }

        $matrix = [$questionHeader, $optionHeader];
        foreach ($rows as $row) {
            $matrix[] = $row['values'];
        }

        return $matrix;
    }

    private function buildCompleteSpreadsheet(CSurvey $survey, Course $course, ?Session $session, Request $request): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Survey results');
        $this->fillWorksheet($sheet, $this->buildExpandedExportMatrix($survey, $course, $session, $request));

        return $spreadsheet;
    }

    private function buildClassSpreadsheet(CSurvey $survey, Course $course, ?Session $session, Request $request): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $classes = $this->getCourseClasses($course);
        if ([] === $classes) {
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('No classes');
            $this->fillWorksheet($sheet, [['No classes or user groups were found for this course.']]);

            return $spreadsheet;
        }

        $questions = $this->getExportQuestions($survey);
        $optionsByQuestion = $this->getOptionsByQuestion($survey);
        $answers = $this->getAnswers($survey, $session);
        $users = $this->buildUsers($survey, $this->getInvitations($survey, $course, $session), $answers);
        $expandedRows = $this->buildExpandedAnswerRows($questions, $optionsByQuestion, $answers, $users);

        foreach ($classes as $index => $class) {
            $sheet = 0 === $index ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheet->setTitle($this->buildWorksheetTitle($class['title']));
            $classUserIds = array_fill_keys($class['userIds'], true);
            $matrix = [];
            $matrix[] = [$class['title']];
            $matrix[] = [];

            foreach ($this->buildExpandedExportHeaders($questions, $optionsByQuestion) as $headerRow) {
                $matrix[] = $headerRow;
            }

            foreach ($expandedRows as $row) {
                if (!isset($classUserIds[(int) $row['userKey']])) {
                    continue;
                }
                $matrix[] = $row['values'];
            }

            if (3 === \count($matrix)) {
                $matrix[] = ['No answers found'];
            }

            $this->fillWorksheet($sheet, $matrix);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @param array<int, CSurveyQuestion>                   $questions
     * @param array<int, array<int, CSurveyQuestionOption>> $optionsByQuestion
     *
     * @return array<int, array<int, string>>
     */
    private function buildExpandedExportHeaders(array $questions, array $optionsByQuestion): array
    {
        $questionHeader = ['User'];
        $optionHeader = [''];
        foreach ($questions as $question) {
            $options = $optionsByQuestion[(int) $question->getIid()] ?? [];
            $columnCount = $this->getExpandedColumnCount($question, $options);
            for ($index = 0; $index < $columnCount; $index++) {
                $questionHeader[] = $this->cleanText($question->getSurveyQuestion());
            }

            if ($this->questionUsesSingleExportColumn($question, $options)) {
                $optionHeader[] = '';

                continue;
            }

            foreach ($options as $option) {
                $optionHeader[] = $this->cleanOptionText($option->getOptionText());
            }
        }

        return [$questionHeader, $optionHeader];
    }

    /**
     * @param array<int, CSurveyQuestion>                   $questions
     * @param array<int, array<int, CSurveyQuestionOption>> $optionsByQuestion
     * @param array<int, CSurveyAnswer>                     $answers
     * @param array<string, array<string, mixed>>           $users
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildExpandedAnswerRows(array $questions, array $optionsByQuestion, array $answers, array $users): array
    {
        $answersByUserQuestion = [];
        foreach ($answers as $answer) {
            $answersByUserQuestion[(string) $answer->getUser()][(int) $answer->getQuestion()->getIid()][] = $answer;
        }

        $rows = [];
        foreach ($users as $userKey => $user) {
            $values = [$user['label'] ?? (string) $userKey];
            foreach ($questions as $question) {
                $questionId = (int) $question->getIid();
                $questionAnswers = $answersByUserQuestion[(string) $userKey][$questionId] ?? [];
                $options = $optionsByQuestion[$questionId] ?? [];
                $values = array_merge($values, $this->buildExpandedAnswerValues($question, $options, $questionAnswers));
            }

            $rows[] = [
                'userKey' => (string) $userKey,
                'userLabel' => $user['label'] ?? (string) $userKey,
                'values' => $values,
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, CSurveyQuestionOption> $options
     * @param array<int, CSurveyAnswer>         $answers
     *
     * @return array<int, string>
     */
    private function buildExpandedAnswerValues(CSurveyQuestion $question, array $options, array $answers): array
    {
        if ($this->questionUsesSingleExportColumn($question, $options)) {
            return [$this->formatAnswerText($question, $options, $answers)];
        }

        $values = [];
        foreach ($options as $option) {
            $values[] = $this->formatOptionAnswerValue($question, $option, $answers);
        }

        return $values;
    }

    /**
     * @param array<int, CSurveyAnswer> $answers
     */
    private function formatOptionAnswerValue(CSurveyQuestion $question, CSurveyQuestionOption $option, array $answers): string
    {
        foreach ($answers as $answer) {
            if ($this->getBaseOptionId($answer->getOptionId()) !== (int) $option->getIid()) {
                continue;
            }

            if ('score' === $question->getType() && (int) $answer->getValue() > 0) {
                return (string) (int) $answer->getValue();
            }

            if ('multiplechoiceother' === $question->getType() && '' !== $this->getOtherText($answer->getOptionId())) {
                return $this->getOtherText($answer->getOptionId());
            }

            if ((int) $answer->getValue() > 0) {
                return (string) (int) $answer->getValue();
            }

            return 'v';
        }

        return '';
    }

    /**
     * @param array<int, CSurveyQuestionOption> $options
     */
    private function questionUsesSingleExportColumn(CSurveyQuestion $question, array $options): bool
    {
        return [] === $options || \in_array($question->getType(), ['open', 'comment'], true);
    }

    /**
     * @param array<int, CSurveyQuestionOption> $options
     */
    private function getExpandedColumnCount(CSurveyQuestion $question, array $options): int
    {
        if ($this->questionUsesSingleExportColumn($question, $options)) {
            return 1;
        }

        return max(1, \count($options));
    }

    /**
     * @return array<int, CSurveyQuestion>
     */
    private function getExportQuestions(CSurvey $survey): array
    {
        return array_values(array_filter(
            $this->getReportableQuestions($survey),
            fn (CSurveyQuestion $question): bool => !str_contains($question->getSurveyQuestion(), '{{')
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function filterRowsByRequestedUser(array $rows, Request $request): array
    {
        $selectedUser = trim((string) $request->query->get('user', ''));
        if ('' === $selectedUser) {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            static fn (array $row): bool => (string) ($row['userKey'] ?? '') === $selectedUser
        ));
    }

    private function fillWorksheet(Worksheet $sheet, array $matrix): void
    {
        foreach ($matrix as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 1, $value);
            }
        }

        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
            $column = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function writeSpreadsheetToTemporaryFile(Spreadsheet $spreadsheet): string
    {
        $file = tempnam(sys_get_temp_dir(), 'survey_export_xlsx_');
        if (false === $file) {
            throw new BadRequestHttpException('Could not create export file.');
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($file);
        $spreadsheet->disconnectWorksheets();

        return $file;
    }

    private function buildSafeExportName(CSurvey $survey): string
    {
        $title = $this->cleanText($survey->getTitle());
        $base = '' !== $title ? $title : ('survey_'.$survey->getIid());
        $base = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $base) ?: 'survey';

        return trim($base, '_') ?: 'survey';
    }

    private function buildWorksheetTitle(string $title): string
    {
        $title = preg_replace('/[\/?*:\[\]]+/', ' ', $this->cleanText($title)) ?: 'Class';
        $title = trim($title);

        return mb_substr('' !== $title ? $title : 'Class', 0, 31);
    }

    /**
     * @return array<int, array{title: string, userIds: array<int, int>}>
     */
    private function getCourseClasses(Course $course): array
    {
        $connection = $this->entityManager->getConnection();
        $tables = $connection->createSchemaManager()->listTableNames();
        if (!\in_array('usergroup', $tables, true) || !\in_array('usergroup_rel_course', $tables, true) || !\in_array('usergroup_rel_user', $tables, true)) {
            return [];
        }

        $classRows = $connection->fetchAllAssociative(
            'SELECT DISTINCT ug.id, ug.title
               FROM usergroup ug
               INNER JOIN usergroup_rel_course ugc ON ugc.usergroup_id = ug.id
              WHERE ugc.course_id = :courseId
              ORDER BY ug.title ASC',
            ['courseId' => (int) $course->getId()]
        );

        $classes = [];
        foreach ($classRows as $classRow) {
            $userIds = $connection->fetchFirstColumn(
                'SELECT DISTINCT user_id
                   FROM usergroup_rel_user
                  WHERE usergroup_id = :usergroupId
                  ORDER BY user_id ASC',
                ['usergroupId' => (int) $classRow['id']]
            );

            $classes[] = [
                'title' => (string) $classRow['title'],
                'userIds' => array_map('intval', $userIds),
            ];
        }

        return $classes;
    }

    private function getBaseOptionId(string $optionId): int
    {
        $parts = explode('@:@', $optionId, 2);

        return (int) $parts[0];
    }

    private function getOtherText(string $optionId): string
    {
        $parts = explode('@:@', $optionId, 2);

        return isset($parts[1]) ? $this->cleanText($parts[1]) : '';
    }

    private function cleanOptionText(string $value): string
    {
        $text = $this->cleanText($value);
        if ('other' === strtolower($text)) {
            return 'Other';
        }

        return $text;
    }

    private function cleanText(string $value): string
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $withoutTags = strip_tags($decoded);

        return trim((string) preg_replace('/\s+/', ' ', html_entity_decode($withoutTags, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    private function isAnonymous(CSurvey $survey): bool
    {
        return '1' === (string) $survey->getAnonymous();
    }

    private function isReportingHidden(): bool
    {
        return $this->isSettingEnabled('survey.hide_survey_reporting_button');
    }

    private function isSettingEnabled(string $settingName): bool
    {
        $value = $this->settingsManager->getSetting($settingName, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }

    private function getQuestionTypeLabel(string $type): string
    {
        return match ($type) {
            'yesno' => 'Yes / No',
            'multiplechoice' => 'Multiple choice',
            'multipleresponse' => 'Multiple answers',
            'dropdown' => 'Dropdown',
            'open' => 'Open',
            'comment' => 'Comment',
            'score' => 'Score',
            'percentage' => 'Percentage',
            'multiplechoiceother' => 'Multiple choice with free text',
            'selectivedisplay' => 'Selective display',
            default => $type,
        };
    }
}
