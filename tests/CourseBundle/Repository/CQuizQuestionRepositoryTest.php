<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Entity\CQuizQuestionOption;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestionCategory;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CQuizQuestionRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $questionRepo = self::getContainer()->get(CQuizQuestionRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $exercise = (new CQuiz())
            ->setTitle('exercise')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $em->persist($exercise);

        $quizQuestionCategory = (new CQuizQuestionCategory())
            ->setTitle('category')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $em->persist($quizQuestionCategory);

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
            ->setPicture('')
            ->setParent($course)
            ->addCourseLink($course)
            ->setCreator($teacher)
        ;

        $option = (new CQuizQuestionOption())
            ->setName('option 1')
            ->setQuestion($question)
            ->setPosition(1)
        ;

        $question->addCategory($quizQuestionCategory);
        $question->updateCategory($quizQuestionCategory);

        $quizRelQuestionCategory = (new CQuizRelQuestionCategory())
            ->setCountQuestions(1)
            ->setCategory($quizQuestionCategory)
            ->setQuiz($exercise)
        ;

        $em->persist($quizRelQuestionCategory);
        $em->persist($option);
        $em->persist($question);

        $quizRelQuestion = (new CQuizRelQuestion())
            ->setQuestion($question)
            ->setQuiz($exercise)
            ->setQuestionOrder(1)
        ;
        $exercise->getQuestions()->add($quizRelQuestion);

        $em->flush();

        $answer = (new CQuizAnswer())
            ->setComment('comment')
            ->setQuestion($question)
            ->setPosition(1)
            ->setAnswer('answer')
            ->setAnswerCode('answer')
            ->setDestination('')
            ->setCorrect(1)
            ->setHotspotCoordinates('')
            ->setHotspotType('')
            ->setPonderation(100)
        ;
        $em->persist($answer);
        $em->flush();
        $em->clear();

        /** @var CQuizQuestion $question */
        $question = $questionRepo->find($question->getIid());

        $this->assertSame(1, $question->getOptions()->count());
        $this->assertSame(1, $question->getAnswers()->count());
        $this->assertSame(1, $exercise->getQuestions()->count());
        $this->assertSame(1, $questionRepo->count([]));
        $this->assertSame(1, $quizRelQuestion->getQuestionOrder());
        $this->assertSame('', $questionRepo->getHotSpotImageUrl($question));
        $this->assertSame(1, $question->getCategories()->count());
    }
}
