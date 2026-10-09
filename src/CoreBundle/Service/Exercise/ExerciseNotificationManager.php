<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\AttemptFile;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

use const ENT_QUOTES;

/**
 * Sends the teacher notifications of the course setting email_alert_manager_on_new_quiz (or the
 * exercise's own notifications override) from the Vue exercise runtime. Port of the legacy
 * Exercise::send_mail_notification_for_exam() and its open/oral question variants.
 */
final readonly class ExerciseNotificationManager
{
    private const OPTION_NONE = 0;
    private const OPTION_END = 1;
    private const OPTION_START = 2;
    private const OPTION_END_OPEN_QUESTION = 3;
    private const OPTION_END_ORAL_QUESTION = 4;
    private const FREE_ANSWER = 5;
    private const ORAL_EXPRESSION = 13;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SettingsManager $settingsManager,
        private SettingsCourseManager $settingsCourseManager,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
        private MessageHelper $messageHelper,
        private TranslatorInterface $translator,
        private RequestStack $requestStack,
        private LoggerInterface $logger,
    ) {}

    public function notifyOnStart(CQuiz $quiz, Course $course, ?Session $session, User $learner, TrackEExercise $attempt): void
    {
        $options = $this->getOptions($quiz, $course);
        if (!\in_array(self::OPTION_START, $options, true) || !$this->shouldNotify($learner, $course, $session)) {
            return;
        }

        foreach ($this->getRecipients($course, $session) as $recipient) {
            $locale = $recipient->getLocale();
            $subject = $this->translator->trans('Student just started an exercise', [], null, $locale);
            $body = $this->trans('Student just started an exercise', $locale).'<br /><br />'
                .$this->buildAttemptDetails($quiz, $course, $session, $learner, $locale, 'Test');

            $this->send($recipient, $learner, $subject, $body, $attempt);
        }
    }

    public function notifyOnFinish(
        CQuiz $quiz,
        Course $course,
        ?Session $session,
        User $learner,
        TrackEExercise $attempt,
        float $score,
        float $maxScore,
    ): void {
        $options = $this->getOptions($quiz, $course);
        $sendEnd = \in_array(self::OPTION_END, $options, true);
        $sendOpen = \in_array(self::OPTION_END_OPEN_QUESTION, $options, true);
        $sendOral = \in_array(self::OPTION_END_ORAL_QUESTION, $options, true);

        if ((!$sendEnd && !$sendOpen && !$sendOral) || !$this->shouldNotify($learner, $course, $session)) {
            return;
        }

        $recipients = $this->getRecipients($course, $session);
        if ([] === $recipients) {
            return;
        }

        $openAnswers = $sendOpen ? $this->getAnswersOfType($attempt, self::FREE_ANSWER) : [];
        $oralAnswers = $sendOral ? $this->getAnswersOfType($attempt, self::ORAL_EXPRESSION) : [];
        $correctionUrl = $this->buildCorrectionUrl($quiz, $course, $session, $attempt);

        foreach ($recipients as $recipient) {
            $locale = $recipient->getLocale();

            if ([] !== $openAnswers) {
                $subject = $this->translator->trans('A learner has answered an open question', [], null, $locale);
                $body = $this->trans('A learner has answered an open question', $locale).'<br /><br />'
                    .$this->buildAttemptDetails($quiz, $course, $session, $learner, $locale, 'Test attempted')
                    .'<br />'.$this->buildAnswersTable($openAnswers, $locale)
                    .$this->buildCorrectionLink($correctionUrl, $locale);

                $this->send($recipient, $learner, $subject, $body, $attempt);
            }

            if ([] !== $oralAnswers) {
                $subject = $this->translator->trans('A learner has attempted one or more oral question', [], null, $locale);
                $body = $this->trans('A learner has attempted one or more oral question', $locale).'<br /><br />'
                    .$this->buildAttemptDetails($quiz, $course, $session, $learner, $locale, 'Test attempted')
                    .'<br />'.\sprintf(
                        $this->trans('The attempted oral questions are %s', $locale),
                        $this->buildAnswersTable($oralAnswers, $locale),
                    )
                    .$this->buildCorrectionLink($correctionUrl, $locale);

                $this->send($recipient, $learner, $subject, $body, $attempt);
            }

            if ($sendEnd) {
                $scoreRow = '';
                if ('true' === $this->settingsManager->getSetting('exercise.send_score_in_exam_notification_mail_to_manager', true)) {
                    $scoreRow = $this->buildRow($this->trans('Score', $locale), $this->formatScore($score, $maxScore));
                }

                $subject = $this->translator->trans('A learner attempted an exercise', [], null, $locale);
                $body = $this->trans('A learner attempted an exercise', $locale).'<br /><br />'
                    .$this->buildAttemptDetails($quiz, $course, $session, $learner, $locale, 'Test', $scoreRow)
                    .$this->buildCorrectionLink($correctionUrl, $locale);

                $this->send($recipient, $learner, $subject, $body, $attempt);
            }
        }
    }

    /**
     * The exercise's own notification options replace the course ones when it has any.
     *
     * @return list<int>
     */
    private function getOptions(CQuiz $quiz, Course $course): array
    {
        $value = trim((string) $quiz->getNotifications());
        if ('' === $value) {
            $this->settingsCourseManager->setCourse($course);
            $value = trim((string) $this->settingsCourseManager->getCourseSettingValue('email_alert_manager_on_new_quiz'));
        }

        if ('' === $value) {
            return [];
        }

        $options = array_map(static fn (string $option): int => (int) trim($option), explode(',', $value));

        // Legacy stopped at an explicit "none", whatever else was selected.
        return \in_array(self::OPTION_NONE, $options, true) ? [] : $options;
    }

    /**
     * Legacy only notified for learners: !api_is_allowed_to_edit(null, true) && !api_is_excluded_user_type().
     */
    private function shouldNotify(User $learner, Course $course, ?Session $session): bool
    {
        if ($learner->isInvitee() || User::ANONYMOUS === $learner->getStatus()) {
            return false;
        }

        return !$this->isAllowedToEditHelper->check(false, true, course: $course, session: $session);
    }

    /**
     * @return array<int, User> keyed by user id
     */
    private function getRecipients(Course $course, ?Session $session): array
    {
        $recipients = [];

        if (null === $session) {
            foreach ($course->getTeachersSubscriptions() as $subscription) {
                if ($subscription instanceof CourseRelUser) {
                    $recipients[(int) $subscription->getUser()->getId()] = $subscription->getUser();
                }
            }

            return $recipients;
        }

        foreach ($session->getSessionRelCourseRelUsersByStatus($course, Session::COURSE_COACH) as $subscription) {
            if ($subscription instanceof SessionRelCourseRelUser) {
                $recipients[(int) $subscription->getUser()->getId()] = $subscription->getUser();
            }
        }

        if ('true' !== $this->settingsManager->getSetting('exercise.block_quiz_mail_notification_general_coach', true)) {
            foreach ($session->getGeneralCoaches() as $generalCoach) {
                $recipients[(int) $generalCoach->getId()] = $generalCoach;
            }
        }

        return $recipients;
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function getAnswersOfType(TrackEExercise $attempt, int $type): array
    {
        $rows = $this->entityManager->getRepository(TrackEAttempt::class)->findBy(
            ['trackExercise' => $attempt],
            ['questionId' => 'ASC', 'position' => 'ASC'],
        );

        $answers = [];
        foreach ($rows as $row) {
            $question = $this->entityManager->find(CQuizQuestion::class, (int) $row->getQuestionId());
            if (!$question instanceof CQuizQuestion || $type !== $question->getType()) {
                continue;
            }

            $answer = trim($row->getAnswer());
            if (self::ORAL_EXPRESSION === $type) {
                $fileNames = [];
                foreach ($row->getAttemptFiles() as $attemptFile) {
                    if ($attemptFile instanceof AttemptFile) {
                        $fileNames[] = $this->escape((string) $attemptFile->getResourceNode()?->getTitle());
                    }
                }
                $answer = trim(('0' === $answer ? '' : $answer).' '.implode(', ', array_filter($fileNames)));
            }

            if ('' === $answer) {
                continue;
            }

            // The answer is kept as HTML on purpose, as legacy did: it is the learner's rich-text
            // answer, and the inbox sanitizes message content before rendering it.
            $answers[] = ['question' => $this->escape((string) $question->getQuestion()), 'answer' => $answer];
        }

        return $answers;
    }

    private function buildAttemptDetails(
        CQuiz $quiz,
        Course $course,
        ?Session $session,
        User $learner,
        string $locale,
        string $exerciseLabel,
        string $extraRows = '',
    ): string {
        $courseLink = '<a href="'.$this->escape($this->getBaseUrl().'/course/'.(int) $course->getId().'/home?sid='.(int) $session?->getId()).'">'
            .$this->escape($course->getTitle()).'</a>';

        $rows = $this->buildRow($this->trans('Course name', $locale), $courseLink);
        if (null !== $session) {
            $rows .= $this->buildRow($this->trans('Session name', $locale), $this->escape($session->getTitle()));
        }
        $rows .= $this->buildRow($this->trans($exerciseLabel, $locale), $this->escape($quiz->getTitle()))
            .$this->buildRow($this->trans('Learner name', $locale), $this->escape($learner->getFullName()))
            .$this->buildRow($this->trans('Learner e-mail', $locale), $this->escape($learner->getEmail()))
            .$extraRows;

        return $this->trans('Attempt details', $locale).' : <br /><br /><table>'.$rows.'</table>';
    }

    /**
     * @param list<array{question: string, answer: string}> $answers
     */
    private function buildAnswersTable(array $answers, string $locale): string
    {
        $rows = '';
        foreach ($answers as $answer) {
            $rows .= $this->buildRow($this->trans('Question', $locale), $answer['question'])
                .$this->buildRow($this->trans('Answer', $locale), $answer['answer']);
        }

        return '<table border="0" cellpadding="3" cellspacing="3">'.$rows.'</table><br />';
    }

    private function buildRow(string $label, string $value): string
    {
        return '<tr><td valign="top">'.$label.'</td><td valign="top">&nbsp;'.$value.'</td></tr>';
    }

    private function buildCorrectionLink(string $url, string $locale): string
    {
        return '<br /><a href="'.$this->escape($url).'">'
            .$this->trans('Click this link to check the answer and/or give feedback', $locale).'</a>';
    }

    private function buildCorrectionUrl(CQuiz $quiz, Course $course, ?Session $session, TrackEExercise $attempt): string
    {
        $query = http_build_query([
            'cid' => (int) $course->getId(),
            'sid' => (int) $session?->getId(),
            'gid' => 0,
            'review' => 1,
            'mode' => 'review',
        ]);

        return $this->getBaseUrl().'/resources/exercise/'.(int) $course->getResourceNode()?->getId()
            .'/'.(int) $quiz->getIid().'/result/'.(int) $attempt->getExeId().'?'.$query;
    }

    private function formatScore(float $score, float $maxScore): string
    {
        if ('true' === $this->settingsManager->getSetting('mail.send_notification_score_in_percentage', true)) {
            $percentage = $maxScore > 0 ? round($score / $maxScore * 100, 2) : 0.0;

            return $percentage.'%';
        }

        return round($score, 2).' / '.round($maxScore, 2);
    }

    private function send(User $recipient, User $learner, string $subject, string $body, TrackEExercise $attempt): void
    {
        try {
            $this->messageHelper->sendMessage(
                (int) $recipient->getId(),
                $subject,
                $body,
                senderId: (int) $learner->getId(),
                forceTitleWhenSendingEmail: true,
            );
        } catch (Throwable $exception) {
            // A notification must never fail the learner's attempt.
            $this->logger->error('Unable to send an exercise notification.', [
                'attemptId' => (int) $attempt->getExeId(),
                'recipientId' => (int) $recipient->getId(),
                'exception' => $exception,
            ]);
        }
    }

    private function getBaseUrl(): string
    {
        return (string) $this->requestStack->getCurrentRequest()?->getSchemeAndHttpHost();
    }

    private function trans(string $key, string $locale): string
    {
        return $this->escape($this->translator->trans($key, [], null, $locale));
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
