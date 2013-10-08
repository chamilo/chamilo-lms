<?php

namespace Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NoResultException;

/**
 * CQuizDistributionRepository
 *
 */
class CQuizDistributionRepository extends EntityRepository
{
    /**
     * @param \Entity\CQuizDistribution $distribution
     */
    public function addDistribution(\Entity\CQuizDistribution $distribution, \Entity\Course $course)
    {
        $exerciseId = $distribution->getExerciseId();
        $exercise = new \Exercise($course->getId());
        $exercise->read($exerciseId);
        $questionList = $exercise->getQuestionList();
        $distribution->setDataTracking(implode(',', $questionList));

        $em = $this->getEntityManager();
        $em->persist($distribution);
        $em->flush();

        // 2. Registering quiz distribution + quiz distribution questions
        if ($distribution) {
            foreach ($questionList as $questionId) {
                $distributionQuestion = new \Entity\CQuizDistributionQuestions();
                $questionObj = \Question::read($questionId);
                $categories = $questionObj->get_categories_from_question();
                if (!empty($categories)) {
                    $categoryId = current($categories);
                    $distributionQuestion->setCategoryId($categoryId);
                }
                $distributionQuestion->setQuestionId($questionId);
                $distributionQuestion->setDistribution($distribution);
                $em->persist($distributionQuestion);
            }
        }

        // 3. Saving to the DB
        $em->flush();

        // 4. Getting questions per categories added in the current exercise // related via the matrix
        $categoriesInExercise = $exercise->get_categories_in_exercise();

        // 5. Getting questions from each category
        $questionsPerCategoryInExercise = array();

        if (!empty($categoriesInExercise)) {
            foreach ($categoriesInExercise as $categoryInfo) {
                $categoryId = $categoryInfo['category_id'];

                $questions = \Testcategory::getQuestionsByCategory($categoryId);
                $questionsPerCategoryInExercise[$categoryId] = $questions;
            }
        }

        // 6. Checking if the quiz distribution exists
        $criteria = array('quizDistributionId' => $distribution->getId());

        $currentDistributionQuestions = $em->getRepository('Entity\CQuizDistributionQuestions')->findBy($criteria);

        // 6. Checking if the quiz distribution exists
        $criteria = array('exerciseId' => $exerciseId);
        $distributionsInExercise = $em->getRepository('Entity\CQuizDistribution')->findBy($criteria);
        $distributionIdList = array();
        if ($distributionsInExercise) {
            foreach ($distributionsInExercise as $distributionItem) {
                // Avoid current dist
                if ($distributionItem->getId() == $distribution->getId()) {
                    continue;
                }
                $distributionIdList[] = $distributionItem->getId();
            }
        }

        $questionsPerCategoryInDistribution = array();

        if (!empty($currentDistributionQuestions)) {
            /** @var \Entity\CQuizDistributionQuestions $question */
            foreach ($currentDistributionQuestions as $question) {
                $questionsPerCategoryInDistribution[$question->getCategoryId()][] = $question->getQuestionId();
            }

            /** @var \Entity\CQuizDistributionQuestions $question */
            foreach ($currentDistributionQuestions as $question) {
                $result = array();

                if (!empty($distributionIdList)) {

                    // Checking if there are questions from this category that are not added yet
                    $qb = $em->getRepository('Entity\CQuizDistributionQuestions')->createQueryBuilder('e');
                    $qb->where('e.categoryId = :categoryId')
                        ->andWhere('e.questionId = :questionId')
                        ->andWhere($qb->expr()->in('e.quizDistributionId', $distributionIdList))
                        ->setParameters(
                            array(
                                'categoryId' => $question->getCategoryId(),
                                'questionId' => $question->getQuestionId()
                            )
                        )
                    ;
                    $result = $qb->getQuery()->getArrayResult();
                }

                // Doubles found
                if (count($result) > 0) {
                    $questionListFromDistribution = $questionsPerCategoryInDistribution[$question->getCategoryId()];
                    $questionListFromExercise = isset($questionsPerCategoryInExercise[$question->getCategoryId()]) ? $questionsPerCategoryInExercise[$question->getCategoryId()] : array();

                    $diff = array_diff($questionListFromExercise, $questionListFromDistribution);

                    // Found some questions. Great! now we can select one question.
                    if (!empty($diff)) {
                        shuffle($diff);
                        $selectedQuestionId = current($diff);
                    } else {
                        // Nothing found take one random question from the question list in the exercise
                        shuffle($questionListFromExercise);
                        $selectedQuestionId = current($questionListFromExercise);
                    }

                    // $selectedQuestionId contains the new question id
                    if (!empty($selectedQuestionId)) {
                        $questionsPerCategoryInDistribution[$question->getCategoryId()][] = $selectedQuestionId;
                        // Update the relationship
                        $question->setQuestionId($selectedQuestionId);
                        $em->persist($question);
                        $em->flush();
                    }
                }
            }

            $criteria = array('quizDistributionId' => $distribution->getId());
            $currentDistributionQuestions = $em
                ->getRepository('Entity\CQuizDistributionQuestions')
                ->findBy($criteria);

            $questionList = array();
            foreach ($currentDistributionQuestions as $question) {
                $questionList[] = $question->getQuestionId();
            }

            // Rebuild question list
            $distribution->setDataTracking(implode(',', $questionList));
            $em->persist($distribution);
            $em->flush();
        }
    }
}
