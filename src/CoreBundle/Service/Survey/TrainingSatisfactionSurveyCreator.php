<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Survey;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Service\Mcp\McpTextAiService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;

final readonly class TrainingSatisfactionSurveyCreator
{
    public function __construct(
        private CSurveyRepository $surveyRepository,
        private EntityManagerInterface $entityManager,
        private McpTextAiService $aiService,
        private AiDisclosureHelper $aiDisclosureHelper,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function create(
        Course $course,
        User $user,
        string $title,
        ?string $language,
        ?string $provider,
        bool $publish,
        bool $anonymous,
    ): array {
        if ($this->isSurveyCreationDisabled()) {
            throw new RuntimeException('Survey creation is disabled by the platform configuration.');
        }

        $title = trim(strip_tags($title));
        if ('' === $title) {
            throw new InvalidArgumentException('The survey title is required.');
        }
        if (mb_strlen($title) > 255) {
            throw new InvalidArgumentException('The survey title cannot be longer than 255 characters.');
        }

        $language = $this->normalizeLanguage($language, $course->getCourseLanguage());
        $questionSet = $this->buildQuestionSet($user, $title, $language, $provider);
        $providerUsed = $questionSet['_provider'] ?? null;
        unset($questionSet['_provider']);

        $visibility = $publish
            ? ResourceLink::VISIBILITY_PUBLISHED
            : ResourceLink::VISIBILITY_DRAFT;

        $availableFrom = new DateTime();
        $availableUntil = (clone $availableFrom)->add(new DateInterval('P1Y'));

        $survey = (new CSurvey())
            ->setCode($this->generateCode((int) $course->getId()))
            ->setTitle($title)
            ->setSubtitle('')
            ->setLang($language)
            ->setAvailFrom($availableFrom)
            ->setAvailTill($availableUntil)
            ->setIsShared('0')
            ->setTemplate('template')
            ->setIntro((string) $questionSet['introduction'])
            ->setSurveythanks((string) $questionSet['thanks'])
            ->setAnonymous($anonymous ? '1' : '0')
            ->setVisibleResults(0)
            ->setDisplayQuestionNumber(true)
            ->setOneQuestionPerPage(false)
            ->setShuffle(false)
            ->setDuration(null)
            ->setSurveyType(0)
            ->setShowFormProfile(0)
            ->setFormFields('')
            ->setParent($course)
            ->addCourseLink($course, null, null, $visibility)
        ;

        $this->surveyRepository->create($survey);
        $surveyId = (int) $survey->getIid();

        $createdQuestions = [];
        foreach ($questionSet['questions'] as $index => $definition) {
            $question = (new CSurveyQuestion())
                ->setSurvey($survey)
                ->setSurveyQuestion((string) $definition['text'])
                ->setSurveyQuestionComment('')
                ->setType((string) $definition['type'])
                ->setDisplay('vertical')
                ->setSort($index + 1)
                ->setSharedQuestionId(0)
                ->setMaxValue(0)
                ->setIsMandatory((bool) $definition['required'])
            ;

            $this->entityManager->persist($question);
            $this->entityManager->flush();

            $options = [];
            foreach ($definition['options'] as $optionIndex => $optionText) {
                $option = (new CSurveyQuestionOption())
                    ->setSurvey($survey)
                    ->setQuestion($question)
                    ->setOptionText((string) $optionText)
                    ->setSort($optionIndex + 1)
                    ->setValue($optionIndex + 1)
                ;
                $this->entityManager->persist($option);
                $options[] = (string) $optionText;
            }

            $createdQuestions[] = [
                'question_id' => (int) $question->getIid(),
                'text' => $question->getSurveyQuestion(),
                'type' => $question->getType(),
                'required' => $question->isMandatory(),
                'options' => $options,
            ];
        }

        $this->entityManager->flush();

        if (null !== $providerUsed) {
            $this->aiDisclosureHelper->logAudit(
                targetKey: 'survey:'.$surveyId,
                userId: (int) $user->getId(),
                meta: [
                    'feature' => 'mcp_training_satisfaction_survey',
                    'provider' => $providerUsed,
                    'question_count' => \count($createdQuestions),
                    'anonymous' => $anonymous,
                    'published' => $publish,
                ],
                courseId: (int) $course->getId(),
                sessionId: 0,
            );
        }

        return [
            'survey_id' => $surveyId,
            'resource_node_id' => (int) $survey->getResourceNode()?->getId(),
            'title' => $survey->getTitle(),
            'language' => $language,
            'anonymous' => $anonymous,
            'published' => $publish,
            'question_count' => \count($createdQuestions),
            'provider_used' => $providerUsed,
            'ai_assisted' => null !== $providerUsed,
            'questions' => $createdQuestions,
            'content_url' => '/resources/survey/'
                .(int) $course->getResourceNode()?->getId()
                .'/'.$surveyId
                .'/questions?cid='.(int) $course->getId(),
        ];
    }

    private function isSurveyCreationDisabled(): bool
    {
        $value = $this->settingsManager->getSetting('survey.hide_survey_edition', true);

        return true === $value || 'true' === $value || '*' === $value;
    }

    private function normalizeLanguage(?string $language, string $courseLanguage): string
    {
        $language = null !== $language ? trim($language) : '';
        if ('' === $language) {
            $language = trim($courseLanguage);
        }
        if ('' === $language) {
            $language = 'en';
        }

        if (!preg_match('/^[a-zA-Z0-9_-]{1,20}$/', $language)) {
            throw new InvalidArgumentException('The survey language code is invalid.');
        }

        return $language;
    }

    private function generateCode(int $courseId): string
    {
        return mb_substr(
            'mcp-sat-'.$courseId.'-'.date('YmdHis').'-'.bin2hex(random_bytes(2)),
            0,
            40,
        );
    }

    /**
     * @return array{
     *     introduction: string,
     *     thanks: string,
     *     questions: list<array{
     *         text: string,
     *         type: 'multiplechoice'|'yesno'|'open',
     *         required: bool,
     *         options: list<string>
     *     }>,
     *     _provider?: string
     * }
     */
    private function buildQuestionSet(
        User $user,
        string $title,
        string $language,
        ?string $provider,
    ): array {
        $provider = null !== $provider ? trim($provider) : '';
        if ('' === $provider) {
            return $this->fallbackQuestions($language);
        }

        $result = $this->aiService->requestJson(
            $user,
            $provider,
            <<<'PROMPT'
Return JSON only using this schema:
{
  "introduction": "short survey introduction",
  "thanks": "short thank-you message",
  "questions": [
    {"text":"...", "type":"multiplechoice", "required":true, "options":["...", "...", "...", "...", "..."]},
    {"text":"...", "type":"yesno", "required":true, "options":["Yes", "No"]},
    {"text":"...", "type":"open", "required":false, "options":[]}
  ]
}
Create exactly seven training-satisfaction questions: five five-option satisfaction questions, one recommendation yes/no question, and one open comments question. Write everything in the requested language.
PROMPT,
            'Survey title: '.$title."\nLanguage: ".$language,
            2500,
        );

        $normalized = $this->normalizeGeneratedQuestions($result);
        $normalized['_provider'] = (string) $result['_provider'];

        return $normalized;
    }

    /**
     * @param array<string, mixed> $result
     *
     * @return array{
     *     introduction: string,
     *     thanks: string,
     *     questions: list<array{
     *         text: string,
     *         type: 'multiplechoice'|'yesno'|'open',
     *         required: bool,
     *         options: list<string>
     *     }>
     * }
     */
    private function normalizeGeneratedQuestions(array $result): array
    {
        $questions = $result['questions'] ?? null;
        if (!\is_array($questions) || 7 !== \count($questions)) {
            throw new RuntimeException('The AI model returned an invalid satisfaction survey.');
        }

        $normalized = [];
        foreach ($questions as $question) {
            if (!\is_array($question)) {
                throw new RuntimeException('The AI model returned an invalid survey question.');
            }

            $type = (string) ($question['type'] ?? '');
            if (!\in_array($type, ['multiplechoice', 'yesno', 'open'], true)) {
                throw new RuntimeException('The AI model returned an unsupported survey question type.');
            }

            $text = trim(strip_tags((string) ($question['text'] ?? '')));
            $options = \is_array($question['options'] ?? null)
                ? array_values(array_filter(array_map(
                    static fn (mixed $option): string => trim(strip_tags((string) $option)),
                    $question['options'],
                )))
                : [];

            if ('' === $text) {
                throw new RuntimeException('The AI model returned an empty survey question.');
            }
            if ('multiplechoice' === $type && 5 !== \count($options)) {
                throw new RuntimeException('A satisfaction question must contain five options.');
            }
            if ('yesno' === $type && 2 !== \count($options)) {
                throw new RuntimeException('The recommendation question must contain two options.');
            }
            if ('open' === $type) {
                $options = [];
            }

            $normalized[] = [
                'text' => mb_substr($text, 0, 2000),
                'type' => $type,
                'required' => (bool) ($question['required'] ?? false),
                'options' => $options,
            ];
        }

        return [
            'introduction' => mb_substr(trim(strip_tags((string) ($result['introduction'] ?? ''))), 0, 4000),
            'thanks' => mb_substr(trim(strip_tags((string) ($result['thanks'] ?? ''))), 0, 4000),
            'questions' => $normalized,
        ];
    }

    /**
     * @return array{
     *     introduction: string,
     *     thanks: string,
     *     questions: list<array{
     *         text: string,
     *         type: 'multiplechoice'|'yesno'|'open',
     *         required: bool,
     *         options: list<string>
     *     }>
     * }
     */
    private function fallbackQuestions(string $language): array
    {
        $spanish = str_starts_with(strtolower($language), 'es');

        $scale = $spanish
            ? ['Muy satisfecho', 'Satisfecho', 'Neutral', 'Insatisfecho', 'Muy insatisfecho']
            : ['Very satisfied', 'Satisfied', 'Neutral', 'Dissatisfied', 'Very dissatisfied'];

        $texts = $spanish
            ? [
                '¿Qué tan satisfecho está con la calidad general de la formación?',
                '¿Qué tan satisfecho está con la relevancia de los contenidos?',
                '¿Qué tan satisfecho está con la claridad de las explicaciones?',
                '¿Qué tan satisfecho está con la organización y el ritmo de la formación?',
                '¿Qué tan satisfecho está con la utilidad práctica de lo aprendido?',
            ]
            : [
                'How satisfied are you with the overall quality of the training?',
                'How satisfied are you with the relevance of the content?',
                'How satisfied are you with the clarity of the explanations?',
                'How satisfied are you with the organization and pace of the training?',
                'How satisfied are you with the practical usefulness of what you learned?',
            ];

        $questions = [];
        foreach ($texts as $text) {
            $questions[] = [
                'text' => $text,
                'type' => 'multiplechoice',
                'required' => true,
                'options' => $scale,
            ];
        }

        $questions[] = [
            'text' => $spanish
                ? '¿Recomendaría esta formación a otras personas?'
                : 'Would you recommend this training to other people?',
            'type' => 'yesno',
            'required' => true,
            'options' => $spanish ? ['Sí', 'No'] : ['Yes', 'No'],
        ];
        $questions[] = [
            'text' => $spanish
                ? '¿Qué deberíamos mejorar en futuras ediciones?'
                : 'What should we improve in future editions?',
            'type' => 'open',
            'required' => false,
            'options' => [],
        ];

        return [
            'introduction' => $spanish
                ? 'Su opinión nos ayudará a mejorar futuras formaciones.'
                : 'Your feedback will help us improve future training sessions.',
            'thanks' => $spanish
                ? 'Gracias por compartir su opinión.'
                : 'Thank you for sharing your feedback.',
            'questions' => $questions,
        ];
    }
}
