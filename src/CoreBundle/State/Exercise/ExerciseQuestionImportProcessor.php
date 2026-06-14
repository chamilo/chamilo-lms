<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseQuestionImport;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Service\Exercise\ExerciseQti2ImportService;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;
use ZipArchive;

/**
 * @implements ProcessorInterface<ExerciseQuestionImport, ExerciseQuestionImport>
 */
final readonly class ExerciseQuestionImportProcessor implements ProcessorInterface
{
    private const UNIQUE_ANSWER = 1;
    private const MULTIPLE_ANSWER = 2;
    private const FILL_IN_BLANKS = 3;
    private const MATCHING = 4;
    private const FREE_ANSWER = 5;
    private const GLOBAL_MULTIPLE_ANSWER = 14;
    private const DEFAULT_TOTAL_WEIGHT = 20.0;
    private const CSRF_TOKEN_ID = ExerciseQuestionImportProvider::CSRF_TOKEN_ID;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private CQuizQuestionCategoryRepository $questionCategoryRepository,
        private ExerciseQti2ImportService $qti2ImportService,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseQuestionImport
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to import exercises in this context.');
        }

        $importType = $this->normalizeImportType((string) ($uriVariables['importType'] ?? 'aiken'));
        $this->validateCsrfToken((string) $request->request->get('submittedCsrfToken', ''));

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile instanceof UploadedFile) {
            throw new BadRequestHttpException('A file is required.');
        }

        if ('excel' === $importType) {
            return $this->processExcelImport($uploadedFile, $request, $course, $session);
        }

        if ('qti2' === $importType) {
            return $this->processQti2Import($uploadedFile, $course, $session);
        }

        return $this->processAikenImport($uploadedFile, $request, $course, $session);
    }

    private function canManageExercises(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function validateCsrfToken(string $submittedToken): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $submittedToken))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
    }

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if (0 >= $courseId) {
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
        if (0 >= $sessionId) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
    }

    private function normalizeImportType(string $importType): string
    {
        $importType = strtolower(trim($importType));
        if (\in_array($importType, ['aiken', 'excel', 'qti2'], true)) {
            return $importType;
        }

        throw new BadRequestHttpException('Unsupported import type.');
    }

    private function normalizeTotalWeight(string $value): float
    {
        $value = str_replace(',', '.', trim($value));
        if ('' === $value || !is_numeric($value)) {
            return self::DEFAULT_TOTAL_WEIGHT;
        }

        $weight = (float) $value;

        return 0 < $weight ? $weight : self::DEFAULT_TOTAL_WEIGHT;
    }

    private function normalizeScoreValue(string $value): ?float
    {
        $value = str_replace(',', '.', trim($value));
        if ('' === $value || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function processAikenImport(UploadedFile $uploadedFile, Request $request, Course $course, ?Session $session): ExerciseQuestionImport
    {
        $totalWeight = $this->normalizeTotalWeight((string) $request->request->get('totalWeight', ''));
        $source = $this->readAikenSource($uploadedFile);
        $parsedQuestions = $this->parseAikenText($source['content'], $totalWeight);
        if ([] === $parsedQuestions['questions']) {
            throw new BadRequestHttpException('No valid Aiken questions were found in the uploaded file.');
        }

        $exerciseTitle = $this->buildAikenExerciseTitle($uploadedFile);
        $quiz = $this->createExercise($exerciseTitle, $course, $session);
        $importedQuestionCount = $this->createAikenQuestions($quiz, $parsedQuestions['questions'], $course, $session);

        if (0 === $importedQuestionCount) {
            throw new BadRequestHttpException('No valid Aiken questions were imported.');
        }

        $this->entityManager->flush();

        return $this->buildSuccessResponse(
            'aiken',
            'Import Aiken quiz',
            'Aiken quiz imported',
            $quiz,
            $importedQuestionCount,
            (int) $parsedQuestions['skippedCount'],
            $parsedQuestions['errors'],
        );
    }

    private function processExcelImport(UploadedFile $uploadedFile, Request $request, Course $course, ?Session $session): ExerciseQuestionImport
    {
        $worksheet = $this->readExcelWorksheet($uploadedFile);
        $excelData = $this->parseExcelWorksheet($worksheet);
        if ('' === trim($excelData['quizTitle'])) {
            throw new BadRequestHttpException('ErrorImportingFile');
        }

        $useCustomScore = $this->normalizeBoolean((string) $request->request->get('useCustomScore', ''));
        $correctScore = $this->normalizeScoreValue((string) $request->request->get('correctScore', ''));
        $incorrectScore = $this->normalizeScoreValue((string) $request->request->get('incorrectScore', ''));
        $propagateNegative = $useCustomScore && null !== $incorrectScore && 0 > $incorrectScore ? 1 : 0;

        $quiz = $this->createExercise($excelData['quizTitle'], $course, $session, $propagateNegative);
        $importResult = $this->createExcelQuestions($quiz, $excelData['questions'], $course, $session, $useCustomScore, $correctScore, $incorrectScore);
        if (0 === $importResult['importedCount']) {
            throw new BadRequestHttpException('No valid Excel questions were imported.');
        }

        $this->entityManager->flush();

        return $this->buildSuccessResponse(
            'excel',
            'Import quiz from Excel',
            'File imported',
            $quiz,
            $importResult['importedCount'],
            $importResult['skippedCount'],
            $importResult['errors'],
        );
    }

    private function processQti2Import(UploadedFile $uploadedFile, Course $course, ?Session $session): ExerciseQuestionImport
    {
        $importResult = $this->qti2ImportService->import($uploadedFile, $course, $session);

        return $this->buildSuccessResponse(
            'qti2',
            'Import exercises QTI2',
            'File imported',
            $importResult['quiz'],
            $importResult['importedCount'],
            $importResult['skippedCount'],
            $importResult['errors'],
        );
    }

    /**
     * @param array<int, string> $errors
     */
    private function buildSuccessResponse(
        string $importType,
        string $title,
        string $message,
        CQuiz $quiz,
        int $importedQuestionCount,
        int $skippedQuestionCount,
        array $errors,
    ): ExerciseQuestionImport {
        $response = new ExerciseQuestionImport();
        $response->importType = $importType;
        $response->title = $title;
        $response->canManage = true;
        $response->success = true;
        $response->exerciseId = (int) $quiz->getIid();
        $response->exerciseTitle = $quiz->getTitle();
        $response->importedQuestionCount = $importedQuestionCount;
        $response->skippedQuestionCount = $skippedQuestionCount;
        $response->errors = $errors;
        $response->message = $message;

        return $response;
    }

    private function normalizeBoolean(string $value): bool
    {
        return \in_array(strtolower(trim($value)), ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * @return array{content: string, filename: string}
     */
    private function readAikenSource(UploadedFile $uploadedFile): array
    {
        if (!$uploadedFile->isValid()) {
            throw new BadRequestHttpException('The uploaded file is not valid.');
        }

        $extension = strtolower((string) $uploadedFile->getClientOriginalExtension());
        if ('txt' === $extension) {
            $content = (string) file_get_contents($uploadedFile->getPathname());

            return ['content' => $this->normalizeTextContent($content), 'filename' => $uploadedFile->getClientOriginalName()];
        }

        if ('zip' === $extension) {
            return ['content' => $this->readAikenZip($uploadedFile), 'filename' => $uploadedFile->getClientOriginalName()];
        }

        throw new BadRequestHttpException('You must upload a .txt or .zip file.');
    }

    private function readAikenZip(UploadedFile $uploadedFile): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new BadRequestHttpException('ZIP support is not available on this platform.');
        }

        $zip = new ZipArchive();
        if (true !== $zip->open($uploadedFile->getPathname())) {
            throw new BadRequestHttpException('The uploaded ZIP file could not be opened.');
        }

        $contents = [];
        for ($index = 0; $index < $zip->numFiles; ++$index) {
            $name = (string) $zip->getNameIndex($index);
            if ('' === $name || str_ends_with($name, '/') || !str_ends_with(strtolower($name), '.txt')) {
                continue;
            }

            $content = $zip->getFromIndex($index);
            if (false !== $content && '' !== trim($content)) {
                $contents[] = $this->normalizeTextContent($content);
            }
        }
        $zip->close();

        if ([] === $contents) {
            throw new BadRequestHttpException('The uploaded ZIP file does not contain Aiken .txt files.');
        }

        return implode("\n\n", $contents);
    }

    private function normalizeTextContent(string $content): string
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        return $content;
    }

    /**
     * @return array{questions: array<int, array<string, mixed>>, skippedCount: int, errors: array<int, string>}
     */
    private function parseAikenText(string $content, float $totalWeight): array
    {
        $questions = [];
        $errors = [];
        $currentQuestion = null;
        $answersByLetter = [];
        $lineNumber = 0;

        foreach (explode("\n", $content) as $line) {
            ++$lineNumber;
            $line = trim($line);

            if ('' === $line || '```' === $line || str_starts_with($line, '```')) {
                continue;
            }

            if ($this->isAikenQuestionTitleLine($line)) {
                if (null !== $currentQuestion) {
                    $questions[] = $currentQuestion;
                }

                $currentQuestion = [
                    'line' => $lineNumber,
                    'title' => $this->stripLeadingQuestionNumber($line),
                    'answers' => [],
                    'correctIndexes' => [],
                    'feedback' => '',
                    'score' => null,
                ];
                $answersByLetter = [];
                continue;
            }

            if (null === $currentQuestion) {
                $errors[] = 'Line '.$lineNumber.': ignored content before the first question.';
                continue;
            }

            if (preg_match('/^([A-Z])\.\s(.*)/', $line, $matches)) {
                $answerIndex = \count($currentQuestion['answers']);
                $currentQuestion['answers'][] = trim((string) $matches[2]);
                $answersByLetter[(string) $matches[1]] = $answerIndex;
                continue;
            }

            if (preg_match('/^ANSWER:\s?([A-Z])/', $line, $matches)) {
                $letter = (string) $matches[1];
                if (isset($answersByLetter[$letter])) {
                    $currentQuestion['correctIndexes'][] = $answersByLetter[$letter];
                } else {
                    $errors[] = 'Line '.$lineNumber.': ANSWER references an unknown option.';
                }
                continue;
            }

            if (preg_match('/^ANSWER_EXPLANATION:\s?(.*)/', $line, $matches)) {
                $currentQuestion['feedback'] = trim((string) $matches[1]);
                continue;
            }

            if (preg_match('/^SCORE:\s?(.*)/', $line, $matches)) {
                $score = str_replace(',', '.', trim((string) $matches[1]));
                if (is_numeric($score) && 0 < (float) $score) {
                    $currentQuestion['score'] = (float) $score;
                }
                continue;
            }

            $errors[] = 'Line '.$lineNumber.': ignored unsupported Aiken line.';
        }

        if (null !== $currentQuestion) {
            $questions[] = $currentQuestion;
        }

        $validQuestions = [];
        $skippedCount = 0;
        $defaultScore = $totalWeight / max(1, \count($questions));
        foreach ($questions as $question) {
            $answers = array_values(array_filter($question['answers'], static fn (string $answer): bool => '' !== trim($answer)));
            $correctIndexes = array_values(array_unique(array_map('intval', $question['correctIndexes'])));
            if ('' === trim((string) $question['title']) || 2 > \count($answers) || [] === $correctIndexes) {
                ++$skippedCount;
                $errors[] = 'Line '.(int) $question['line'].': skipped invalid Aiken question.';
                continue;
            }

            $question['answers'] = $answers;
            $question['correctIndexes'] = $correctIndexes;
            $question['score'] = null !== $question['score'] ? (float) $question['score'] : $defaultScore;
            $validQuestions[] = $question;
        }

        return [
            'questions' => $validQuestions,
            'skippedCount' => $skippedCount,
            'errors' => $errors,
        ];
    }

    private function isAikenQuestionTitleLine(string $line): bool
    {
        return !preg_match('/^[A-Z]\.\s/', $line)
            && !preg_match('/^ANSWER:\s?[A-Z]/', $line)
            && !preg_match('/^ANSWER_EXPLANATION:\s?(.*)/', $line)
            && !preg_match('/^SCORE:\s?(.*)/', $line);
    }

    private function stripLeadingQuestionNumber(string $line): string
    {
        $line = trim($line);
        if ('' === $line) {
            return $line;
        }

        $normalized = preg_replace('/^\d+\s*[\.\)\-:]\s+/u', '', $line);
        if (null === $normalized) {
            return $line;
        }

        $normalized = trim($normalized);

        return '' !== $normalized ? $normalized : $line;
    }

    private function buildAikenExerciseTitle(UploadedFile $uploadedFile): string
    {
        $name = $uploadedFile->getClientOriginalName();
        $title = preg_replace('/\.(zip|txt)$/i', '', $name) ?? $name;
        $title = trim($title);

        return '' !== $title ? $title : 'Imported Aiken quiz';
    }

    private function readExcelWorksheet(UploadedFile $uploadedFile): Worksheet
    {
        if (!$uploadedFile->isValid()) {
            throw new BadRequestHttpException('The uploaded file is not valid.');
        }

        $extension = strtolower((string) $uploadedFile->getClientOriginalExtension());
        if (!\in_array($extension, ['xls', 'xlsx'], true)) {
            throw new BadRequestHttpException('The uploaded file must be an Excel document.');
        }

        try {
            $spreadsheet = IOFactory::load($uploadedFile->getPathname());
        } catch (Throwable) {
            throw new BadRequestHttpException('The uploaded Excel file could not be read.');
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet->getActiveSheet();
    }

    /**
     * @return array{quizTitle: string, questions: array<int, array<string, mixed>>}
     */
    private function parseExcelWorksheet(Worksheet $worksheet): array
    {
        $quizTitle = '';
        $questions = [];
        $currentIndex = null;
        $highestRow = $worksheet->getHighestRow();

        for ($row = 1; $row <= $highestRow; ++$row) {
            $label = $this->getWorksheetCellValue($worksheet, 'A'.$row);
            $data = $this->getWorksheetCellValue($worksheet, 'B'.$row);
            $extra = $this->getWorksheetCellValue($worksheet, 'C'.$row);

            if ('Quiz' === $label) {
                $quizTitle = $data;
                continue;
            }

            if ('Question' === $label) {
                $questions[] = $this->createEmptyExcelQuestion($data, $row);
                $currentIndex = array_key_last($questions);
                continue;
            }

            if (null === $currentIndex) {
                continue;
            }

            if (str_starts_with($label, 'Answer')) {
                $questions[$currentIndex]['answers'][] = ['data' => $data, 'extra' => $extra];
                continue;
            }

            if ('Score' === $label) {
                $questions[$currentIndex]['score'] = $extra;
                continue;
            }

            if ('NoNegativeScore' === $label) {
                $questions[$currentIndex]['noNegativeScore'] = $extra;
                continue;
            }

            if ('Category' === $label) {
                $questions[$currentIndex]['category'] = $data;
                continue;
            }

            if ('FeedbackTrue' === $label) {
                $questions[$currentIndex]['feedbackTrue'] = $data;
                continue;
            }

            if ('FeedbackFalse' === $label) {
                $questions[$currentIndex]['feedbackFalse'] = $data;
                continue;
            }

            if ('EnrichQuestion' === $label) {
                $questions[$currentIndex]['description'] = $data;
                continue;
            }

            if ('QuestionType' === $label) {
                $questions[$currentIndex]['type'] = $extra;
            }
        }

        return ['quizTitle' => trim($quizTitle), 'questions' => $questions];
    }

    private function getWorksheetCellValue(Worksheet $worksheet, string $coordinate): string
    {
        $value = $worksheet->getCell($coordinate)->getValue();
        if (null === $value) {
            return '';
        }

        return trim((string) $value);
    }

    /**
     * @return array<string, mixed>
     */
    private function createEmptyExcelQuestion(string $title, int $row): array
    {
        return [
            'row' => $row,
            'title' => $title,
            'answers' => [],
            'score' => null,
            'noNegativeScore' => '',
            'category' => '',
            'feedbackTrue' => '',
            'feedbackFalse' => '',
            'description' => '',
            'type' => null,
        ];
    }

    private function createExercise(string $title, Course $course, ?Session $session, int $propagateNegative = 0): CQuiz
    {
        $quiz = new CQuiz();
        $quiz
            ->setTitle($title)
            ->setDescription('')
            ->setType(CQuiz::ONE_PER_PAGE)
            ->setRandom(0)
            ->setRandomAnswers(false)
            ->setResultsDisabled(0)
            ->setMaxAttempt(1)
            ->setFeedbackType(0)
            ->setExpiredTime(0)
            ->setPropagateNeg($propagateNegative)
            ->setSaveCorrectAnswers(0)
            ->setReviewAnswers(0)
            ->setRandomByCategory(0)
            ->setDisplayCategoryName(0)
            ->setPassPercentage(0)
            ->setPreventBackwards(0)
            ->setHideQuestionTitle(false)
            ->setHideQuestionNumber(0)
            ->setShowPreviousButton(true)
            ->setNotifications('')
            ->setAutoLaunch(false)
            ->setHideAttemptsTable(false)
            ->setPageResultConfiguration([])
            ->setParent($course)
            ->addCourseLink($course, $session)
        ;

        $this->entityManager->persist($quiz);

        return $quiz;
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     */
    private function createAikenQuestions(CQuiz $quiz, array $questions, Course $course, ?Session $session): int
    {
        $questionOrder = 1;
        foreach ($questions as $questionData) {
            $score = max(0.0, (float) $questionData['score']);
            $question = $this->createQuestion($quiz, $course, $session, (string) $questionData['title'], '', self::UNIQUE_ANSWER, $score, $questionOrder);
            $this->createChoiceAnswers($question, $questionData['answers'], $questionData['correctIndexes'], $score, (string) $questionData['feedback']);
            ++$questionOrder;
        }

        return \count($questions);
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     *
     * @return array{importedCount: int, skippedCount: int, errors: array<int, string>}
     */
    private function createExcelQuestions(
        CQuiz $quiz,
        array $questions,
        Course $course,
        ?Session $session,
        bool $useCustomScore,
        ?float $correctScore,
        ?float $incorrectScore,
    ): array {
        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];
        $questionOrder = 1;

        foreach ($questions as $questionData) {
            $title = trim((string) $questionData['title']);
            if ('' === $title) {
                ++$skippedCount;
                $errors[] = 'Row '.(int) $questionData['row'].': skipped question without title.';
                continue;
            }

            $answers = \is_array($questionData['answers']) ? $questionData['answers'] : [];
            $questionType = $this->normalizeExcelQuestionType($questionData['type'], $answers);
            $description = (string) $questionData['description'];
            $questionDescription = '' !== $description ? '<p>'.$description.'</p>' : '<p></p>';
            if (self::FILL_IN_BLANKS === $questionType) {
                $questionDescription = '';
            }

            $question = $this->createQuestion($quiz, $course, $session, $title, $questionDescription, $questionType, 0.0, $questionOrder);
            $category = $this->findOrCreateQuestionCategory((string) $questionData['category'], $course, $session);
            if ($category instanceof CQuizQuestionCategory) {
                $question->updateCategory($category);
            }

            $this->createExcelAnswers($question, $questionType, $answers, $questionData, $useCustomScore, $correctScore, $incorrectScore, $description);
            ++$importedCount;
            ++$questionOrder;
        }

        return [
            'importedCount' => $importedCount,
            'skippedCount' => $skippedCount,
            'errors' => $errors,
        ];
    }

    private function createQuestion(
        CQuiz $quiz,
        Course $course,
        ?Session $session,
        string $title,
        string $description,
        int $type,
        float $ponderation,
        int $questionOrder,
    ): CQuizQuestion {
        $question = new CQuizQuestion();
        $question
            ->setQuestion($title)
            ->setDescription($description)
            ->setFeedback('')
            ->setExtra(null)
            ->setType($type)
            ->setLevel(0)
            ->setPosition($questionOrder)
            ->setPonderation($ponderation)
            ->setMandatory(0)
            ->setDuration(null)
            ->setParentMediaId(null)
            ->setParent($course)
            ->addCourseLink($course, $session)
        ;
        $this->entityManager->persist($question);

        $relation = new CQuizRelQuestion();
        $relation
            ->setQuiz($quiz)
            ->setQuestion($question)
            ->setQuestionOrder($questionOrder)
        ;
        $this->entityManager->persist($relation);

        return $question;
    }

    /**
     * @param array<int, string> $answers
     * @param array<int, int> $correctIndexes
     */
    private function createChoiceAnswers(CQuizQuestion $question, array $answers, array $correctIndexes, float $score, string $feedback): void
    {
        foreach ($answers as $position => $answerText) {
            $isCorrect = \in_array((int) $position, $correctIndexes, true);
            $answer = new CQuizAnswer();
            $answer
                ->setQuestion($question)
                ->setAnswer((string) $answerText)
                ->setCorrect($isCorrect ? 1 : 0)
                ->setComment($isCorrect ? $feedback : '')
                ->setPonderation($isCorrect ? $score : 0.0)
                ->setPosition((int) $position)
            ;
            $this->entityManager->persist($answer);
        }
    }

    /**
     * @param array<int, array<string, string>> $answers
     * @param array<string, mixed> $questionData
     */
    private function createExcelAnswers(
        CQuizQuestion $question,
        int $questionType,
        array $answers,
        array $questionData,
        bool $useCustomScore,
        ?float $correctScore,
        ?float $incorrectScore,
        string $description,
    ): void {
        match ($questionType) {
            self::FREE_ANSWER => $this->updateQuestionPonderation($question, $this->scoreFromValue($questionData['score'])),
            self::FILL_IN_BLANKS => $this->createFillInBlankAnswer($question, $answers, $description),
            self::MATCHING => $this->createMatchingAnswers($question, $answers, $this->scoreFromValue($questionData['score'])),
            self::GLOBAL_MULTIPLE_ANSWER => $this->createMultipleChoiceAnswers($question, $answers, $questionData, $useCustomScore, $correctScore, $incorrectScore, true),
            self::MULTIPLE_ANSWER, self::UNIQUE_ANSWER => $this->createMultipleChoiceAnswers($question, $answers, $questionData, $useCustomScore, $correctScore, $incorrectScore, false),
            default => $this->createMultipleChoiceAnswers($question, $answers, $questionData, $useCustomScore, $correctScore, $incorrectScore, false),
        };
    }

    private function updateQuestionPonderation(CQuizQuestion $question, float $ponderation): void
    {
        $question->setPonderation($ponderation);
    }

    /**
     * @param array<int, array<string, string>> $answers
     * @param array<string, mixed> $questionData
     */
    private function createMultipleChoiceAnswers(
        CQuizQuestion $question,
        array $answers,
        array $questionData,
        bool $useCustomScore,
        ?float $correctScore,
        ?float $incorrectScore,
        bool $globalMultiple,
    ): void {
        $total = 0.0;
        $globalScore = $this->scoreFromValue($questionData['score']);
        $numberRightAnswers = $this->countRightAnswers($answers);
        $position = 1;

        foreach ($answers as $answerData) {
            $answerValue = (string) ($answerData['data'] ?? '');
            $extra = (string) ($answerData['extra'] ?? '');
            $correct = 'x' === strtolower($extra);
            $score = 0.0;
            $comment = $correct ? (string) $questionData['feedbackTrue'] : (string) $questionData['feedbackFalse'];

            if ($correct) {
                $score = $globalScore;
            } elseif (is_numeric($extra)) {
                $score = (float) $extra;
            }

            if ($useCustomScore) {
                $score = $correct ? (float) ($correctScore ?? 0.0) : (float) ($incorrectScore ?? 0.0);
            }

            if ($globalMultiple) {
                if ($correct) {
                    $score = abs($globalScore);
                } else {
                    $score = 'x' === (string) $questionData['noNegativeScore'] ? 0.0 : -abs($globalScore);
                }
                $score /= max(1, $numberRightAnswers);
            }

            $answer = new CQuizAnswer();
            $answer
                ->setQuestion($question)
                ->setAnswer($answerValue)
                ->setCorrect($correct ? 1 : 0)
                ->setComment($comment)
                ->setPonderation($score)
                ->setPosition($position)
            ;
            $this->entityManager->persist($answer);

            if ($correct) {
                $total += $score;
            }
            ++$position;
        }

        $question->setPonderation($globalMultiple ? $globalScore : $total);
    }

    /**
     * @param array<int, array<string, string>> $answers
     */
    private function createFillInBlankAnswer(CQuizQuestion $question, array $answers, string $description): void
    {
        $scores = [];
        $sizes = [];
        $globalScore = 0.0;
        foreach ($answers as $answerData) {
            $score = $this->scoreFromValue($answerData['extra'] ?? null);
            $globalScore += $score;
            $scores[] = (string) $score;
            $sizes[] = '200';
        }

        $answer = new CQuizAnswer();
        $answer
            ->setQuestion($question)
            ->setAnswer($description.'::'.implode(',', $scores).':'.implode(',', $sizes).':0@')
            ->setCorrect(0)
            ->setComment('')
            ->setPonderation($globalScore)
            ->setPosition(1)
        ;
        $this->entityManager->persist($answer);
        $question->setPonderation($globalScore);
    }

    /**
     * @param array<int, array<string, string>> $answers
     */
    private function createMatchingAnswers(CQuizQuestion $question, array $answers, float $globalScore): void
    {
        $position = 1;
        foreach ($answers as $answerData) {
            $answer = new CQuizAnswer();
            $answer
                ->setQuestion($question)
                ->setAnswer((string) ($answerData['extra'] ?? ''))
                ->setCorrect(0)
                ->setComment('')
                ->setPonderation(0.0)
                ->setPosition($position)
            ;
            $this->entityManager->persist($answer);
            ++$position;
        }

        $counter = 1;
        foreach ($answers as $answerData) {
            $answer = new CQuizAnswer();
            $answer
                ->setQuestion($question)
                ->setAnswer((string) ($answerData['data'] ?? ''))
                ->setCorrect($counter)
                ->setComment(' ')
                ->setPonderation($globalScore)
                ->setPosition($position)
            ;
            $this->entityManager->persist($answer);
            ++$counter;
            ++$position;
        }

        $question->setPonderation($globalScore);
    }

    private function scoreFromValue(mixed $value): float
    {
        $value = str_replace(',', '.', trim((string) $value));
        if ('' === $value || !is_numeric($value)) {
            return 0.0;
        }

        return (float) $value;
    }

    /**
     * @param array<int, array<string, string>> $answers
     */
    private function countRightAnswers(array $answers): int
    {
        $count = 0;
        foreach ($answers as $answerData) {
            if ('x' === strtolower((string) ($answerData['extra'] ?? ''))) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @param array<int, array<string, string>> $answers
     */
    private function normalizeExcelQuestionType(mixed $questionType, array $answers): int
    {
        $questionType = trim((string) $questionType);
        if ('' !== $questionType) {
            $type = (int) $questionType;

            return \in_array($type, [self::UNIQUE_ANSWER, self::MULTIPLE_ANSWER, self::FILL_IN_BLANKS, self::MATCHING, self::FREE_ANSWER, self::GLOBAL_MULTIPLE_ANSWER], true)
                ? $type
                : self::UNIQUE_ANSWER;
        }

        return $this->detectExcelQuestionType($answers);
    }

    /**
     * @param array<int, array<string, string>> $answers
     */
    private function detectExcelQuestionType(array $answers): int
    {
        $correct = 0;
        $isNumeric = false;

        if ([] === $answers) {
            return self::FREE_ANSWER;
        }

        foreach ($answers as $answerData) {
            $extra = (string) ($answerData['extra'] ?? '');
            if ('x' === strtolower($extra)) {
                ++$correct;
                continue;
            }

            if (is_numeric($extra)) {
                $isNumeric = true;
            }
        }

        if (1 === $correct) {
            return self::UNIQUE_ANSWER;
        }

        if (1 < $correct) {
            return $isNumeric ? self::MULTIPLE_ANSWER : self::GLOBAL_MULTIPLE_ANSWER;
        }

        return self::FREE_ANSWER;
    }

    private function findOrCreateQuestionCategory(string $categoryTitle, Course $course, ?Session $session): ?CQuizQuestionCategory
    {
        $categoryTitle = trim($categoryTitle);
        if ('' === $categoryTitle) {
            return null;
        }

        $existingCategory = $this->questionCategoryRepository->findCourseResourceByTitle($categoryTitle, $course->getResourceNode(), $course);
        if ($existingCategory instanceof CQuizQuestionCategory) {
            return $existingCategory;
        }

        $category = new CQuizQuestionCategory();
        $category
            ->setTitle($categoryTitle)
            ->setDescription('')
            ->setParent($course)
            ->addCourseLink($course, $session)
        ;
        $this->questionCategoryRepository->create($category);

        return $category;
    }
}
