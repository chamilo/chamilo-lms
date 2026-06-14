<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionCategoryRepository;
use DOMDocument;
use DOMElement;
use DOMNode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use ZipArchive;

/**
 * Modern IMS/QTI 2 importer for the subset supported by the legacy Chamilo importer.
 *
 * This service intentionally ports the legacy parsing rules instead of calling
 * the legacy importer file.
 */
final readonly class ExerciseQti2ImportService
{
    private const UNIQUE_ANSWER = 1;
    private const MULTIPLE_ANSWER = 2;
    private const FILL_IN_BLANKS = 3;
    private const FREE_ANSWER = 5;

    /**
     * @var array<int, string>
     */
    private const SUPPORTED_TYPES = [
        self::UNIQUE_ANSWER => 'Unique answer',
        self::MULTIPLE_ANSWER => 'Multiple answer',
        self::FREE_ANSWER => 'Free answer',
        self::FILL_IN_BLANKS => 'Fill in blanks',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CQuizQuestionCategoryRepository $questionCategoryRepository,
    ) {}

    /**
     * @return array{quiz: CQuiz, importedCount: int, skippedCount: int, errors: array<int, string>}
     */
    public function import(UploadedFile $uploadedFile, Course $course, ?Session $session): array
    {
        if (!$uploadedFile->isValid()) {
            throw new BadRequestHttpException('The uploaded file is not valid.');
        }

        $extension = strtolower((string) $uploadedFile->getClientOriginalExtension());
        if ('zip' !== $extension) {
            throw new BadRequestHttpException('You must upload a .zip file.');
        }

        if (!class_exists(ZipArchive::class)) {
            throw new BadRequestHttpException('ZIP support is not available on this platform.');
        }

        $package = $this->readQti2Package($uploadedFile);
        if ([] === $package['questions']) {
            throw new BadRequestHttpException('No valid QTI2 questions were found in the uploaded file.');
        }

        $quiz = $this->createExercise(
            '' !== $package['name'] ? $package['name'] : $this->buildExerciseTitle($uploadedFile),
            $course,
            $session,
            (string) $package['description'],
            'Random' === $package['orderType'],
        );

        $result = $this->createQuestions($quiz, $package['questions'], $course, $session);
        if (0 === $result['importedCount']) {
            throw new BadRequestHttpException('No valid QTI2 questions were imported.');
        }

        $this->entityManager->flush();

        return [
            'quiz' => $quiz,
            'importedCount' => $result['importedCount'],
            'skippedCount' => $result['skippedCount'],
            'errors' => $result['errors'],
        ];
    }

    /**
     * @return array{name: string, description: string, orderType: ?string, questions: array<string, array<string, mixed>>}
     */
    private function readQti2Package(UploadedFile $uploadedFile): array
    {
        $zip = new ZipArchive();
        if (true !== $zip->open($uploadedFile->getPathname())) {
            throw new BadRequestHttpException('The uploaded ZIP file could not be opened.');
        }

        $package = [
            'name' => $this->buildExerciseTitle($uploadedFile),
            'description' => '',
            'orderType' => null,
            'questions' => [],
        ];
        $fileFound = false;

        try {
            for ($index = 0; $index < $zip->numFiles; ++$index) {
                $name = (string) $zip->getNameIndex($index);
                if ('' === $name || str_ends_with($name, '/')) {
                    continue;
                }

                $content = $zip->getFromIndex($index);
                if (!is_string($content) || '' === trim($content)) {
                    continue;
                }

                if (!$this->isQtiQuestionBank($content)) {
                    continue;
                }

                $fileFound = true;
                $this->parseQti2Xml($content, $package);
            }
        } finally {
            $zip->close();
        }

        if (!$fileFound) {
            throw new BadRequestHttpException('NoXMLFileFoundInTheZip');
        }

        return $package;
    }

    private function buildExerciseTitle(UploadedFile $uploadedFile): string
    {
        $baseName = $uploadedFile->getClientOriginalName();
        $baseName = '' !== $baseName ? $baseName : 'qti2-import.zip';
        $baseName = preg_replace('/\.zip$/i', '', $baseName) ?? $baseName;

        return trim($baseName) ?: 'QTI2 import';
    }

    private function isQtiQuestionBank(string $content): bool
    {
        return 1 === preg_match('/ims_qtiasiv(\d)p(\d)/', $content);
    }

    /**
     * @param array{name: string, description: string, orderType: ?string, questions: array<string, array<string, mixed>>} $package
     */
    private function parseQti2Xml(string $content, array &$package): void
    {
        $content = $this->stripGivenTags($content, ['p', 'front']);
        $version = [];
        $matched = preg_match('/ims_qtiasiv(\d)p(\d)/', $content, $version);
        $mainVersion = $matched ? (int) $version[1] : 2;
        if (2 !== $mainVersion) {
            throw new BadRequestHttpException('Unsupported IMS/QTI version.');
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            if (!$document->loadXML($content, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
                throw new BadRequestHttpException('Error opening XML file');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $nodes = $document->getElementsByTagName('*');
        $currentQuestionIdent = '';
        $currentAnswerId = '';
        $currentQuestionItemBody = '';
        $cardinality = '';
        $currentMatchSet = null;
        $nonHtmlTagsToAvoid = [
            'prompt',
            'simpleChoice',
            'choiceInteraction',
            'inlineChoiceInteraction',
            'inlineChoice',
            'soMPLEMATCHSET',
            'simpleAssociableChoice',
            'textEntryInteraction',
            'feedbackInline',
            'matchInteraction',
            'extendedTextInteraction',
            'itemBody',
            'br',
            'img',
        ];

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $nodeName = $this->localName($node);
            switch ($nodeName) {
                case 'assessmentItem':
                    $currentQuestionIdent = $node->getAttribute('identifier');
                    if ('' === $currentQuestionIdent) {
                        $currentQuestionIdent = 'question_'.(count($package['questions']) + 1);
                    }

                    $package['questions'][$currentQuestionIdent] = [
                        'answer' => [],
                        'correct_answers' => [],
                        'title' => $node->getAttribute('title'),
                        'category' => $node->getAttribute('category'),
                        'type' => null,
                        'subtype' => null,
                        'description' => null,
                        'response_text' => null,
                        'fib_options' => [],
                        'statement' => '',
                    ];
                    $currentMatchSet = null;
                    break;

                case 'section':
                    $title = trim($node->getAttribute('title'));
                    if ('' !== $title) {
                        $package['name'] = $this->formatPlainText($title);
                    }
                    break;

                case 'responseDeclaration':
                    if (!$this->hasCurrentQuestion($package, $currentQuestionIdent)) {
                        break;
                    }

                    $currentAnswerId = $node->getAttribute('identifier');
                    $cardinality = $node->getAttribute('cardinality');

                    if (str_starts_with($currentAnswerId, 'fill_')) {
                        $package['questions'][$currentQuestionIdent]['type'] = self::FILL_IN_BLANKS;
                        $package['questions'][$currentQuestionIdent]['subtype'] = 'TEXTFIELD_FILL';
                        break;
                    }

                    if ('multiple' === $cardinality) {
                        $package['questions'][$currentQuestionIdent]['type'] = self::MULTIPLE_ANSWER;
                    } elseif ('single' === $cardinality) {
                        $package['questions'][$currentQuestionIdent]['type'] = self::UNIQUE_ANSWER;
                    }
                    break;

                case 'inlineChoiceInteraction':
                    if (!$this->hasCurrentQuestion($package, $currentQuestionIdent)) {
                        break;
                    }
                    $package['questions'][$currentQuestionIdent]['type'] = self::FILL_IN_BLANKS;
                    $package['questions'][$currentQuestionIdent]['subtype'] = 'LISTBOX_FILL';
                    $currentAnswerId = $node->getAttribute('responseIdentifier');
                    break;

                case 'inlineChoice':
                    if (!$this->hasCurrentQuestion($package, $currentQuestionIdent)) {
                        break;
                    }

                    $parent = $node->parentNode;
                    if (!$parent instanceof DOMElement || 'inlineChoiceInteraction' !== $this->localName($parent)) {
                        break;
                    }

                    $responseId = $parent->getAttribute('responseIdentifier');
                    if ('' === $responseId) {
                        break;
                    }

                    $correctChoiceId = $package['questions'][$currentQuestionIdent]['correct_answers'][$responseId] ?? null;
                    $choiceId = $node->getAttribute('identifier');
                    $choiceText = trim($node->textContent);

                    if (!isset($package['questions'][$currentQuestionIdent]['fib_options'][$responseId])) {
                        $package['questions'][$currentQuestionIdent]['fib_options'][$responseId] = [
                            'correct' => null,
                            'wrongs' => [],
                        ];
                    }

                    if (null !== $correctChoiceId && $choiceId === $correctChoiceId) {
                        $package['questions'][$currentQuestionIdent]['fib_options'][$responseId]['correct'] = $choiceText;
                    } else {
                        $package['questions'][$currentQuestionIdent]['fib_options'][$responseId]['wrongs'][] = $choiceText;
                    }
                    break;

                case 'textEntryInteraction':
                    if (!$this->hasCurrentQuestion($package, $currentQuestionIdent)) {
                        break;
                    }
                    $package['questions'][$currentQuestionIdent]['type'] = self::FILL_IN_BLANKS;
                    $package['questions'][$currentQuestionIdent]['subtype'] = 'TEXTFIELD_FILL';
                    $package['questions'][$currentQuestionIdent]['response_text'] = $currentQuestionItemBody;
                    break;

                case 'matchInteraction':
                    if ($this->hasCurrentQuestion($package, $currentQuestionIdent)) {
                        // The legacy importer detected matching but did not save it in the supported list.
                        $package['questions'][$currentQuestionIdent]['type'] = 4;
                    }
                    break;

                case 'extendedTextInteraction':
                    if (!$this->hasCurrentQuestion($package, $currentQuestionIdent)) {
                        break;
                    }
                    $package['questions'][$currentQuestionIdent]['type'] = self::FREE_ANSWER;
                    $package['questions'][$currentQuestionIdent]['description'] = trim($node->textContent);
                    break;

                case 'simpleMatchSet':
                    if (!$this->hasCurrentQuestion($package, $currentQuestionIdent)) {
                        break;
                    }
                    $currentMatchSet = null === $currentMatchSet ? 1 : $currentMatchSet + 1;
                    $package['questions'][$currentQuestionIdent]['answer'][$currentMatchSet] = [];
                    break;

                case 'simpleAssociableChoice':
                    if (!$this->hasCurrentQuestion($package, $currentQuestionIdent) || null === $currentMatchSet) {
                        break;
                    }
                    $choiceId = $node->getAttribute('identifier');
                    $package['questions'][$currentQuestionIdent]['answer'][$currentMatchSet][$choiceId] = trim($node->textContent);
                    break;

                case 'simpleChoice':
                    if (!$this->hasCurrentQuestion($package, $currentQuestionIdent)) {
                        break;
                    }
                    $currentAnswerId = $node->getAttribute('identifier');
                    if ('' === $currentAnswerId) {
                        break;
                    }
                    $simpleChoiceValue = $this->extractSimpleChoiceText($node);
                    $package['questions'][$currentQuestionIdent]['answer'][$currentAnswerId]['value'] = ($package['questions'][$currentQuestionIdent]['answer'][$currentAnswerId]['value'] ?? '').$simpleChoiceValue;
                    break;

                case 'mapEntry':
                    if (!$this->hasCurrentQuestion($package, $currentQuestionIdent)) {
                        break;
                    }
                    $parentName = $this->localName($node->parentNode);
                    if ('mapping' === $parentName || 'mapEntry' === $parentName) {
                        $answerId = $node->getAttribute('mapKey');
                        if ('' !== $answerId) {
                            $package['questions'][$currentQuestionIdent]['weighting'][$answerId] = $node->getAttribute('mappedValue');
                        }
                    }
                    break;

                case 'mapping':
                    if (!$this->hasCurrentQuestion($package, $currentQuestionIdent)) {
                        break;
                    }
                    $defaultValue = $node->getAttribute('defaultValue');
                    if ('' !== $defaultValue) {
                        $package['questions'][$currentQuestionIdent]['default_weighting'] = $defaultValue;
                    }
                    break;

                case 'itemBody':
                    if (!$this->hasCurrentQuestion($package, $currentQuestionIdent)) {
                        break;
                    }
                    $currentQuestionItemBody = $this->buildItemBodyText($node, $nonHtmlTagsToAvoid);
                    $questionType = (int) ($package['questions'][$currentQuestionIdent]['type'] ?? 0);

                    if (self::FILL_IN_BLANKS === $questionType) {
                        $candidate = (string) $currentQuestionItemBody;
                        $hasPlaceholders = false !== strpos($candidate, '**claroline_start**');
                        $hasRealContent = '' !== trim(strip_tags($candidate));
                        if ($hasPlaceholders || $hasRealContent) {
                            $package['questions'][$currentQuestionIdent]['response_text'] = $candidate;
                        }
                    } elseif (self::FREE_ANSWER === $questionType) {
                        $candidate = trim($currentQuestionItemBody);
                        if ('' !== $candidate) {
                            $package['questions'][$currentQuestionIdent]['description'] = $candidate;
                        }
                    } else {
                        $package['questions'][$currentQuestionIdent]['statement'] = $currentQuestionItemBody;
                    }
                    break;

                case 'order':
                    $orderType = $node->getAttribute('order_type');
                    if ('' !== $orderType) {
                        $package['orderType'] = $orderType;
                    }
                    break;

                case 'feedbackInline':
                    if (!$this->hasCurrentQuestion($package, $currentQuestionIdent) || '' === $currentAnswerId) {
                        break;
                    }
                    $package['questions'][$currentQuestionIdent]['answer'][$currentAnswerId]['feedback'] = ($package['questions'][$currentQuestionIdent]['answer'][$currentAnswerId]['feedback'] ?? '').trim($node->textContent);
                    break;

                case 'value':
                    if (!$this->hasCurrentQuestion($package, $currentQuestionIdent)) {
                        break;
                    }
                    $parentName = $this->localName($node->parentNode);
                    if ('correctResponse' === $parentName) {
                        $nodeValue = trim($node->textContent);
                        if ('' !== $currentAnswerId && str_starts_with($currentAnswerId, 'fill_')) {
                            $package['questions'][$currentQuestionIdent]['type'] = self::FILL_IN_BLANKS;
                            $package['questions'][$currentQuestionIdent]['subtype'] = $package['questions'][$currentQuestionIdent]['subtype'] ?? 'TEXTFIELD_FILL';
                            $package['questions'][$currentQuestionIdent]['correct_answers'][$currentAnswerId] = $nodeValue;
                            if (empty($package['questions'][$currentQuestionIdent]['response_text'])) {
                                $package['questions'][$currentQuestionIdent]['response_text'] = $nodeValue;
                            }
                        } elseif ('single' === $cardinality) {
                            $package['questions'][$currentQuestionIdent]['correct_answers'][$nodeValue] = $nodeValue;
                        } else {
                            $package['questions'][$currentQuestionIdent]['correct_answers'][] = $nodeValue;
                        }
                    }

                    $grandParentName = $this->localName($node->parentNode?->parentNode);
                    if ('outcomeDeclaration' === $grandParentName) {
                        $nodeValue = trim($node->textContent);
                        if ('' !== $nodeValue) {
                            $package['questions'][$currentQuestionIdent]['weighting'][0] = $nodeValue;
                        }
                    }
                    break;

                case 'mattext':
                    $nodeValue = trim($node->textContent);
                    if ('' !== $nodeValue) {
                        $package['description'] = $nodeValue;
                    }
                    break;

                case 'prompt':
                    if ($this->hasCurrentQuestion($package, $currentQuestionIdent)) {
                        $description = $this->sanitizePrompt(trim($node->textContent));
                        if ('' !== $description) {
                            $package['questions'][$currentQuestionIdent]['description'] = $description;
                        }
                    }
                    break;
            }
        }

        $this->postProcessFibQuestions($package['questions']);
    }

    /**
     * @param array{name: string, description: string, orderType: ?string, questions: array<string, array<string, mixed>>} $package
     */
    private function hasCurrentQuestion(array $package, string $currentQuestionIdent): bool
    {
        return '' !== $currentQuestionIdent && isset($package['questions'][$currentQuestionIdent]);
    }

    /**
     * @param array<int, string> $tags
     */
    private function stripGivenTags(string $content, array $tags): string
    {
        foreach ($tags as $tag) {
            $content = preg_replace(sprintf('#</?%s(?:\s[^>]*)?>#i', preg_quote($tag, '#')), '', $content) ?? $content;
        }

        return $content;
    }

    private function localName(?DOMNode $node): string
    {
        if (!$node instanceof DOMNode) {
            return '';
        }

        return $node->localName ?: $node->nodeName;
    }

    private function extractSimpleChoiceText(DOMElement $node): string
    {
        $value = '';
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement && 'feedbackInline' === $this->localName($childNode)) {
                continue;
            }
            $value .= $childNode->textContent;
        }

        return trim($value);
    }

    /**
     * @param array<int, string> $nonHtmlTagsToAvoid
     */
    private function buildItemBodyText(DOMElement $node, array $nonHtmlTagsToAvoid): string
    {
        $content = '';
        foreach ($node->childNodes as $childNode) {
            if ('#text' === $childNode->nodeName) {
                continue;
            }

            $childName = $this->localName($childNode);
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            if (!in_array($childName, $nonHtmlTagsToAvoid, true)) {
                $content .= '<'.$childName;
                foreach ($childNode->attributes ?? [] as $attribute) {
                    $content .= ' '.$attribute->nodeName.'="'.htmlspecialchars($attribute->nodeValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"';
                }
                $content .= '>'.$childNode->textContent.'</'.$childName.'>';
                continue;
            }

            if ('inlineChoiceInteraction' === $childName || 'textEntryInteraction' === $childName) {
                $responseId = $childNode->getAttribute('responseIdentifier');
                $content .= '**claroline_start**'.$responseId.'**claroline_end**';
                continue;
            }

            if ('br' === $childName) {
                $content .= '<br>';
            }
        }

        $firstChild = $node->firstChild;
        if ($firstChild instanceof DOMNode && '#text' === $firstChild->nodeName) {
            $firstText = trim($firstChild->nodeValue ?? '');
            if ('' !== $firstText) {
                $content .= $firstText;
            }
        }

        return $content;
    }

    private function sanitizePrompt(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('#<\s*(script|style)[^>]*>.*?<\s*/\s*\1\s*>#is', '', $text) ?? $text;
        $text = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $text) ?? $text;

        return trim($text);
    }

    /**
     * @param array<string, array<string, mixed>> $questions
     */
    private function postProcessFibQuestions(array &$questions): void
    {
        foreach ($questions as &$question) {
            if ((int) ($question['type'] ?? 0) !== self::FILL_IN_BLANKS) {
                continue;
            }

            $text = (string) ($question['response_text'] ?? '');
            if ('' === $text) {
                continue;
            }

            $subtype = $question['subtype'] ?? null;
            if ('LISTBOX_FILL' === $subtype) {
                $options = is_array($question['fib_options'] ?? null) ? $question['fib_options'] : [];
                foreach ($options as $responseId => $data) {
                    $correct = (string) ($data['correct'] ?? '');
                    $wrongs = is_array($data['wrongs'] ?? null) ? $data['wrongs'] : [];
                    $final = [];
                    if ('' !== $correct) {
                        $final[] = $correct;
                    }
                    foreach ($wrongs as $wrong) {
                        $wrong = trim((string) $wrong);
                        if ('' === $wrong || $wrong === $correct) {
                            continue;
                        }
                        $final[] = $wrong;
                    }
                    $text = str_replace('**claroline_start**'.$responseId.'**claroline_end**', '['.implode('|', $final).']', $text);
                }
            } else {
                $corrects = is_array($question['correct_answers'] ?? null) ? $question['correct_answers'] : [];
                foreach ($corrects as $responseId => $answerValue) {
                    if (!str_starts_with((string) $responseId, 'fill_')) {
                        continue;
                    }
                    $text = str_replace('**claroline_start**'.$responseId.'**claroline_end**', '['.trim((string) $answerValue).']', $text);
                }
            }

            $question['response_text'] = preg_replace('/\*\*claroline_start\*\*fill_[^*]+\*\*claroline_end\*\*/', '[]', $text) ?? $text;
        }
        unset($question);
    }

    private function createExercise(string $title, Course $course, ?Session $session, string $description, bool $randomQuestions): CQuiz
    {
        $quiz = new CQuiz();
        $quiz
            ->setTitle($this->formatPlainText($title))
            ->setDescription('' !== trim($description) ? $this->formatPlainText(strip_tags($description)) : '')
            ->setType(CQuiz::ONE_PER_PAGE)
            ->setRandom($randomQuestions ? -1 : 0)
            ->setRandomAnswers(false)
            ->setResultsDisabled(0)
            ->setMaxAttempt(1)
            ->setFeedbackType(0)
            ->setExpiredTime(0)
            ->setPropagateNeg(0)
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

        if ($randomQuestions) {
            $quiz->setQuestionSelectionType(2);
        }

        $this->entityManager->persist($quiz);

        return $quiz;
    }

    /**
     * @param array<string, array<string, mixed>> $questions
     *
     * @return array{importedCount: int, skippedCount: int, errors: array<int, string>}
     */
    private function createQuestions(CQuiz $quiz, array $questions, Course $course, ?Session $session): array
    {
        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];
        $questionOrder = 1;

        foreach ($questions as $identifier => $questionData) {
            $questionType = (int) ($questionData['type'] ?? 0);
            if (!isset(self::SUPPORTED_TYPES[$questionType])) {
                ++$skippedCount;
                continue;
            }

            $title = $this->formatPlainText(strip_tags((string) ($questionData['title'] ?? '')));
            if ('' === $title) {
                ++$skippedCount;
                $errors[] = sprintf('QTI question %s skipped because it has no title.', (string) $identifier);
                continue;
            }

            $description = (string) ($questionData['description'] ?? '');
            $question = $this->createQuestion($quiz, $course, $session, $title, $description, $questionType, 0.0, $questionOrder);
            $category = $this->findOrCreateQuestionCategory((string) ($questionData['category'] ?? ''), $course, $session);
            if ($category instanceof CQuizQuestionCategory) {
                $question->updateCategory($category);
            }

            if (self::FILL_IN_BLANKS === $questionType) {
                [$answerString, $totalWeight] = $this->buildFibAnswerString($questionData);
                $question->setPonderation($totalWeight);
                $this->createSingleAnswer($question, $answerString, 0, '', $totalWeight, 1);
            } elseif (self::FREE_ANSWER === $questionType) {
                $question->setPonderation($this->scoreFromValue($questionData['weighting'][0] ?? 0));
            } else {
                $this->createChoiceAnswers($question, $questionData);
            }

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
     * @param array<string, mixed> $questionData
     */
    private function createChoiceAnswers(CQuizQuestion $question, array $questionData): void
    {
        $answerList = is_array($questionData['answer'] ?? null) ? $questionData['answer'] : [];
        $correctAnswersRaw = is_array($questionData['correct_answers'] ?? null) ? $questionData['correct_answers'] : [];
        $correctAnswerIds = array_values($correctAnswersRaw);
        $defaultWeight = $this->scoreFromValue($questionData['default_weighting'] ?? 0);
        $totalCorrectWeight = 0.0;
        $position = 1;

        foreach ($answerList as $key => $answerData) {
            $answerData = is_array($answerData) ? $answerData : [];
            $answerValue = $this->formatHtml((string) ($answerData['value'] ?? ''));
            $answerFeedback = $this->formatHtml((string) ($answerData['feedback'] ?? ''));
            $isCorrect = in_array((string) $key, array_map('strval', $correctAnswerIds), true);
            $weight = $defaultWeight;
            if (isset($questionData['weighting']) && is_array($questionData['weighting']) && array_key_exists((string) $key, $questionData['weighting'])) {
                $weight = $this->scoreFromValue($questionData['weighting'][(string) $key]);
            }

            $this->createSingleAnswer($question, $answerValue, $isCorrect ? 1 : 0, $answerFeedback, $weight, $position);
            if ($isCorrect) {
                $totalCorrectWeight += $weight;
            }
            ++$position;
        }

        $question->setPonderation($totalCorrectWeight);
    }

    private function createSingleAnswer(CQuizQuestion $question, string $answerText, int $correct, string $comment, float $weight, int $position): void
    {
        $answer = new CQuizAnswer();
        $answer
            ->setQuestion($question)
            ->setAnswer($answerText)
            ->setCorrect($correct)
            ->setComment($comment)
            ->setPonderation($weight)
            ->setPosition($position)
        ;
        $this->entityManager->persist($answer);
    }

    /**
     * @param array<string, mixed> $questionData
     *
     * @return array{0: string, 1: float}
     */
    private function buildFibAnswerString(array $questionData): array
    {
        $text = trim((string) ($questionData['response_text'] ?? ''));
        $looksEmptyOrWrong = '' === $text
            || (false === strpos($text, '[') && false === strpos($text, '::') && false === strpos($text, '**claroline_start**'));

        if ($looksEmptyOrWrong && isset($questionData['correct_answers']) && is_array($questionData['correct_answers'])) {
            foreach ($questionData['correct_answers'] as $key => $value) {
                if (str_starts_with((string) $key, 'fill_')) {
                    $text = trim((string) $value);
                    break;
                }
            }
        }

        $text = str_replace("\xc2\xa0", ' ', $this->formatHtml($text));
        $text = trim($text);

        $existing = $this->parseExistingFibMeta($text);
        if (null !== $existing) {
            return $existing;
        }

        $text = str_replace('::', '', $text);
        $text = str_replace("\xc2\xa0", ' ', $text);
        $weightsMap = is_array($questionData['weighting'] ?? null) ? $questionData['weighting'] : [];
        preg_match_all('/\[[^\]]*\]/', $text, $matches);
        $weights = [];
        $sizes = [];
        $total = 0.0;

        foreach ($matches[0] ?? [] as $blankRaw) {
            $inside = trim((string) $blankRaw, '[]');
            $correct = $inside;
            if (false !== strpos($correct, '||')) {
                $parts = explode('||', $correct);
                $correct = $parts[0] ?? $correct;
            } elseif (false !== strpos($correct, '|')) {
                $parts = explode('|', $correct);
                $correct = $parts[0] ?? $correct;
            }
            $correct = trim((string) $correct);
            $weight = 1.0;
            if ('' !== $correct && array_key_exists($correct, $weightsMap)) {
                $weight = $this->scoreFromValue($weightsMap[$correct]);
            }
            $weights[] = $weight;
            $sizes[] = 200;
            $total += $weight;
        }

        if ([] !== $weights) {
            $text .= '::'.implode(',', $weights).':'.implode(',', $sizes);
        }

        $text .= ':0@0';

        return [$text, $total];
    }

    /**
     * @return array{0: string, 1: float}|null
     */
    private function parseExistingFibMeta(string $text): ?array
    {
        $position = strrpos(trim($text), '::');
        if (false === $position) {
            return null;
        }

        $tail = substr($text, $position + 2);
        if (1 === preg_match('/^([0-9]+(?:\.[0-9]+)?(?:,[0-9]+(?:\.[0-9]+)?)*)\:([0-9]+(?:,[0-9]+)*)\:(\d+)@(\d+)$/', $tail, $matches)) {
            $weights = array_map('floatval', explode(',', $matches[1]));

            return [$text, array_sum($weights)];
        }

        if (1 === preg_match('/^([0-9]+(?:\.[0-9]+)?(?:,[0-9]+(?:\.[0-9]+)?)*)\:([0-9]+(?:,[0-9]+)*)\:(\d+)@$/', $tail, $matches)) {
            $weights = array_map('floatval', explode(',', $matches[1]));

            return [$text.'0', array_sum($weights)];
        }

        return null;
    }

    private function scoreFromValue(mixed $value): float
    {
        $value = str_replace(',', '.', trim((string) $value));
        if ('' === $value || !is_numeric($value)) {
            return 0.0;
        }

        return (float) $value;
    }

    private function formatPlainText(string $text): string
    {
        return trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function formatHtml(string $text): string
    {
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function findOrCreateQuestionCategory(string $categoryTitle, Course $course, ?Session $session): ?CQuizQuestionCategory
    {
        $categoryTitle = $this->formatPlainText(strip_tags($categoryTitle));
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
