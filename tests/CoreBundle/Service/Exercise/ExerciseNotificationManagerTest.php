<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\AttemptFile;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Service\Exercise\ExerciseNotificationManager;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Teacher notifications of the course setting email_alert_manager_on_new_quiz, sent from the Vue
 * exercise runtime. They were lost when the exercise player moved to Vue: a session coach whose
 * learner answered an open question was never told there was something to correct.
 *
 * The cases pin who is told (base-course teachers outside a session, the course coaches and the
 * general coaches inside one), what each option triggers, and that the people who run the course
 * never notify themselves.
 */
final class ExerciseNotificationManagerTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const OPEN_SUBJECT = 'A learner has answered an open question';
    private const ORAL_SUBJECT = 'A learner has attempted one or more oral question';
    private const END_SUBJECT = 'A learner attempted an exercise';
    private const START_SUBJECT = 'Student just started an exercise';
    private const FREE_ANSWER = 5;
    private const ORAL_EXPRESSION = 13;

    private EntityManagerInterface $entityManager;
    private ExerciseNotificationManager $notificationManager;
    private Course $course;
    private Session $session;
    private CQuiz $quiz;
    private CQuizQuestion $openQuestion;
    private CQuizQuestion $oralQuestion;
    private User $teacher;
    private User $courseCoach;
    private User $generalCoach;
    private User $learner;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        Container::setSession(new HttpSession(new MockArraySessionStorage()));

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->notificationManager = $container->get(ExerciseNotificationManager::class);

        $suffix = bin2hex(random_bytes(4));

        $this->teacher = $this->createRecipient('enm_teacher_'.$suffix);
        $this->courseCoach = $this->createRecipient('enm_coach_'.$suffix);
        $this->generalCoach = $this->createRecipient('enm_gcoach_'.$suffix);
        $this->learner = $this->createRecipient('enm_learner_'.$suffix);

        $this->course = $this->createCourse('ENM '.$suffix);
        $this->course->addUserAsTeacher($this->teacher);
        $this->course->addUserAsStudent($this->learner);
        $container->get(CourseRepository::class)->update($this->course);

        $this->session = (new Session())
            ->setTitle('ENM session '.$suffix)
            ->addAccessUrl($this->getAccessUrl())
            ->addGeneralCoach($this->generalCoach)
            ->addCourse($this->course)
        ;
        $this->session->addUserInCourse(Session::COURSE_COACH, $this->courseCoach, $this->course);
        $this->session->addUserInCourse(Session::STUDENT, $this->learner, $this->course);
        $container->get(SessionRepository::class)->update($this->session);

        $admin = $this->getUser('admin');
        $this->quiz = (new CQuiz())
            ->setTitle('ENM exercise '.$suffix)
            ->setParent($this->course)
            ->setCreator($admin)
            ->addCourseLink($this->course)
        ;
        $this->entityManager->persist($this->quiz);

        $this->openQuestion = $this->createQuestion('Explain the open question '.$suffix, self::FREE_ANSWER, 1, $admin);
        $this->oralQuestion = $this->createQuestion('Say the oral question '.$suffix, self::ORAL_EXPRESSION, 2, $admin);
        $this->entityManager->flush();

        $container->get(SettingsManager::class)->updateSetting('exercise.block_quiz_mail_notification_general_coach', 'false');
    }

    public function testOpenAnswerInSessionNotifiesTheCourseCoachAndTheGeneralCoachButNotTheBaseTeacher(): void
    {
        // The reported case: the session's coaches run the learners there, so they correct the
        // answer. The base-course teacher does not follow session learners.
        $this->setCourseOptions('3');
        $attempt = $this->createAttempt($this->session, [$this->openQuestion->getIid() => '<p>My open answer</p>']);

        $this->finish($attempt, $this->session);

        self::assertSame([self::OPEN_SUBJECT], $this->receivedSubjects($this->courseCoach));
        self::assertSame([self::OPEN_SUBJECT], $this->receivedSubjects($this->generalCoach));
        self::assertSame([], $this->receivedSubjects($this->teacher));
        self::assertStringContainsString('<p>My open answer</p>', $this->lastContent($this->courseCoach));
    }

    public function testGeneralCoachIsLeftOutWhenThePlatformBlocksIt(): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting('exercise.block_quiz_mail_notification_general_coach', 'true');
        $this->setCourseOptions('3');
        $attempt = $this->createAttempt($this->session, [$this->openQuestion->getIid() => 'Answer']);

        $this->finish($attempt, $this->session);

        self::assertSame([self::OPEN_SUBJECT], $this->receivedSubjects($this->courseCoach));
        self::assertSame([], $this->receivedSubjects($this->generalCoach));
    }

    public function testOutsideASessionTheCourseTeachersAreToldWhenTheLearnerStartsAndFinishes(): void
    {
        $this->setCourseOptions('1,2');
        $attempt = $this->createAttempt(null, [$this->openQuestion->getIid() => 'Answer']);

        $this->loginAs($this->learner);
        $this->notificationManager->notifyOnStart($this->quiz, $this->course, null, $this->learner, $attempt);
        self::assertSame([self::START_SUBJECT], $this->receivedSubjects($this->teacher));

        $this->finish($attempt, null);

        // Option 3 is off: the open answer alone must not add a third message.
        self::assertSame([self::START_SUBJECT, self::END_SUBJECT], $this->receivedSubjects($this->teacher));
        self::assertSame([], $this->receivedSubjects($this->courseCoach));
    }

    public function testOpenQuestionOptionStaysSilentWhenNoOpenQuestionWasAnswered(): void
    {
        // Option 3 is about answers that need correcting; an empty one needs nothing.
        $this->setCourseOptions('3');
        $attempt = $this->createAttempt(null, [$this->openQuestion->getIid() => '']);

        $this->finish($attempt, null);

        self::assertSame([], $this->receivedSubjects($this->teacher));
    }

    public function testOralAnswerNotificationNamesTheRecording(): void
    {
        // The Vue recorder stores the answer as an attached file and an empty answer text, so
        // the recording is the only proof the learner answered.
        $this->setCourseOptions('4');
        $attempt = $this->createAttempt(null, [$this->oralQuestion->getIid() => ''], 'oral_recording_test.wav');

        $this->finish($attempt, null);

        self::assertSame([self::ORAL_SUBJECT], $this->receivedSubjects($this->teacher));
        self::assertStringContainsString('oral_recording_test.wav', $this->lastContent($this->teacher));
    }

    public function testExerciseOptionsReplaceTheCourseOptions(): void
    {
        $this->setCourseOptions('3');
        $this->quiz->setNotifications('1');
        $this->entityManager->flush();
        $attempt = $this->createAttempt(null, [$this->openQuestion->getIid() => 'Answer']);

        $this->finish($attempt, null);

        self::assertSame([self::END_SUBJECT], $this->receivedSubjects($this->teacher));
    }

    public function testNoneOptionDisablesEveryNotification(): void
    {
        $this->setCourseOptions('0,1,3');
        $attempt = $this->createAttempt(null, [$this->openQuestion->getIid() => 'Answer']);

        $this->finish($attempt, null);

        self::assertSame([], $this->receivedSubjects($this->teacher));
    }

    public function testTeacherTakingTheExerciseNotifiesNobody(): void
    {
        // Legacy skipped api_is_allowed_to_edit() users: a teacher trying the exercise out
        // must not flood their colleagues with notifications.
        $this->setCourseOptions('1,2,3');
        $otherTeacher = $this->createRecipient('enm_teacher2_'.bin2hex(random_bytes(4)));
        $this->course->addUserAsTeacher($otherTeacher);
        self::getContainer()->get(CourseRepository::class)->update($this->course);
        $attempt = $this->createAttempt(null, [$this->openQuestion->getIid() => 'Answer'], null, $this->teacher);

        $this->loginAs($this->teacher);
        $this->notificationManager->notifyOnStart($this->quiz, $this->course, null, $this->teacher, $attempt);
        $this->notificationManager->notifyOnFinish($this->quiz, $this->course, null, $this->teacher, $attempt, 1.0, 1.0);

        self::assertSame([], $this->receivedSubjects($otherTeacher));
    }

    public function testLearnerNameIsEscapedInTheMessage(): void
    {
        // The learner chooses their own name: it must reach the teacher as text, not markup.
        $this->learner->setFirstname('<img src=x onerror=alert(1)>');
        $this->entityManager->flush();
        $this->setCourseOptions('1');
        $attempt = $this->createAttempt(null, [$this->openQuestion->getIid() => 'Answer']);

        $this->finish($attempt, null);

        $content = $this->lastContent($this->teacher);
        self::assertStringNotContainsString('<img src=x', $content);
        self::assertStringContainsString('&lt;img src=x', $content);
    }

    private function createRecipient(string $username): User
    {
        $user = $this->createUser($username);
        // Subjects are asserted in English, the language each recipient reads them in.
        $user->setLocale('en_US');
        $this->entityManager->flush();

        return $user;
    }

    private function createQuestion(string $title, int $type, int $position, User $creator): CQuizQuestion
    {
        $question = (new CQuizQuestion())
            ->setQuestion($title)
            ->setPonderation(1.0)
            ->setPosition($position)
            ->setType($type)
            ->setLevel(1)
            ->setParent($this->course)
            ->setCreator($creator)
            ->addCourseLink($this->course)
        ;
        $this->entityManager->persist($question);

        $relation = (new CQuizRelQuestion())
            ->setQuiz($this->quiz)
            ->setQuestion($question)
            ->setQuestionOrder($position)
        ;
        $this->entityManager->persist($relation);

        return $question;
    }

    private function setCourseOptions(string $value): void
    {
        $setting = (new CCourseSetting())
            ->setCId((int) $this->course->getId())
            ->setVariable('email_alert_manager_on_new_quiz')
            ->setCategory('quiz')
            ->setValue($value)
            ->setTitle('')
        ;
        $this->entityManager->persist($setting);
        $this->entityManager->flush();
    }

    /**
     * Rows are written through DBAL, as TrackEAttemptApiSecurityTest does: TrackEExercise::$exeId
     * has no default, which the API Platform purge listener cannot read before the insert.
     *
     * @param array<int, string> $answers answer text keyed by question id
     */
    private function createAttempt(?Session $session, array $answers, ?string $recordingName = null, ?User $user = null): TrackEExercise
    {
        $user ??= $this->learner;
        $connection = $this->entityManager->getConnection();
        $connection->insert('track_e_exercises', [
            'exe_user_id' => (int) $user->getId(),
            'c_id' => (int) $this->course->getId(),
            'session_id' => $session?->getId(),
            'exe_exo_id' => (int) $this->quiz->getIid(),
            'exe_date' => '2026-01-01 10:00:00',
            'score' => 0.0,
            'max_score' => 2.0,
            'user_ip' => '127.0.0.1',
            'status' => 'completed',
            'data_tracking' => implode(',', array_keys($answers)),
            'start_date' => '2026-01-01 09:00:00',
            'steps_counter' => 0,
            'orig_lp_id' => 0,
            'orig_lp_item_id' => 0,
            'exe_duration' => 60,
            'orig_lp_item_view_id' => 0,
            'questions_to_check' => '',
        ]);
        $attemptId = (int) $connection->lastInsertId();

        foreach ($answers as $questionId => $answer) {
            $connection->insert('track_e_attempt', [
                'exe_id' => $attemptId,
                'user_id' => (int) $user->getId(),
                'question_id' => $questionId,
                'answer' => $answer,
                'teacher_comment' => '',
                'marks' => 0.0,
                'tms' => '2026-01-01 10:00:00',
                'seconds_spent' => 30,
            ]);

            if (null !== $recordingName) {
                $row = $this->entityManager->find(TrackEAttempt::class, (int) $connection->lastInsertId());
                $node = (new ResourceNode())
                    ->setTitle($recordingName)
                    ->setResourceType($this->getAttemptFileResourceType())
                    ->setCreator($user)
                ;
                $this->entityManager->persist($node);
                $attemptFile = (new AttemptFile())->setResourceNode($node);
                $row->addAttemptFile($attemptFile);
                $this->entityManager->persist($attemptFile);
                $this->entityManager->flush();
            }
        }

        return $this->entityManager->find(TrackEExercise::class, $attemptId);
    }

    private function getAttemptFileResourceType(): ResourceType
    {
        $type = $this->entityManager->getRepository(ResourceType::class)->findOneBy(['title' => 'attempt_file']);
        if (!$type instanceof ResourceType) {
            self::markTestSkipped('The attempt_file resource type is not present in this DB.');
        }

        return $type;
    }

    private function finish(TrackEExercise $attempt, ?Session $session): void
    {
        $this->loginAs($this->learner);
        $this->notificationManager->notifyOnFinish($this->quiz, $this->course, $session, $this->learner, $attempt, 0.0, 2.0);
    }

    private function loginAs(User $user): void
    {
        self::getContainer()->get(TokenStorageInterface::class)->setToken(
            new UsernamePasswordToken($user, 'main', $user->getRoles())
        );
    }

    /**
     * @return list<string>
     */
    private function receivedSubjects(User $user): array
    {
        return array_map(
            static fn (Message $message): string => $message->getTitle(),
            $this->receivedMessages($user),
        );
    }

    private function lastContent(User $user): string
    {
        $messages = $this->receivedMessages($user);

        return [] === $messages ? '' : end($messages)->getContent();
    }

    /**
     * @return list<Message>
     */
    private function receivedMessages(User $user): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('message')
            ->from(Message::class, 'message')
            ->innerJoin('message.receivers', 'receiver')
            ->andWhere('receiver.receiver = :user')
            ->andWhere('receiver.receiverType = :type')
            ->setParameter('user', (int) $user->getId())
            ->setParameter('type', MessageRelUser::TYPE_TO)
            ->orderBy('message.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
