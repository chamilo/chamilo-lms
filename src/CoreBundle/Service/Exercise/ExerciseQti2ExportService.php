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
use DOMDocument;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use ZipArchive;

/**
 * Modern IMS/QTI 2 exporter for the same exercise-level subset exported by legacy Chamilo.
 *
 * The legacy exercise QTI2 action only exported Unique answer, Multiple answer and Free answer
 * questions through export_question_qti(). Other question types were silently skipped.
 */
final readonly class ExerciseQti2ExportService
{
    private const UNIQUE_ANSWER = 1;
    private const MULTIPLE_ANSWER = 2;
    private const FREE_ANSWER = 5;
    private const VISIBILITY_PUBLISHED = 2;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {}

    public function exportExerciseZip(int $exerciseId, Request $request): BinaryFileResponse
    {
        $quiz = $this->getValidatedExercise($exerciseId, $request);
        $xml = $this->buildExerciseXml($quiz);
        $this->assertXmlCanBeParsed($xml);

        $zipPath = $this->createZipFile();
        $zip = new ZipArchive();
        if (true !== $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            throw new BadRequestHttpException('The QTI2 export archive could not be created.');
        }

        $zip->addFromString(sprintf('qti2export_%d.xml', $exerciseId), $xml);
        if (!$zip->close()) {
            throw new BadRequestHttpException('The QTI2 export archive could not be finalized.');
        }

        $response = new BinaryFileResponse(new File($zipPath));
        $response->headers->set('Content-Type', 'application/zip');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, sprintf('qti2_export_%d.zip', $exerciseId));
        $response->deleteFileAfterSend(true);

        return $response;
    }

    private function getValidatedExercise(int $exerciseId, Request $request): CQuiz
    {
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        if (!$this->canExportExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to export this exercise.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);

        return $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
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

    private function getExerciseFromCurrentContext(int $exerciseId, Course $course, ?Session $session): CQuiz
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('quiz')
            ->addSelect('links.visibility AS linkVisibility')
            ->from(CQuiz::class, 'quiz')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('quiz.iid = :exerciseId')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('exerciseId', $exerciseId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(links.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        $row = $queryBuilder->getQuery()->getOneOrNullResult();
        if (null === $row) {
            throw new NotFoundHttpException('The requested exercise was not found in the current course context.');
        }

        $quiz = null;
        $visibility = self::VISIBILITY_PUBLISHED;
        if ($row instanceof CQuiz) {
            $quiz = $row;
        } elseif (\is_array($row)) {
            $candidate = $row[0] ?? $row['quiz'] ?? null;
            if ($candidate instanceof CQuiz) {
                $quiz = $candidate;
            }
            $visibility = (int) ($row['linkVisibility'] ?? self::VISIBILITY_PUBLISHED);
        }

        if (!$quiz instanceof CQuiz) {
            throw new NotFoundHttpException('The requested exercise was not found.');
        }

        if (0 !== $visibility && self::VISIBILITY_PUBLISHED !== $visibility && !$this->canExportExercises()) {
            throw new AccessDeniedHttpException('The requested exercise is not visible.');
        }

        return $quiz;
    }

    private function buildExerciseXml(CQuiz $quiz): string
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
            $xml .= $this->exportQuestion($question);
        }

        $xml .= '</section>'."\n";
        $xml .= '</questestinterop>'."\n";

        return $xml;
    }

    private function exportDuration(CQuiz $quiz): string
    {
        $duration = (int) ($quiz->getDuration() ?? 0);
        if (0 >= $duration) {
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
        if (0 < $random) {
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
            if (0 < $questionId) {
                $questions[$questionId] = $question;
            }
        }

        return array_values($questions);
    }

    private function isLegacyExportableQuestion(CQuizQuestion $question): bool
    {
        return \in_array((int) $question->getType(), [self::UNIQUE_ANSWER, self::MULTIPLE_ANSWER, self::FREE_ANSWER], true);
    }

    private function exportQuestion(CQuizQuestion $question): string
    {
        $questionIdent = 'QST_'.(int) $question->getIid();
        $categoryTitle = $this->getQuestionCategoryTitle($question);
        $xml = '<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1"'."\n";
        $xml .= '                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'."\n";
        $xml .= '                xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p1 imsqti_v2p1.xsd"'."\n";
        $xml .= '                identifier="'.$questionIdent.'"'."\n";
        $xml .= '                title = "'.$this->escapeAttribute($this->formatText($question->getQuestion())).'"'."\n";
        $xml .= '                category = "'.$this->escapeAttribute($this->formatText($categoryTitle)).'"'."\n";
        $xml .= '        >'."\n";
        $xml .= $this->exportQuestionResponseDeclaration($question, $questionIdent);
        $xml .= "  <itemBody>\n";
        $xml .= $this->exportQuestionResponses($question, $questionIdent);
        $xml .= "  </itemBody>\n";
        $xml .= '  <responseProcessing template="http://www.imsglobal.org/question/qti_v2p1/rptemplates/map_correct"/>'."\n";
        $xml .= '</assessmentItem>'."\n";

        return $xml;
    }

    private function exportQuestionResponseDeclaration(CQuizQuestion $question, string $questionIdent): string
    {
        if (self::FREE_ANSWER === (int) $question->getType()) {
            $xml = '  <responseDeclaration identifier="'.$questionIdent.'" cardinality="single" baseType="string">';
            $xml .= '<outcomeDeclaration identifier="SCORE" cardinality="single" baseType="float">' .
                '<defaultValue><value>'.$this->formatScore((float) $question->getPonderation()).'</value></defaultValue></outcomeDeclaration>';
            $xml .= '  </responseDeclaration>'."\n";

            return $xml;
        }

        $answers = $this->getSortedAnswers($question);
        $cardinality = self::MULTIPLE_ANSWER === (int) $question->getType() ? 'multiple' : 'single';
        $xml = '  <responseDeclaration identifier="'.$questionIdent.'" cardinality="'.$cardinality.'" baseType="identifier">'."\n";
        $xml .= "    <correctResponse>\n";
        foreach ($answers as $answer) {
            if ((int) ($answer->getCorrect() ?? 0) > 0) {
                $xml .= '      <value>answer_'.(int) $answer->getIid().'</value>'."\n";
            }
        }
        $xml .= "    </correctResponse>\n";
        $xml .= "    <mapping>\n";
        foreach ($answers as $answer) {
            $xml .= ' <mapEntry mapKey="answer_'.(int) $answer->getIid().'" mappedValue="'.$this->formatScore($answer->getPonderation()).'" />'."\n";
        }
        $xml .= "    </mapping>\n";
        $xml .= '  </responseDeclaration>'."\n";

        return $xml;
    }

    private function exportQuestionResponses(CQuizQuestion $question, string $questionIdent): string
    {
        if (self::FREE_ANSWER === (int) $question->getType()) {
            return '<extendedTextInteraction responseIdentifier="'.$questionIdent.'" >
            <prompt>
            '.$this->formatText((string) $question->getDescription()).'
            </prompt>
            </extendedTextInteraction>';
        }

        $xml = '    <choiceInteraction responseIdentifier="'.$questionIdent.'" >'."\n";
        $xml .= '      <prompt><![CDATA['.$this->escapeCdata($this->formatText($question->getQuestion())).']]></prompt>'."\n";
        foreach ($this->getSortedAnswers($question) as $answer) {
            $answerId = (int) $answer->getIid();
            $xml .= '<simpleChoice identifier="answer_'.$answerId.'" fixed="false">
                         <![CDATA['.$this->escapeCdata($this->formatText($answer->getAnswer())).']]>';
            $comment = (string) ($answer->getComment() ?? '');
            if ('' !== $comment) {
                $xml .= '<feedbackInline identifier="answer_'.$answerId.'">
                             <![CDATA['.$this->escapeCdata($this->formatText($comment)).']]>
                             </feedbackInline>';
            }
            $xml .= '</simpleChoice>'."\n";
        }
        $xml .= '    </choiceInteraction>'."\n";

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

    private function canExportExercises(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
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
        $formatted = rtrim(rtrim(sprintf('%.6F', $score), '0'), '.');

        return '' === $formatted ? '0' : $formatted;
    }
}
