<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyAnswer;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyAnswer as CSurveyAnswerEntity;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<SurveyAnswer, SurveyAnswer>
 */
final readonly class SurveyAnswerProcessor implements ProcessorInterface
{
    use SurveyPersonalitySupportTrait;
    use SurveyProfileFieldsTrait;
    use SurveyCsrfTokenValidationTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private SettingsManager $settingsManager,
        private SurveyAnswerProvider $surveyAnswerProvider,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SurveyAnswer
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $surveyId = isset($uriVariables['surveyId']) ? (int) $uriVariables['surveyId'] : 0;
        if ($surveyId <= 0) {
            throw new BadRequestHttpException('A valid survey id is required.');
        }

        $payload = $this->getPayload($request, $data);
        $this->validateSubmittedCsrfToken($request, $this->csrfTokenManager, SurveyAnswerProvider::CSRF_TOKEN_ID, $payload);

        $survey = $this->surveyAnswerProvider->getSurvey($surveyId);
        $course = $this->surveyAnswerProvider->getCourse($request, $survey);
        $session = $this->surveyAnswerProvider->getSession($request);
        $user = $this->surveyAnswerProvider->getCurrentUserOrNull();
        $survey = $this->surveyAnswerProvider->getSurveyFromCurrentContext($surveyId, $course, $session);
        $this->assertPersonalitySurveySupported($survey);
        $invitation = $this->surveyAnswerProvider->getInvitation($survey, $course, $session, $user, $request);

        if (3 === $survey->getSurveyType()) {
            throw new BadRequestHttpException('Meeting poll answers must be submitted from the meeting view.');
        }

        if (1 === (int) $invitation->getAnswered() && !$this->isAnsweredQuestionEditAllowed()) {
            throw new AccessDeniedHttpException('You already filled this survey.');
        }

        $answers = isset($payload['answers']) && \is_array($payload['answers']) ? $payload['answers'] : [];
        $otherAnswers = isset($payload['otherAnswers']) && \is_array($payload['otherAnswers']) ? $payload['otherAnswers'] : [];
        $profileValues = isset($payload['profileValues']) && \is_array($payload['profileValues']) ? $payload['profileValues'] : [];
        $questions = $this->surveyAnswerProvider->getOrderedQuestions($survey);

        $this->validateMandatoryAnswers($questions, $answers, $otherAnswers);
        if ($user instanceof User) {
            $this->applySurveyProfileValues($survey, $user, $profileValues);
        }
        $answerUserKey = $this->surveyAnswerProvider->getAnswerUserKey($survey, $user, $request);
        $this->removeExistingAnswers($survey, $answerUserKey, $request);

        foreach ($questions as $question) {
            if (!$this->isQuestionVisible($question, $answers)) {
                continue;
            }

            $this->saveQuestionAnswer($survey, $question, $answerUserKey, $answers, $otherAnswers, $request);
        }

        $wasAnswered = 1 === (int) $invitation->getAnswered();
        $invitation
            ->setAnswered(1)
            ->setAnsweredAt(new DateTime())
        ;

        if (!$wasAnswered) {
            $survey->setAnswered($survey->getAnswered() + 1);
        }

        $this->entityManager->flush();

        return $this->surveyAnswerProvider->buildResponse(
            $survey,
            $course,
            $session,
            false,
            $request,
            true,
            'Thank you for answering the survey.',
        );
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

        if ($data instanceof SurveyAnswer) {
            return [
                'csrfToken' => $data->csrfToken,
                'answers' => $data->submittedAnswers,
                'otherAnswers' => $data->otherAnswers,
                'profileValues' => $data->profileValues,
            ];
        }

        return [];
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(SurveyAnswerProvider::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }

    /**
     * @param array<int, CSurveyQuestion> $questions
     * @param array<string, mixed>        $answers
     * @param array<string, mixed>        $otherAnswers
     */
    private function validateMandatoryAnswers(array $questions, array $answers, array $otherAnswers): void
    {
        foreach ($questions as $question) {
            $type = $question->getType();
            if ('pagebreak' === $type) {
                continue;
            }

            if (!$this->isRequiredQuestionType($type) || !$question->isMandatory() || !$this->isQuestionVisible($question, $answers)) {
                continue;
            }

            $questionId = (string) $question->getIid();
            if ('multiplechoiceother' === $type && '' !== trim((string) ($otherAnswers[$questionId] ?? ''))) {
                continue;
            }

            if (!$this->hasAnswerValue($type, $answers[$questionId] ?? null)) {
                throw new BadRequestHttpException('Please answer all mandatory questions. Missing question id: '.$questionId.'.');
            }
        }
    }

    private function isRequiredQuestionType(string $type): bool
    {
        return \in_array($type, ['yesno', 'multiplechoice'], true);
    }

    private function hasAnswerValue(string $type, mixed $value): bool
    {
        if ('open' === $type || 'comment' === $type) {
            return null !== $value && '' !== trim((string) $value);
        }

        if ('score' === $type) {
            return \is_array($value) && [] !== array_filter($value, static fn (mixed $score): bool => '' !== (string) $score && null !== $score);
        }

        if ('multipleresponse' === $type) {
            return \is_array($value) && [] !== array_filter($value, static fn (mixed $optionId): bool => (int) $optionId > 0);
        }

        return null !== $value && '' !== trim((string) $value) && 0 !== (int) $value;
    }

    /**
     * @param array<string, mixed> $answers
     */
    private function isQuestionVisible(CSurveyQuestion $question, array $answers): bool
    {
        $parent = $question->getParent();
        $parentOption = $question->getParentOption();
        if (null === $parent || null === $parentOption) {
            return true;
        }

        $parentQuestionId = (string) $parent->getIid();
        $expectedOptionId = (int) $parentOption->getIid();
        $parentAnswer = $answers[$parentQuestionId] ?? null;

        if (\is_array($parentAnswer)) {
            return \in_array($expectedOptionId, array_map('intval', $parentAnswer), true)
                || isset($parentAnswer[(string) $expectedOptionId]);
        }

        return $expectedOptionId === (int) $parentAnswer;
    }

    private function removeExistingAnswers(CSurvey $survey, string $answerUserKey, Request $request): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->delete(CSurveyAnswerEntity::class, 'answer')
            ->andWhere('IDENTITY(answer.survey) = :surveyId')
            ->andWhere('answer.user = :answerUser')
            ->andWhere('answer.lpItemId = :lpItemId')
            ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
            ->setParameter('answerUser', $answerUserKey)
            ->setParameter('lpItemId', $request->query->getInt('lpItemId'), Types::INTEGER)
        ;

        $sessionId = $request->query->getInt('sid');
        if ($sessionId > 0) {
            $queryBuilder
                ->andWhere('answer.sessionId = :sessionId')
                ->setParameter('sessionId', $sessionId, Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('answer.sessionId IS NULL');
        }

        $queryBuilder->getQuery()->execute();
    }

    /**
     * @param array<string, mixed> $answers
     * @param array<string, mixed> $otherAnswers
     */
    private function saveQuestionAnswer(
        CSurvey $survey,
        CSurveyQuestion $question,
        string $answerUserKey,
        array $answers,
        array $otherAnswers,
        Request $request
    ): void {
        $questionId = (string) $question->getIid();
        $value = $answers[$questionId] ?? null;
        $type = $question->getType();

        if ('pagebreak' === $type) {
            return;
        }

        if ('multiplechoiceother' === $type) {
            $this->saveMultipleChoiceOtherAnswer($survey, $question, $answerUserKey, $answers, $otherAnswers, $request);

            return;
        }

        if (!$this->hasAnswerValue($type, $value) && !\in_array($type, ['open', 'comment'], true)) {
            return;
        }

        if ('multipleresponse' === $type && \is_array($value)) {
            foreach ($value as $optionId) {
                if ((int) $optionId > 0) {
                    $this->persistAnswer($survey, $question, $answerUserKey, (string) (int) $optionId, 0, $request);
                }
            }

            return;
        }

        if ('score' === $type && \is_array($value)) {
            foreach ($value as $optionId => $score) {
                if ((int) $optionId > 0 && '' !== (string) $score) {
                    $this->persistAnswer($survey, $question, $answerUserKey, (string) (int) $optionId, (int) $score, $request);
                }
            }

            return;
        }

        if ('open' === $type || 'comment' === $type) {
            $text = trim((string) $value);
            if ('' !== $text) {
                $this->persistAnswer($survey, $question, $answerUserKey, $text, 0, $request);
            }

            return;
        }

        $optionId = (int) $value;
        if ($optionId <= 0) {
            return;
        }

        $optionValue = 0;
        if ('percentage' === $type) {
            $option = $this->findOption($question, $optionId);
            $optionValue = null !== $option ? (int) strip_tags($option->getOptionText()) : 0;
        }

        $this->persistAnswer($survey, $question, $answerUserKey, (string) $optionId, $optionValue, $request);
    }

    /**
     * @param array<string, mixed> $answers
     * @param array<string, mixed> $otherAnswers
     */
    private function saveMultipleChoiceOtherAnswer(
        CSurvey $survey,
        CSurveyQuestion $question,
        string $answerUserKey,
        array $answers,
        array $otherAnswers,
        Request $request
    ): void {
        $questionId = (string) $question->getIid();
        $optionId = (int) ($answers[$questionId] ?? 0);
        $otherText = trim((string) ($otherAnswers[$questionId] ?? ''));

        if ($optionId <= 0 && '' === $otherText) {
            return;
        }

        if ('' !== $otherText && ($optionId <= 0 || !$this->isOtherOption($question, $optionId))) {
            $optionId = $this->findOtherOptionId($question);
        }

        if ($optionId <= 0) {
            return;
        }

        $storedOptionId = (string) $optionId;
        if ('' !== $otherText && $this->isOtherOption($question, $optionId)) {
            $storedOptionId .= '@:@'.$otherText;
        }

        $this->persistAnswer($survey, $question, $answerUserKey, $storedOptionId, 0, $request);
    }

    private function persistAnswer(
        CSurvey $survey,
        CSurveyQuestion $question,
        string $answerUserKey,
        string $optionId,
        int $value,
        Request $request
    ): void {
        $answer = new CSurveyAnswerEntity();
        $answer
            ->setSurvey($survey)
            ->setQuestion($question)
            ->setUser($answerUserKey)
            ->setOptionId($optionId)
            ->setValue($value)
            ->setLpItemId($request->query->getInt('lpItemId'))
            ->setSessionId($request->query->getInt('sid') > 0 ? $request->query->getInt('sid') : null)
        ;

        $this->entityManager->persist($answer);
    }

    private function findOption(CSurveyQuestion $question, int $optionId): ?CSurveyQuestionOption
    {
        foreach ($this->surveyAnswerProvider->getSortedOptions($question) as $option) {
            if ((int) $option->getIid() === $optionId) {
                return $option;
            }
        }

        return null;
    }

    private function isOtherOption(CSurveyQuestion $question, int $optionId): bool
    {
        $option = $this->findOption($question, $optionId);
        if (null === $option) {
            return false;
        }

        return 'other' === trim(strtolower(strip_tags($option->getOptionText())));
    }

    private function findOtherOptionId(CSurveyQuestion $question): int
    {
        foreach ($this->surveyAnswerProvider->getSortedOptions($question) as $option) {
            if ('other' === trim(strtolower(strip_tags($option->getOptionText())))) {
                return (int) $option->getIid();
            }
        }

        return 0;
    }

    private function isAnsweredQuestionEditAllowed(): bool
    {
        $value = $this->settingsManager->getSetting('survey.survey_allow_answered_question_edit', true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }
}
