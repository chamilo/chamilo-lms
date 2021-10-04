<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\AttemptFeedback;
use Chamilo\CoreBundle\Entity\AttemptFile;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackExercise;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Repository\TrackExerciseRepository;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
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

        $repo = self::getContainer()->get(TrackExerciseRepository::class);
        $exerciseRepo = self::getContainer()->get(CQuizRepository::class);
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
        $this->assertSame(1, $repo->count([]));
        $this->assertSame(1, $exerciseRepo->count([]));

        $repo->delete($trackExercise);

        $this->assertSame(0, $repo->count([]));
        $this->assertSame(1, $exerciseRepo->count([]));
    }

    public function testCreateWithAttempt(): void
    {
        self::bootKernel();

        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(TrackExerciseRepository::class);
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
            ->setTms(new DateTime())
            ->setQuestionId(1)
            ->setPosition(1)
            ->setAnswer('great')
            ->setMarks(100)
            ->setUser($student)
        ;

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
            ->addAttempt($attempt)
        ;
        $em->persist($attempt);
        $em->persist($trackExercise);
        $this->assertHasNoEntityViolations($attempt);
        $this->assertHasNoEntityViolations($trackExercise);
        $em->flush();

        //$trackExercise = $repo->find($trackExercise->getExeId());
        $this->assertSame(1, $trackExercise->getAttempts()->count());
        $this->assertInstanceOf(TrackEAttempt::class, $trackExercise->getAttemptByQuestionId(1));

        $file = $this->getUploadedFile();

        $assetRepo = self::getContainer()->get(AssetRepository::class);
        $em = $this->getEntityManager();

        // Create asset.
        $asset = (new Asset())
            ->setTitle('test')
            ->setCategory(Asset::EXERCISE_ATTEMPT)
            ->setFile($file)
        ;
        $em->persist($asset);
        $em->flush();

        $feedback = (new AttemptFeedback())
            ->setUser($student)
            ->setComment('great!')
            ->setAsset($asset)
        ;
        $attempt->addAttemptFeedback($feedback);
        $this->assertHasNoEntityViolations($feedback);
        $em->persist($feedback);
        $em->flush();

        $this->assertNotNull($feedback->getId());
        $this->assertNotNull($feedback->getAsset());
        $this->assertNotNull($feedback->getUser());
        $this->assertNotNull($feedback->getAttempt());
        $this->assertNotNull($feedback->getCreatedAt());
        $this->assertNotNull($feedback->getUpdatedAt());

        $asset = (new Asset())
            ->setTitle('test')
            ->setCategory(Asset::EXERCISE_ATTEMPT)
            ->setFile($file)
        ;
        $em->persist($asset);
        $em->flush();

        $attemptFile = (new AttemptFile())
            ->setComment('great!')
            ->setAsset($asset)
        ;
        $attempt->addAttemptFile($attemptFile);
        $this->assertHasNoEntityViolations($attemptFile);

        $em->persist($attemptFile);
        $em->flush();

        $this->assertNotNull($attemptFile->getId());
        $this->assertNotNull($attemptFile->getAsset());
        $this->assertNotNull($attemptFile->getAttempt());
        $this->assertNotNull($attemptFile->getCreatedAt());
        $this->assertNotNull($attemptFile->getUpdatedAt());

        $this->assertSame(1, $trackExercise->getAttempts()->count());
        $this->assertSame(1, $attempt->getAttemptFeedbacks()->count());
        $this->assertSame(1, $attempt->getAttemptFiles()->count());

        $this->assertSame(1, $repo->count([]));
        $this->assertSame(1, $exerciseRepo->count([]));
    }
}
