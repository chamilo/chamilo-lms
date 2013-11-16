<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Admin\ExerciseStatistics;

use Silex\Application;
use ChamiloLMS\Controller\CommonController;
use Entity;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use ChamiloLMS\Form\CQuizDistributionType;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Tests\ValidationVisitorTest;

/**
 * Class ExerciseStatisticsController
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class ExerciseStatisticsController extends CommonController
{
    public $exerciseId = null;

    /**
     * @Route("/{exerciseId}/distribution/result")
     * @Method({"GET"})
     */
    public function indexAction($exerciseId)
    {
        if (!api_is_platform_admin()) {
            return $this->abort(500);
        }

        set_time_limit(0);

        $template = $this->get('template');
        $em = $this->getManager();
        $course = $this->getCourse();
        $sessionId = $this->getSessionId();
        $courseId = $course->getId();

        // Getting exercise
        $exercise = new \Exercise($course->getId());
        $exercise->read($exerciseId);

        /** @var  Gedmo\Tree\Entity\Repository\NestedTreeRepository $repo */
        $repo = $em->getRepository('Entity\CQuizCategory');

        // Getting categories
        //$questionList = $exercise->getQuestionList();
        $questionList = $exercise->getQuestionOrderedList();

        // Getting categories
        $categories = $exercise->getListOfCategoriesWithQuestionForTest();

        $cat = new \Testcategory();
        $categoriesAddedInExercise = $cat->getCategoryExerciseTree($exerciseId, $courseId, 'root, lft ASC', false, true, false);
        $categories = \Testcategory::getQuestionsByCat($exerciseId, $questionList, $categoriesAddedInExercise, $courseId);
        $categories = $exercise->fillQuestionByCategoryArray($categories);

        $categories = $exercise->getListOfCategoriesWithQuestionForTest($categories);

        $globalCategories = \Testcategory::globalizeCategories($exerciseId, $courseId, $categories, true, true);

        $categoryList = array();
        $categoryListInverse = array();

        foreach ($globalCategories as $categoryId => & $data) {
            $data['id'] = $categoryId;
            $cat = $em->find('Entity\CQuizCategory', $categoryId);
            if (isset($cat)) {
                $children = $repo->getChildren($cat);

                // Acceso a cargos directivos

                /**  @var Entity\CQuizCategory $child */
                foreach ($children as $child) {
                    /** @var Entity\CQuizCategory $cat */
                    $cat = $em->find('Entity\CQuizCategory', $child->getIid());

                    $subChild = $repo->getChildren($cat);
                    /** @var Entity\CQuizCategory $sub */
                    foreach ($subChild as $sub) {
                        $categoryList[$categoryId][$sub->getIid()] = $sub->getTitle();
                        $categoryListInverse[$sub->getIid()] = $categoryId;
                    }
                }
            }
        }


        if (empty($categories) || empty($globalCategories)) {
            throw new \Exception('No categories in this exercise.');
        }

        // Grouping question id with categories.
        $questionListWithCategory = array();
        foreach ($categories as $categoryData) {
            foreach ($categoryData['question_list'] as $questionId) {
                //$questionListWithCategory[$questionId] = $categoryData['id'];
                if (isset($categoryListInverse[$categoryData['id']])) {
                    $questionListWithCategory[$questionId] = $categoryListInverse[$categoryData['id']];
                }
            }
        }

        $params = array(
            'exerciseId' => $exerciseId,
            //'sessionId' => $sessionId,
            'cId' => $course->getId()
        );

        // Getting forms
        $quizDistributionRelSessions = $em->getRepository("Entity\CQuizDistributionRelSession")->findBy($params);

        /** @var Entity\CQuizDistributionRelSession $distribution */

        $finalResults = array();

        // Getting results per distribution
        $attemptsPerDistribution = array();
        /** @var Entity\CQuizDistributionRelSession $distribution */
        $distributionIdList = array();
        foreach ($quizDistributionRelSessions as $distribution) {
            $attemptsPerDistribution[$distribution->getQuizDistributionId()] = $em->getRepository("Entity\TrackExercise")->getResultsWithNoSession(
                $exerciseId,
                $courseId,
                //$sessionId,
                $distribution->getQuizDistributionId()
            );
        }

        $distributionIdList = array_keys($attemptsPerDistribution);

        if (empty($attemptsPerDistribution)) {
            throw new \Exception('Not enough data. TrackExercise has not quizDistributionId');
        }

        foreach ($distributionIdList as $distributionId) {
            $attemptList = $attemptsPerDistribution[$distributionId];
            $exeIdList = array_keys($attemptList);
            $markPerQuestions = $em->getRepository("Entity\TrackAttempt")->getResults(
                $exeIdList
            );

            $total = 0;
            if (!empty($markPerQuestions)) {
                foreach ($markPerQuestions as $questionId => $marks) {
                    /*if (!isset($questionListWithCategory[$questionId])) {
                        continue;
                    }*/
                    $categoryId = $questionListWithCategory[$questionId];
                    if (!isset($finalResults[$distributionId][$categoryId])) {
                        $finalResults[$distributionId][$categoryId]['result'] = 0;
                    }
                    $finalResults[$distributionId][$categoryId]['result'] += $marks;
                    $total += $marks;
                    $finalResults[$distributionId][$categoryId]['modif'] = 1;
                }
            }
            $finalResults[$distributionId]['counter'] = count($exeIdList);
            $finalResults[$distributionId]['total'] = $total;
        }

        $template->assign('results', $finalResults);

        $template->assign('global_categories', $globalCategories);
        $template->assign('categories', $categories);
        $template->assign('total', $total);
        $template->assign('cId', $this->getCourseId());
        $template->assign('exerciseId', $exerciseId);
        $template->assign('sessionId', $this->getSessionId());
        $template->assign('distributions', $distributionIdList);
        $response = $template->render_template($this->getTemplatePath().'index.tpl');
        return new Response($response, 200, array());
    }

    public function indexOldAction($exerciseId)
    {
        set_time_limit(0);

        $template = $this->get('template');
        $em = $this->getManager();
        $course = $this->getCourse();
        $sessionId = $this->getSessionId();
        $courseId = $course->getId();

        // Getting exercise
        $exercise = new \Exercise($course->getId());
        $exercise->read($exerciseId);

        // Getting categories
        $categories = $exercise->getListOfCategoriesWithQuestionForTest();

        if (empty($categories)) {
            throw new \Exception('No categories in this exercise.');
        }

        // Grouping question id with categories.
        $questionListWithCategory = array();
        foreach ($categories as $categoryData) {
            foreach ($categoryData['question_list'] as $questionId) {
                $questionListWithCategory[$questionId] = $categoryData['id'];
            }
        }

        $params = array(
            'exerciseId' => $exerciseId,
            'sessionId' => $sessionId,
            'cId' => $course->getId()
        );

        // Getting forms
        $quizDistributionRelSessions = $em->getRepository("Entity\CQuizDistributionRelSession")->findBy($params);

        /** @var Entity\CQuizDistributionRelSession $distribution */

        $finalResults = array();

        // Getting tracks per distribution
        $attemptsPerDistribution = array();
        foreach ($quizDistributionRelSessions as $distribution) {
            $attemptsPerDistribution[$distribution->getId()] = $em->getRepository("Entity\TrackExercise")->getResults(
                $exerciseId,
                $courseId,
                $sessionId,
                $distribution->getId()
            );
        }

        if (empty($attemptsPerDistribution)) {
            throw new \Exception('Not enough data TrackExercise has not quizDistributionId');
        }

        foreach ($quizDistributionRelSessions as $distribution) {
            $exeIdList = $attemptsPerDistribution[$distribution->getId()];

            $markPerQuestions = $em->getRepository("Entity\TrackAttempt")->getResults(
                $exeIdList
            );
            $total = 0;
            if (!empty($markPerQuestions)) {
                foreach ($markPerQuestions as $questionId => $marks) {
                    $categoryId = $questionListWithCategory[$questionId];
                    if (!isset($finalResults[$distribution->getId()][$categoryId])) {
                        $finalResults[$distribution->getId()][$categoryId] = 0;
                    }
                    $finalResults[$distribution->getId()][$categoryId] += $marks;
                    $total += $marks;
                }
            }
            $finalResults[$distribution->getId()]['counter'] = count($exeIdList);
            $finalResults[$distribution->getId()]['total'] = $total;
        }

        $template->assign('results', $finalResults);
        $template->assign('categories', $categories);
        $template->assign('total', $total);

        $template->assign('distributions', $quizDistributionRelSessions);
        $response = $template->render_template($this->getTemplatePath().'index.tpl');
        return new Response($response, 200, array());
    }

    /**
     * @return array
     */
    protected function getExtraParameters()
    {
        return array('exerciseId', 'cidReq', 'id_session');
    }

    /**
     * @return string|void
     */
    protected function getControllerAlias()
    {
        return 'exercise_statistics.controller';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplatePath()
    {
        return 'admin/exercise_statistics/exercise_distribution/';
    }

    /**
     * @return \Entity\Repository\JuryRepository
     */
    protected function getRepository()
    {
        return $this->get('orm.em')->getRepository('Entity\CQuizDistribution');
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewEntity()
    {
        return new Entity\CQuizDistribution();
    }

    protected function getDefaultEntity()
    {
        $dist =  new Entity\CQuizDistribution();
        $dist ->setExerciseId($this->exerciseId);
        return $dist;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return new CQuizDistributionType();
    }
}
