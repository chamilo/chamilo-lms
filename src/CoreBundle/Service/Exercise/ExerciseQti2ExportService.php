<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use ZipArchive;

use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const LIBXML_NOERROR;
use const LIBXML_NONET;
use const LIBXML_NOWARNING;

/**
 * Modern IMS/QTI 2 exporter for the same exercise-level subset exported by legacy Chamilo.
 *
 * The legacy exercise QTI2 action only exported Unique answer, Multiple answer and Free answer
 * questions through export_question_qti(). Matching questions are exported as a matchInteraction and
 * Exact combination questions as a multiple choiceInteraction scored all-or-nothing
 * (Qti2ExactCombinationWriter). Other question types are skipped.
 */
final readonly class ExerciseQti2ExportService
{
    private const UNIQUE_ANSWER = 1;
    private const MULTIPLE_ANSWER = 2;
    private const FILL_IN_BLANKS = 3;
    private const MATCHING = 4;
    private const FREE_ANSWER = 5;
    private const MULTIPLE_ANSWER_COMBINATION = 9;
    private const UNIQUE_ANSWER_NO_OPTION = 10;
    private const UNIQUE_ANSWER_IMAGE = 17;
    private const HOTSPOT = 6;
    private const GRAPHIC_ORDER = 7;
    private const ORAL = 13;
    private const UPLOAD = 23;
    private const MULTIPLE_ANSWER_TRUE_FALSE = 11;
    private const MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE = 12;
    private const MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY = 22;
    private const MULTIPLE_ANSWER_DROPDOWN = 29;
    private const DRAGGABLE = 18;
    private const MATCHING_DRAGGABLE = 19;
    private const VISIBILITY_PUBLISHED = 2;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CidReqHelper $cidReqHelper,
        private UserHelper $userHelper,
        private CQuizRepository $quizRepository,
        private ?Qti2QuestionImageMedia $questionImageMedia = null,
    ) {}

    public function exportExerciseZip(int $exerciseId): BinaryFileResponse
    {
        $quiz = $this->getValidatedExercise($exerciseId);
        $zipPath = $this->createZipFile();
        $zip = new ZipArchive();
        if (true !== $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            throw new BadRequestHttpException('The QTI2 export archive could not be created.');
        }

        try {
            $xml = $this->buildExerciseXml(
                $quiz,
                $zip,
                $this->cidReqHelper->requireDoctrineCourseEntity(),
                $this->cidReqHelper->getDoctrineSessionEntity(),
                new Qti2MediaArchive()
            );
            $this->assertXmlCanBeParsed($xml);
            if (!$zip->addFromString(\sprintf('qti2export_%d.xml', $exerciseId), $xml)) {
                throw new BadRequestHttpException('The QTI2 XML could not be written to the archive.');
            }
        } catch (Throwable $error) {
            $zip->close();
            @unlink($zipPath);
            if ($error instanceof InvalidArgumentException) {
                throw new BadRequestHttpException('QTI image export failed: '.$error->getMessage(), $error);
            }

            throw $error;
        }
        if (!$zip->close()) {
            throw new BadRequestHttpException('The QTI2 export archive could not be finalized.');
        }

        $response = new BinaryFileResponse(new File($zipPath));
        $response->headers->set('Content-Type', 'application/zip');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, \sprintf('qti2_export_%d.zip', $exerciseId));
        $response->deleteFileAfterSend(true);

        return $response;
    }

    private function getValidatedExercise(int $exerciseId): CQuiz
    {
        if ($exerciseId <= 0) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        if (!$this->userHelper->isTeacherOfCurrentCourse()) {
            throw new AccessDeniedHttpException('You are not allowed to export this exercise.');
        }

        return $this->getExerciseFromCurrentContext(
            $exerciseId,
            $this->cidReqHelper->requireDoctrineCourseEntity(),
            $this->cidReqHelper->getDoctrineSessionEntity(),
        );
    }

    private function getExerciseFromCurrentContext(int $exerciseId, Course $course, ?Session $session): CQuiz
    {
        $context = $this->quizRepository->findInCourseContextWithVisibility($exerciseId, $course, $session, true);
        if (null === $context) {
            throw new NotFoundHttpException('The requested exercise was not found in the current course context.');
        }

        $visibility = $context['visibility'];
        if (0 !== $visibility && self::VISIBILITY_PUBLISHED !== $visibility && !$this->userHelper->isTeacherOfCurrentCourse()) {
            throw new AccessDeniedHttpException('The requested exercise is not visible.');
        }

        return $context['quiz'];
    }

    private function buildExerciseXml(CQuiz $quiz, ?ZipArchive $archive = null, ?Course $course = null, ?Session $session = null, ?Qti2MediaArchive $media = null): string
    {
        $exerciseId = (int) $quiz->getIid();
        $xml = '<?xml version = "1.0" encoding = "UTF-8" standalone = "no"?>'."\n";
        $xml .= '<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv2p1.dtd">'."\n";
        $xml .= '<questestinterop>'."\n";
        $xml .= '<section'."\n";
        $xml .= '            ident = "EXO_'.$exerciseId.'"'."\n";
        $xml .= '            title = "'.$this->formatDescription($quiz->getTitle()).'"'."\n";
        $xml .= '        >'."\n";
        $xml .= $this->exportDuration($quiz);
        $xml .= $this->exportPresentation($quiz);
        $xml .= $this->exportOrdering($quiz);

        foreach ($this->getExerciseQuestions($quiz) as $question) {
            $xml .= $this->exportQuestion($question, $archive, $course, $session, $media);
        }

        $xml .= '</section>'."\n";
        $xml .= '</questestinterop>'."\n";

        return $xml;
    }

    private function exportDuration(CQuiz $quiz): string
    {
        $duration = (int) ($quiz->getDuration() ?? 0);
        if ($duration <= 0) {
            return '';
        }

        $minutes = (int) floor($duration / 60);
        $seconds = $duration % 60;

        return '<duration>PT'.$minutes.'M'.$seconds.'S</duration>'."\n";
    }

    private function exportPresentation(CQuiz $quiz): string
    {
        return "<presentation_material><flow_mat><material>\n"
            .'  <mattext><![CDATA['.$this->formatDescription((string) $quiz->getDescription())."]]></mattext>\n"
            ."</material></flow_mat></presentation_material>\n";
    }

    private function exportOrdering(CQuiz $quiz): string
    {
        $random = (int) $quiz->getRandom();
        if ($random > 0) {
            return '<selection_ordering>'
                ."  <selection>\n"
                .'    <selection_number>'.$random."</selection_number>\n"
                ."  </selection>\n"
                .'  <order order_type="Random" />'
                ."\n</selection_ordering>\n";
        }

        return '<selection_ordering sequence_type="Normal">'."\n"
            ."  <selection />\n"
            ."</selection_ordering>\n";
    }

    /**
     * @return array<int, CQuizQuestion>
     */
    private function getExerciseQuestions(CQuiz $quiz): array
    {
        $relations = $this->entityManager->createQueryBuilder()
            ->select('relQuestion', 'question', 'answer')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->innerJoin('relQuestion.question', 'question')
            ->leftJoin('question.answers', 'answer')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->orderBy('relQuestion.questionOrder', 'ASC')
            ->addOrderBy('question.position', 'ASC')
            ->addOrderBy('answer.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $questions = [];
        foreach ($relations as $relation) {
            if (!$relation instanceof CQuizRelQuestion) {
                continue;
            }

            $question = $relation->getQuestion();
            if (!$this->isLegacyExportableQuestion($question)) {
                continue;
            }

            $questionId = (int) $question->getIid();
            if ($questionId > 0) {
                $questions[$questionId] = $question;
            }
        }

        return array_values($questions);
    }

    private function isLegacyExportableQuestion(CQuizQuestion $question): bool
    {
        return \in_array((int) $question->getType(), [self::UNIQUE_ANSWER, self::MULTIPLE_ANSWER, self::MULTIPLE_ANSWER_COMBINATION, self::UNIQUE_ANSWER_NO_OPTION, self::UNIQUE_ANSWER_IMAGE, self::HOTSPOT, self::GRAPHIC_ORDER, self::ORAL, self::UPLOAD, self::MULTIPLE_ANSWER_DROPDOWN, self::MULTIPLE_ANSWER_TRUE_FALSE, self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE, self::MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY, self::FILL_IN_BLANKS, self::FREE_ANSWER, self::MATCHING, self::MATCHING_DRAGGABLE, self::DRAGGABLE], true);
    }

    private function isMatchingQuestion(CQuizQuestion $question): bool
    {
        return \in_array((int) $question->getType(), [self::MATCHING, self::MATCHING_DRAGGABLE], true);
    }

    private function exportQuestion(CQuizQuestion $question, ?ZipArchive $archive = null, ?Course $course = null, ?Session $session = null, ?Qti2MediaArchive $media = null): string
    {
        $questionIdent = 'QST_'.(int) $question->getIid();
        if (\in_array((int) $question->getType(), [self::MULTIPLE_ANSWER_TRUE_FALSE, self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE, self::MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY], true)) {
            $xml = Qti2TrueFalseItem::write($question, $questionIdent);
            if (null === $xml) {
                throw new BadRequestHttpException('True/false question has invalid option references or scoring.');
            }

            return $xml;
        }
        $categoryTitle = $this->getQuestionCategoryTitle($question);
        $xml = '<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1"'."\n";
        $xml .= '                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'."\n";
        $xml .= '                xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p1 imsqti_v2p1.xsd"'."\n";
        $xml .= '                identifier="'.$questionIdent.'"'."\n";
        $xml .= '                title = "'.$this->escapeAttribute($this->formatText($question->getQuestion())).'"'."\n";
        $xml .= '                category = "'.$this->escapeAttribute($this->formatText($categoryTitle)).'"'."\n";
        if (self::MATCHING_DRAGGABLE === (int) $question->getType()) {
            $xml .= '                label="'.Qti2MatchingTypeMarker::DRAGGABLE_LABEL.'"'."\n";
        } elseif (self::UNIQUE_ANSWER_NO_OPTION === (int) $question->getType()) {
            $xml .= '                label="'.Qti2UniqueAnswerUnknownMarker::LABEL.'"'."\n";
        } elseif (self::MULTIPLE_ANSWER_DROPDOWN === (int) $question->getType()) {
            $xml .= '                label="chamilo:multiple-answer-dropdown"'."\n";
        } elseif (self::UNIQUE_ANSWER_IMAGE === (int) $question->getType()) {
            $xml .= '                label="'.Qti2QuestionImageMedia::LABEL.'"'."\n";
        } elseif ('' !== Qti2SpecialQuestionItem::label((int) $question->getType())) {
            $xml .= '                label="'.Qti2SpecialQuestionItem::label((int) $question->getType()).'"'."\n";
        }
        $xml .= '        >'."\n";
        if (\in_array((int) $question->getType(), [self::HOTSPOT, self::GRAPHIC_ORDER, self::ORAL, self::UPLOAD], true)) {
            $image = null;
            if (\in_array((int) $question->getType(), [self::HOTSPOT, self::GRAPHIC_ORDER], true)) {
                if (null === $archive || null === $this->questionImageMedia) {
                    throw new BadRequestHttpException('QTI hotspot export requires the question image storage service.');
                }
                $image = $this->questionImageMedia->exportQuestionImage($question, $archive, $media ??= new Qti2MediaArchive());
            }

            return $xml.Qti2SpecialQuestionItem::write($question, $questionIdent, $image).'</assessmentItem>'."\n";
        }
        if (self::FILL_IN_BLANKS === (int) $question->getType()) {
            return $xml.$this->exportFillBlanksItem($question).'</assessmentItem>'."\n";
        }
        if (self::DRAGGABLE === (int) $question->getType()) {
            return $xml.$this->exportOrderingItem($question, $questionIdent).'</assessmentItem>'."\n";
        }
        if (self::MULTIPLE_ANSWER_COMBINATION === (int) $question->getType()) {
            return $xml.$this->exportExactCombinationItem($question, $questionIdent).'</assessmentItem>'."\n";
        }
        $xml .= $this->exportQuestionResponseDeclaration($question, $questionIdent);
        $xml .= "  <itemBody>\n";
        $xml .= $this->exportQuestionResponses($question, $questionIdent, '', $archive, $course, $session, $media);
        $xml .= "  </itemBody>\n";
        $xml .= \in_array((int) $question->getType(), [self::UNIQUE_ANSWER_NO_OPTION, self::MULTIPLE_ANSWER_DROPDOWN], true)
            ? Qti2UniqueAnswerUnknownMarker::responseProcessing($questionIdent)
            : '  <responseProcessing template="http://www.imsglobal.org/question/qti_v2p1/rptemplates/map_correct"/>'."\n";
        $xml .= '</assessmentItem>'."\n";

        return $xml;
    }

    private function exportQuestionResponseDeclaration(CQuizQuestion $question, string $questionIdent): string
    {
        if (self::FREE_ANSWER === (int) $question->getType()) {
            $xml = '  <responseDeclaration identifier="'.$questionIdent.'" cardinality="single" baseType="string">';
            $xml .= '<outcomeDeclaration identifier="SCORE" cardinality="single" baseType="float">'.
                '<defaultValue><value>'.$this->formatScore((float) $question->getPonderation()).'</value></defaultValue></outcomeDeclaration>';
            $xml .= '  </responseDeclaration>'."\n";

            return $xml;
        }

        if ($this->isMatchingQuestion($question)) {
            return $this->exportMatchingResponseDeclaration($question, $questionIdent);
        }

        $answers = $this->getSortedAnswers($question);
        $cardinality = \in_array((int) $question->getType(), [self::MULTIPLE_ANSWER, self::MULTIPLE_ANSWER_DROPDOWN], true) ? 'multiple' : 'single';
        $xml = '  <responseDeclaration identifier="'.$questionIdent.'" cardinality="'.$cardinality.'" baseType="identifier">'."\n";
        $xml .= "    <correctResponse>\n";
        foreach ($answers as $answer) {
            $unknown = self::UNIQUE_ANSWER_NO_OPTION === (int) $question->getType()
                && Qti2UniqueAnswerUnknownMarker::UNKNOWN_POSITION === (int) $answer->getPosition();
            if (!$unknown && (int) ($answer->getCorrect() ?? 0) > 0) {
                $xml .= '      <value>answer_'.(int) $answer->getIid().'</value>'."\n";
            }
        }
        $xml .= "    </correctResponse>\n";
        $xml .= "    <mapping>\n";
        foreach ($answers as $answer) {
            $unknown = self::UNIQUE_ANSWER_NO_OPTION === (int) $question->getType()
                && Qti2UniqueAnswerUnknownMarker::UNKNOWN_POSITION === (int) $answer->getPosition();
            $identifier = $unknown ? Qti2UniqueAnswerUnknownMarker::UNKNOWN_IDENTIFIER : 'answer_'.(int) $answer->getIid();
            $weight = $unknown ? 0.0 : (float) $answer->getPonderation();
            $xml .= ' <mapEntry mapKey="'.$identifier.'" mappedValue="'.$this->formatScore($weight).'" />'."\n";
        }
        $xml .= "    </mapping>\n";
        $xml .= '  </responseDeclaration>'."\n";
        if (\in_array((int) $question->getType(), [self::UNIQUE_ANSWER_NO_OPTION, self::MULTIPLE_ANSWER_DROPDOWN], true)) {
            $xml .= '  <outcomeDeclaration identifier="SCORE" cardinality="single" baseType="float">'
                .'<defaultValue><value>0</value></defaultValue></outcomeDeclaration>'."\n";
        }

        return $xml;
    }

    private function exportQuestionResponses(CQuizQuestion $question, string $questionIdent, string $interactionAttributes = '', ?ZipArchive $archive = null, ?Course $course = null, ?Session $session = null, ?Qti2MediaArchive $media = null): string
    {
        if (self::FREE_ANSWER === (int) $question->getType()) {
            return '<extendedTextInteraction responseIdentifier="'.$questionIdent.'" >
            <prompt>
            '.$this->formatText((string) $question->getDescription()).'
            </prompt>
            </extendedTextInteraction>';
        }

        if ($this->isMatchingQuestion($question)) {
            return $this->exportMatchingInteraction($question, $questionIdent);
        }

        // Multiple answer requires maxChoices=0; exact combination already supplies it.
        $maxChoices = \in_array((int) $question->getType(), [self::MULTIPLE_ANSWER, self::MULTIPLE_ANSWER_DROPDOWN], true)
            ? ' maxChoices="0"'
            : $interactionAttributes;
        $xml = '    <choiceInteraction responseIdentifier="'.$questionIdent.'"'.$maxChoices.' >'."\n";
        $xml .= '      <prompt><![CDATA['.$this->escapeCdata($this->formatText($question->getQuestion())).']]></prompt>'."\n";
        foreach ($this->getSortedAnswers($question) as $answer) {
            $answerId = (int) $answer->getIid();
            $unknown = self::UNIQUE_ANSWER_NO_OPTION === (int) $question->getType()
                && Qti2UniqueAnswerUnknownMarker::UNKNOWN_POSITION === (int) $answer->getPosition();
            $identifier = $unknown ? Qti2UniqueAnswerUnknownMarker::UNKNOWN_IDENTIFIER : 'answer_'.$answerId;
            $answerHtml = (string) $answer->getAnswer();
            if (self::UNIQUE_ANSWER_IMAGE === (int) $question->getType()) {
                if (null === $this->questionImageMedia || null === $archive || null === $course) {
                    throw new BadRequestHttpException('QTI image export requires a media archive and course context.');
                }
                $answerHtml = $this->questionImageMedia->exportHtml(
                    $answerHtml,
                    $archive,
                    $media ??= new Qti2MediaArchive(),
                    $course,
                    $session
                );
            }
            $answerHtml = $this->formatText($answerHtml);
            $xml .= '<simpleChoice identifier="'.$identifier.'" fixed="'.($unknown ? 'true' : 'false').'">
                         <![CDATA['.$this->escapeCdata($answerHtml).']]>';
            $comment = (string) ($answer->getComment() ?? '');
            if ('' !== $comment) {
                $xml .= '<feedbackInline identifier="'.$identifier.'">
                             <![CDATA['.$this->escapeCdata($this->formatText($comment)).']]>
                             </feedbackInline>';
            }
            $xml .= '</simpleChoice>'."\n";
        }
        $xml .= '    </choiceInteraction>'."\n";

        return $xml;
    }

    /**
     * Any number of choices can be ticked (maxChoices="0"); the whole score is only granted for exactly
     * the set of correct choices.
     */
    private function exportExactCombinationItem(CQuizQuestion $question, string $questionIdent): string
    {
        $answers = $this->getSortedAnswers($question);
        $correctIdentifiers = [];
        $weights = [];
        foreach ($answers as $answer) {
            $weights[] = (float) $answer->getPonderation();
            if (1 === (int) ($answer->getCorrect() ?? 0)) {
                $correctIdentifiers[] = 'answer_'.(int) $answer->getIid();
            }
        }
        $score = Qti2ExactCombinationWriter::score($weights, (float) $question->getPonderation());

        return Qti2ExactCombinationWriter::declarations($questionIdent, $correctIdentifiers, $score)
            ."  <itemBody>\n"
            .$this->exportQuestionResponses($question, $questionIdent, ' maxChoices="0"')
            ."  </itemBody>\n"
            .Qti2ExactCombinationWriter::responseProcessing($questionIdent, $score);
    }

    /**
     * Serializes Chamilo positional answers without changing its partial-credit rules.
     */
    private function exportOrderingItem(CQuizQuestion $question, string $responseId): string
    {
        $answers = $this->getSortedAnswers($question);
        $optionIids = [];
        $items = [];
        foreach ($answers as $answer) {
            if (0 === (int) ($answer->getCorrect() ?? 0)) {
                $optionIids[(int) $answer->getIid()] = \count($optionIids) + 1;
            }
        }
        foreach ($answers as $answer) {
            $correct = (int) ($answer->getCorrect() ?? 0);
            if ($correct <= 0) {
                continue;
            }
            $items[] = [
                'identifier' => 'answer_'.(int) $answer->getIid(),
                'text' => $answer->getAnswer(),
                'target' => $optionIids[$correct] ?? $correct,
                'weight' => (float) $answer->getPonderation(),
            ];
        }

        $xml = Qti2OrderingItem::write($responseId, $question->getQuestion(), $items, (string) $question->getExtra());
        if (null === $xml) {
            throw new BadRequestHttpException('Sequence ordering question has invalid target positions or scores.');
        }

        return $xml;
    }

    /**
     * The whole Fill in blanks question is stored in its first answer row; the blanks are exported as
     * textEntryInteraction (typed) and inlineChoiceInteraction (menus), each one scored on its own.
     */
    private function exportFillBlanksItem(CQuizQuestion $question): string
    {
        $answers = $this->getSortedAnswers($question);
        $answer = $answers[0] ?? null;
        $caseInsensitive = str_contains((string) $question->getExtra(), 'case:false');
        $fillBlanks = null !== $answer ? Qti2FillBlanksWriter::write((string) $answer->getAnswer(), $caseInsensitive) : null;
        if (null === $fillBlanks) {
            return "  <itemBody>\n  </itemBody>\n";
        }

        $xml = $fillBlanks['declarations'];
        $xml .= "  <itemBody>\n".$fillBlanks['body']."  </itemBody>\n";
        $xml .= '  <responseProcessing template="http://www.imsglobal.org/question/qti_v2p1/rptemplates/map_response"/>'."\n";

        return $xml;
    }

    /**
     * Matching rows with correct = 0 are the options; the other rows are the items to match, their
     * "correct" field holding the iid of the option they must be associated with.
     */
    private function exportMatchingResponseDeclaration(CQuizQuestion $question, string $questionIdent): string
    {
        $correctPairs = [];
        foreach ($this->getSortedAnswers($question) as $answer) {
            $optionIid = (int) ($answer->getCorrect() ?? 0);
            if ($optionIid > 0) {
                $correctPairs['left_'.(int) $answer->getIid().' right_'.$optionIid] = $answer->getPonderation();
            }
        }

        $xml = '  <responseDeclaration identifier="'.$questionIdent.'" cardinality="multiple" baseType="directedPair">'."\n";
        $xml .= "    <correctResponse>\n";
        foreach (array_keys($correctPairs) as $pair) {
            $xml .= '      <value>'.$pair.'</value>'."\n";
        }
        $xml .= "    </correctResponse>\n";
        $xml .= "    <mapping>\n";
        foreach ($correctPairs as $pair => $score) {
            $xml .= ' <mapEntry mapKey="'.$pair.'" mappedValue="'.$this->formatScore($score).'" />'."\n";
        }
        $xml .= "    </mapping>\n";
        $xml .= '  </responseDeclaration>'."\n";

        return $xml;
    }

    private function exportMatchingInteraction(CQuizQuestion $question, string $questionIdent): string
    {
        $items = '';
        $options = '';
        $itemCount = 0;
        foreach ($this->getSortedAnswers($question) as $answer) {
            $text = '<![CDATA['.$this->escapeCdata($this->formatText($answer->getAnswer())).']]>';
            if ((int) ($answer->getCorrect() ?? 0) > 0) {
                $items .= '        <simpleAssociableChoice identifier="left_'.(int) $answer->getIid().'" matchMax="1">'.$text.'</simpleAssociableChoice>'."\n";
                ++$itemCount;
            } else {
                $options .= '        <simpleAssociableChoice identifier="right_'.(int) $answer->getIid().'" matchMax="0">'.$text.'</simpleAssociableChoice>'."\n";
            }
        }

        $xml = '    <matchInteraction responseIdentifier="'.$questionIdent.'" shuffle="false" maxAssociations="'.$itemCount.'">'."\n";
        $xml .= '      <prompt><![CDATA['.$this->escapeCdata($this->formatText($question->getQuestion())).']]></prompt>'."\n";
        $xml .= "      <simpleMatchSet>\n".$items."      </simpleMatchSet>\n";
        $xml .= "      <simpleMatchSet>\n".$options."      </simpleMatchSet>\n";
        $xml .= '    </matchInteraction>'."\n";

        return $xml;
    }

    /**
     * @return array<int, CQuizAnswer>
     */
    private function getSortedAnswers(CQuizQuestion $question): array
    {
        $answers = [];
        foreach ($question->getAnswers() as $answer) {
            if ($answer instanceof CQuizAnswer) {
                $answers[] = $answer;
            }
        }

        usort(
            $answers,
            static fn (CQuizAnswer $left, CQuizAnswer $right): int => $left->getPosition() <=> $right->getPosition()
        );

        return $answers;
    }

    private function getQuestionCategoryTitle(CQuizQuestion $question): string
    {
        foreach ($question->getCategories() as $category) {
            if ($category instanceof CQuizQuestionCategory) {
                return $category->getTitle();
            }
        }

        return '';
    }

    private function assertXmlCanBeParsed(string $xml): void
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        try {
            if (!$document->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
                throw new BadRequestHttpException('The QTI2 export XML could not be generated.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function createZipFile(): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new BadRequestHttpException('ZIP support is not available on this platform.');
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'exercise-qti2-');
        if (false === $zipPath) {
            throw new BadRequestHttpException('The QTI2 export file could not be created.');
        }

        return $zipPath;
    }

    private function formatDescription(string $text): string
    {
        return htmlspecialchars(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function formatText(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function escapeAttribute(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function escapeCdata(string $text): string
    {
        return str_replace(']]>', ']]]]><![CDATA[>', $text);
    }

    private function formatScore(float $score): string
    {
        $formatted = rtrim(rtrim(\sprintf('%.6F', $score), '0'), '.');

        return '' === $formatted ? '0' : $formatted;
    }
}
