<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AttemptFeedback;
use Chamilo\CoreBundle\Entity\AttemptFile;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Repository\TrackEExerciseRepository;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

class TrackEExerciseRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();

        $course = $this->createCourse('new');

        $teacher = $this->createUser('teacher');
        $student = $this->createUser('student');

        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        /** @var TrackEExerciseRepository $trackExerciseRepo */
        $trackExerciseRepo = self::getContainer()->get(TrackEExerciseRepository::class);

        /** @var CQuizRepository $exerciseRepo */
        $exerciseRepo = self::getContainer()->get(CQuizRepository::class);

        $this->assertSame(1, $courseRepo->count([]));

        $exercise = (new CQuiz())
            ->setTitle('exercise')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $em->persist($exercise);
        $em->flush();

        $trackExercise = (new TrackEExercise())
            ->setQuestionsToCheck('')
            ->setExeDate(new DateTime())
            ->setStartDate(new DateTime())
            ->setSession(null)
            ->setStatus('completed')
            ->setCourse($course)
            ->setScore(100)
            ->setMaxScore(100)
            ->setDataTracking('')
            ->setUser($student)
            ->setUserIp('127.0.0.1')
            ->setStepsCounter(1)
            ->setOrigLpId(0)
            ->setOrigLpItemId(0)
            ->setOrigLpItemViewId(0)
            ->setExeDuration(10)
            ->setExpiredTimeControl(new DateTime())
            ->setQuiz($exercise)
        ;
        $em->persist($trackExercise);
        $this->assertHasNoEntityViolations($trackExercise);
        $em->flush();
        $this->assertSame(1, $trackExerciseRepo->count([]));
        $this->assertSame(1, $exerciseRepo->count([]));
        $this->assertSame(1, $courseRepo->count([]));

        $trackExerciseRepo->delete($trackExercise);

        $this->assertSame(1, $courseRepo->count([]));
        $this->assertSame(0, $trackExerciseRepo->count([]));
        $this->assertSame(1, $exerciseRepo->count([]));

        $em->remove($course);
        $em->flush();

        $this->assertSame(0, $courseRepo->count([]));
        $this->assertSame(0, $trackExerciseRepo->count([]));
        $this->assertSame(1, $exerciseRepo->count([]));
    }

    public function testCreateInSession(): void
    {
        $em = $this->getEntityManager();

        $course = $this->createCourse('new');
        $session = $this->createSession('session 1');

        $teacher = $this->createUser('teacher');
        $student = $this->createUser('student');

        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $sessionRepo = self::getContainer()->get(SessionRepository::class);
        $trackExerciseRepo = self::getContainer()->get(TrackEExerciseRepository::class);
        $exerciseRepo = self::getContainer()->get(CQuizRepository::class);

        $this->assertSame(1, $courseRepo->count([]));

        $exercise = (new CQuiz())
            ->setTitle('exercise')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $em->persist($exercise);
        $em->flush();

        $trackExercise = (new TrackEExercise())
            ->setQuestionsToCheck('')
            ->setExeDate(new DateTime())
            ->setStartDate(new DateTime())
            ->setSession($session)
            ->setStatus('completed')
            ->setCourse($course)
            ->setScore(100)
            ->setMaxScore(100)
            ->setDataTracking('')
            ->setUser($student)
            ->setUserIp('127.0.0.1')
            ->setStepsCounter(1)
            ->setOrigLpId(0)
            ->setOrigLpItemId(0)
            ->setOrigLpItemViewId(0)
            ->setExeDuration(10)
            ->setExpiredTimeControl(new DateTime())
            ->setQuiz($exercise)
        ;
        $em->persist($trackExercise);
        $this->assertHasNoEntityViolations($trackExercise);
        $em->flush();

        $this->assertSame(1, $trackExerciseRepo->count([]));
        $this->assertSame(1, $exerciseRepo->count([]));
        $this->assertSame(1, $courseRepo->count([]));

        $trackExerciseRepo->delete($trackExercise);

        $this->assertSame(1, $courseRepo->count([]));
        $this->assertSame(0, $trackExerciseRepo->count([]));
        $this->assertSame(1, $exerciseRepo->count([]));

        $em->remove($session);
        $em->flush();

        $this->assertSame(0, $trackExerciseRepo->count([]));
        $this->assertSame(1, $exerciseRepo->count([]));
        $this->assertSame(1, $courseRepo->count([]));
        $this->assertSame(0, $sessionRepo->count([]));
    }

    public function testCreateWithAttempt(): void
    {
        $em = $this->getEntityManager();

        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $trackExerciseRepo = self::getContainer()->get(TrackEExerciseRepository::class);
        $exerciseRepo = self::getContainer()->get(CQuizRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');
        $student = $this->createUser('student');

        $exercise = (new CQuiz())
            ->setTitle('exercise')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $em->persist($exercise);
        $em->flush();

        $attempt = (new TrackEAttempt())
            ->setTeacherComment('comment')
            ->setFilename('')
            ->setSecondsSpent(100)
            ->setTms(new DateTime())
            ->setQuestionId(1)
            ->setPosition(1)
            ->setAnswer('great')
            ->setMarks(100)
            ->setUser($student)
        ;

        $trackExercise = (new TrackEExercise())
            ->setQuestionsToCheck('')
            ->setExeDate(new DateTime())
            ->setStatus('completed')
            ->setCourse($course)
            ->setScore(100)
            ->setMaxScore(100)
            ->setDataTracking('')
            ->setUser($student)
            ->setUserIp('127.0.0.1')
            ->setStepsCounter(1)
            ->setOrigLpId(0)
            ->setOrigLpItemId(0)
            ->setOrigLpItemViewId(0)
            ->setExeDuration(10)
            ->setExpiredTimeControl(new DateTime())
            ->setQuiz($exercise)
            ->addAttempt($attempt)
        ;
        $em->persist($attempt);
        $em->persist($trackExercise);
        $this->assertHasNoEntityViolations($attempt);
        $this->assertHasNoEntityViolations($trackExercise);
        $em->flush();

        $this->assertSame(1, $trackExercise->getAttempts()->count());
        $this->assertInstanceOf(TrackEAttempt::class, $trackExercise->getAttemptByQuestionId(1));
        $this->assertNull($trackExercise->getAttemptByQuestionId(99));

        $em = $this->getEntityManager();

        $feedback = (new AttemptFeedback())
            ->setUser($student)
            ->setComment('great!')
            ->setResourceNode($this->createAttemptResourceNode($teacher))
        ;
        $attempt->addAttemptFeedback($feedback);
        $this->assertHasNoEntityViolations($feedback);
        $em->persist($feedback);
        $em->flush();

        $this->assertNotNull($feedback->getId());
        $this->assertNotNull($feedback->getResourceNode());
        $this->assertNotNull($feedback->getUser());
        $this->assertNotNull($feedback->getAttempt());
        $this->assertNotNull($feedback->getCreatedAt());
        $this->assertNotNull($feedback->getUpdatedAt());

        $attemptFile = (new AttemptFile())
            ->setComment('great!')
            ->setResourceNode($this->createAttemptResourceNode($student))
        ;
        $attempt->addAttemptFile($attemptFile);
        $this->assertHasNoEntityViolations($attemptFile);

        $em->persist($attemptFile);
        $em->flush();

        $this->assertNotNull($attemptFile->getId());
        $this->assertNotNull($attemptFile->getResourceNode());
        $this->assertNotNull($attemptFile->getAttempt());
        $this->assertNotNull($attemptFile->getCreatedAt());
        $this->assertNotNull($attemptFile->getUpdatedAt());

        $this->assertSame(1, $trackExercise->getAttempts()->count());
        $this->assertSame(1, $attempt->getAttemptFeedbacks()->count());
        $this->assertSame(1, $attempt->getAttemptFiles()->count());
        $this->assertSame(1, $trackExerciseRepo->count([]));
        $this->assertSame(1, $exerciseRepo->count([]));
        $this->assertSame(1, $courseRepo->count([]));

        // Delete course.
        $em->remove($course);
        $em->flush();

        $this->assertSame(0, $trackExerciseRepo->count([]));
        $this->assertSame(1, $exerciseRepo->count([]));
        $this->assertSame(0, $courseRepo->count([]));

        $teacher = $this->getUser('teacher');
        $this->assertNotNull($teacher);

        $student = $this->getUser('student');
        $this->assertNotNull($student);
    }

    /**
     * Attempt feedbacks and files hold their upload in a ResourceNode, the same way
     * ExerciseRuntimeAnswerProcessor builds them at runtime.
     */
    private function createAttemptResourceNode(User $creator): ResourceNode
    {
        $em = $this->getEntityManager();

        $tool = $em->getRepository(Tool::class)->findOneBy(['title' => 'quiz']);
        $this->assertInstanceOf(Tool::class, $tool);

        $resourceType = $em->getRepository(ResourceType::class)->findOneBy(['title' => 'attempt_file']);
        if (!$resourceType instanceof ResourceType) {
            $resourceType = (new ResourceType())
                ->setTitle('attempt_file')
                ->setTool($tool)
            ;
            $em->persist($resourceType);
        }

        $file = $this->getUploadedFile();
        $resourceFile = (new ResourceFile())
            ->setTitle($file->getFilename())
            ->setOriginalName($file->getFilename())
            ->setMimeType('image/png')
            ->setSize((int) $file->getSize())
            ->setFile($file)
        ;

        $node = (new ResourceNode())
            ->setTitle($file->getFilename())
            ->setResourceType($resourceType)
            ->setCreator($creator)
        ;
        $node->addResourceFile($resourceFile);

        $em->persist($node);
        $em->persist($resourceFile);
        $em->flush();

        return $node;
    }
}
