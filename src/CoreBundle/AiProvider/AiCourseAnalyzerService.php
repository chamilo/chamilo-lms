<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Throwable;
use ZipArchive;

final class AiCourseAnalyzerService
{
    private const MAX_DOCUMENTS = 25;
    private const MAX_EXERCISES = 20;
    private const MAX_QUESTIONS_PER_EXERCISE = 80;
    private const MAX_ANSWERS_PER_QUESTION = 20;
    private const MAX_CHARS_PER_DOCUMENT = 12000;
    private const MAX_TOTAL_TEXT_CHARS = 90000;

    /**
     * @var string[]
     */
    private const READABLE_EXTENSIONS = [
        'txt',
        'md',
        'markdown',
        'html',
        'htm',
        'csv',
        'json',
        'xml',
        'yaml',
        'yml',
        'log',
        'docx',
    ];

    /**
     * @var string[]
     */
    private const READABLE_MIME_PREFIXES = [
        'text/',
        'application/json',
        'application/xml',
        'application/xhtml+xml',
        'application/x-yaml',
        'application/yaml',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AiChatCompletionClientInterface $chatCompletionClient,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function analyze(Course $course, ?Session $session, string $teacherPrompt, string $provider): array
    {
        $payload = $this->buildPayload($course, $session, $teacherPrompt);
        $messages = $this->buildMessages($payload);

        $rawResponse = $this->chatCompletionClient->chat($provider, $messages, [
            'temperature' => 0.2,
            'max_tokens' => 3500,
            'throw_on_error' => true,
        ]);

        return [
            'payload' => $payload,
            'rawResponse' => $rawResponse,
            'structuredResponse' => $this->decodeStructuredResponse($rawResponse),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(Course $course, ?Session $session, string $teacherPrompt): array
    {
        $documents = $this->collectVisibleDocuments($course, $session);
        $exercises = $this->collectVisibleExercises($course, $session);

        return [
            'course' => [
                'id' => $course->getId(),
                'code' => $course->getCode(),
                'title' => $course->getTitle(),
                'description' => $this->cleanText((string) $course->getDescription()),
            ],
            'teacherPrompt' => trim($teacherPrompt),
            'limits' => [
                'maxDocuments' => self::MAX_DOCUMENTS,
                'maxExercises' => self::MAX_EXERCISES,
                'maxQuestionsPerExercise' => self::MAX_QUESTIONS_PER_EXERCISE,
                'maxCharactersPerDocument' => self::MAX_CHARS_PER_DOCUMENT,
                'maxTotalTextCharacters' => self::MAX_TOTAL_TEXT_CHARS,
            ],
            'documents' => $documents,
            'exercises' => $exercises,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function collectVisibleDocuments(Course $course, ?Session $session): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('document', 'resourceNode', 'resourceLink', 'resourceFile')
            ->from(CDocument::class, 'document')
            ->innerJoin('document.resourceNode', 'resourceNode')
            ->innerJoin('resourceNode.resourceLinks', 'resourceLink')
            ->leftJoin('resourceNode.resourceFiles', 'resourceFile')
            ->andWhere('resourceLink.course = :course')
            ->andWhere('resourceLink.visibility = :visibility')
            ->andWhere('document.filetype = :filetype')
            ->setParameter('course', $course)
            ->setParameter('visibility', ResourceLink::VISIBILITY_PUBLISHED, Types::INTEGER)
            ->setParameter('filetype', 'file', Types::STRING)
            ->setMaxResults(self::MAX_DOCUMENTS)
            ->orderBy('resourceLink.displayOrder', 'ASC')
        ;

        if ($session instanceof Session) {
            $qb
                ->andWhere('(resourceLink.session IS NULL OR resourceLink.session = :session)')
                ->setParameter('session', $session)
            ;
        } else {
            $qb->andWhere('resourceLink.session IS NULL');
        }

        /** @var CDocument[] $documentList */
        $documentList = $qb->getQuery()->getResult();

        $items = [];
        $totalCharacters = 0;

        foreach ($documentList as $document) {
            $resourceNode = $document->getResourceNode();
            if (null === $resourceNode) {
                continue;
            }

            /** @var ResourceFile|false $resourceFile */
            $resourceFile = $resourceNode->getResourceFiles()->first();

            $metadata = [
                'id' => $document->getIid(),
                'title' => $document->getTitle(),
                'comment' => $this->cleanText((string) $document->getComment()),
                'fileType' => $document->getFiletype(),
                'resourceNodeId' => $resourceNode->getId(),
                'resourcePath' => $resourceNode->getPathForDisplay(),
                'fileName' => null,
                'mimeType' => null,
                'size' => null,
                'textIncluded' => false,
                'text' => '',
                'notice' => null,
            ];

            if ($resourceFile instanceof ResourceFile) {
                $metadata['fileName'] = $resourceFile->getOriginalName() ?: $resourceFile->getTitle();
                $metadata['mimeType'] = $resourceFile->getMimeType();
                $metadata['size'] = $resourceFile->getSize();

                $remainingCharacters = self::MAX_TOTAL_TEXT_CHARS - $totalCharacters;
                if ($remainingCharacters > 0) {
                    $text = $this->extractResourceFileText($resourceFile, min(self::MAX_CHARS_PER_DOCUMENT, $remainingCharacters));
                    if ('' !== $text) {
                        $metadata['textIncluded'] = true;
                        $metadata['text'] = $text;
                        $totalCharacters += mb_strlen($text);
                    } else {
                        $metadata['notice'] = 'This file type is listed but was not included as text in this proof of concept.';
                    }
                } else {
                    $metadata['notice'] = 'The global text limit was reached before this document could be included.';
                }
            }

            $items[] = $metadata;
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function collectVisibleExercises(Course $course, ?Session $session): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('quiz', 'resourceNode', 'resourceLink', 'quizQuestionRel', 'question', 'answer')
            ->from(CQuiz::class, 'quiz')
            ->innerJoin('quiz.resourceNode', 'resourceNode')
            ->innerJoin('resourceNode.resourceLinks', 'resourceLink')
            ->leftJoin('quiz.questions', 'quizQuestionRel')
            ->leftJoin('quizQuestionRel.question', 'question')
            ->leftJoin('question.answers', 'answer')
            ->andWhere('resourceLink.course = :course')
            ->andWhere('resourceLink.visibility = :visibility')
            ->setParameter('course', $course)
            ->setParameter('visibility', ResourceLink::VISIBILITY_PUBLISHED, Types::INTEGER)
            ->setMaxResults(self::MAX_EXERCISES)
            ->orderBy('resourceLink.displayOrder', 'ASC')
        ;

        if ($session instanceof Session) {
            $qb
                ->andWhere('(resourceLink.session IS NULL OR resourceLink.session = :session)')
                ->setParameter('session', $session)
            ;
        } else {
            $qb->andWhere('resourceLink.session IS NULL');
        }

        /** @var CQuiz[] $quizList */
        $quizList = $qb->getQuery()->getResult();

        $items = [];

        foreach ($quizList as $quiz) {
            $questions = [];

            foreach ($quiz->getQuestions() as $quizQuestionRel) {
                if (\count($questions) >= self::MAX_QUESTIONS_PER_EXERCISE) {
                    break;
                }

                if (!method_exists($quizQuestionRel, 'getQuestion')) {
                    continue;
                }

                $question = $quizQuestionRel->getQuestion();
                if (!$question instanceof CQuizQuestion) {
                    continue;
                }

                $answers = [];
                foreach ($question->getAnswers() as $answer) {
                    if (\count($answers) >= self::MAX_ANSWERS_PER_QUESTION) {
                        break;
                    }

                    if (!$answer instanceof CQuizAnswer) {
                        continue;
                    }

                    $answers[] = [
                        'answer' => $this->cleanText($answer->getAnswer()),
                        'correct' => $answer->getCorrect(),
                        'comment' => $this->cleanText((string) $answer->getComment()),
                        'ponderation' => $answer->getPonderation(),
                        'position' => $answer->getPosition(),
                    ];
                }

                $questions[] = [
                    'id' => $question->getIid(),
                    'type' => $question->getType(),
                    'question' => $this->cleanText($question->getQuestion()),
                    'description' => $this->cleanText((string) $question->getDescription()),
                    'ponderation' => $question->getPonderation(),
                    'position' => $question->getPosition(),
                    'answers' => $answers,
                ];
            }

            $items[] = [
                'id' => $quiz->getIid(),
                'title' => $quiz->getTitle(),
                'description' => $this->cleanText((string) $quiz->getDescription()),
                'type' => $quiz->getType(),
                'questions' => $questions,
            ];
        }

        return $items;
    }

    private function extractResourceFileText(ResourceFile $resourceFile, int $maxCharacters): string
    {
        if ($maxCharacters <= 0) {
            return '';
        }

        $file = $resourceFile->getFile();
        if (!$file instanceof File || !$file->isReadable()) {
            return '';
        }

        $fileName = (string) ($resourceFile->getOriginalName() ?: $resourceFile->getTitle() ?: $file->getFilename());
        $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
        $mimeType = strtolower((string) $resourceFile->getMimeType());

        if ('docx' === $extension) {
            return $this->truncateText($this->extractDocxText($file), $maxCharacters);
        }

        if (!$this->isPlainTextResource($extension, $mimeType)) {
            return '';
        }

        $contents = file_get_contents($file->getPathname());
        if (!\is_string($contents)) {
            return '';
        }

        return $this->truncateText($this->cleanText($contents), $maxCharacters);
    }

    private function isPlainTextResource(string $extension, string $mimeType): bool
    {
        if (\in_array($extension, self::READABLE_EXTENSIONS, true) && 'docx' !== $extension) {
            return true;
        }

        foreach (self::READABLE_MIME_PREFIXES as $prefix) {
            if (str_starts_with($mimeType, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function extractDocxText(File $file): string
    {
        if (!class_exists(ZipArchive::class)) {
            return '';
        }

        $zip = new ZipArchive();
        if (true !== $zip->open($file->getPathname())) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!\is_string($xml)) {
            return '';
        }

        $xml = preg_replace('/<\/w:p>/', "\n", $xml) ?? $xml;
        $xml = strip_tags($xml);

        return $this->cleanText($xml);
    }

    private function cleanText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\R{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function truncateText(string $text, int $maxCharacters): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $maxCharacters) {
            return $text;
        }

        return mb_substr($text, 0, $maxCharacters).'…';
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{role:string,content:string}>
     */
    private function buildMessages(array $payload): array
    {
        $payloadJson = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!\is_string($payloadJson)) {
            $payloadJson = '{}';
        }

        return [
            [
                'role' => 'system',
                'content' => implode("\n", [
                    'You are an expert instructional designer and e-learning quality reviewer.',
                    'Analyze the Chamilo course content provided by the teacher.',
                    'Return only valid JSON, without markdown fences.',
                    'Use this exact structure:',
                    '{',
                    '  "generalFeedback": "string",',
                    '  "strengths": ["string"],',
                    '  "risks": ["string"],',
                    '  "recommendations": ["string"],',
                    '  "documents": [{"title": "string", "feedback": "string", "recommendations": ["string"]}],',
                    '  "exercises": [{"title": "string", "feedback": "string", "recommendations": ["string"]}]',
                    '}',
                ]),
            ],
            [
                'role' => 'user',
                'content' => $payloadJson,
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeStructuredResponse(string $rawResponse): ?array
    {
        $response = trim($rawResponse);
        $response = preg_replace('/^```(?:json)?\s*/i', '', $response) ?? $response;
        $response = preg_replace('/\s*```$/', '', $response) ?? $response;

        try {
            $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        return \is_array($decoded) ? $decoded : null;
    }
}
