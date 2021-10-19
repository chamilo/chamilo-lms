<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CQuizQuestionRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CQuizQuestionRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $exercise = (new CQuiz())
            ->setTitle('exercise')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $em->persist($exercise);

        $question = (new CQuizQuestion())
            ->setQuestionCode('code')
            ->setQuestion('question')
            ->setDescription('desc')
            ->setType(1)
            ->setExtra('extra')
            ->setFeedback('feedback')
            ->setLevel(1)
            ->setPonderation(100)
            ->setPosition(1)
            ->setParent($course)
            ->addCourseLink($course)
            ->setCreator($teacher)
        ;
        $em->persist($question);

        $quizRelQuestion = (new CQuizRelQuestion())
            ->setQuestion($question)
            ->setQuiz($exercise)
            ->setQuestionOrder(1)
        ;
        $exercise->getQuestions()->add($quizRelQuestion);

        $em->flush();

        $this->assertSame(1, $exercise->getQuestions()->count());
        $this->assertSame(1, $repo->count([]));
        $this->assertSame('', $repo->getHotSpotImageUrl($question));
    }
}
