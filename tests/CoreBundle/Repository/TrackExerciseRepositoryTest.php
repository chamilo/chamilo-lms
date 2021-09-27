<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\Tag;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackExercise;
use Chamilo\CoreBundle\Repository\TrackExerciseRepository;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

class TrackExerciseRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();
        $em = $this->getEntityManager();

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

        $trackExercise = (new TrackExercise())
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
            ->setExeExoId($exercise->getIid())
        ;
        $em->persist($trackExercise);
        $this->assertHasNoEntityViolations($trackExercise);
        $em->flush();
    }

    public function testCreateWithAttempt(): void
    {
        self::bootKernel();

        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(TrackExerciseRepository::class);

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

        $trackExercise = (new TrackExercise())
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
            ->setExeExoId($exercise->getIid())
        ;
        $em->persist($trackExercise);
        $this->assertHasNoEntityViolations($trackExercise);

        $this->assertSame(0, $trackExercise->getAttempts()->count());

        $attempt = (new TrackEAttempt())
            ->setTrackExercise($trackExercise)
            ->setTms(new DateTime())
            ->setQuestionId(1)
            ->setPosition(1)
            ->setAnswer('great')
            ->setMarks(100)
            ->setUser($student)
        ;
        $this->assertHasNoEntityViolations($attempt);
        $em->persist($attempt);

        $em->flush();

        $this->assertSame(1, $trackExercise->getAttempts()->count());
        $this->assertSame(1, $repo->count([]));
    }
}
