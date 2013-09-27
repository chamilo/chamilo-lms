<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Admin\QuestionManager;

use Silex\Application;
use ChamiloLMS\Controller\CommonController;
use Entity;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use ChamiloLMS\Form\CQuizDistributionType;
use Doctrine\Common\Collections\Criteria;

/**
 * Class DistributionController
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class ExerciseDistributionController extends CommonController
{
    public $exerciseId = null;

    /**
     * @Route("/{exerciseId}/distribution/list")
     * @Method({"GET"})
     */
    public function indexAction($exerciseId)
    {
        $criteria = array('exerciseId' => $exerciseId);
        $items = $this->getRepository()->findBy($criteria);

        $template = $this->get('template');
        $template->assign('exerciseId', $exerciseId);
        $template->assign('items', $items);
        $template->assign('links', $this->generateLinks());
        $response = $template->render_template($this->getTemplatePath().'list.tpl');
        return new Response($response, 200, array());
    }

    /**
     *
     * @Route("/{exerciseId}/distribution/{id}", requirements={"id" = "\d+"})
     * @Method({"GET"})
     */
    public function readAction($id)
    {
        return parent::readAction($id);
    }

    /**
     *
     * @Route("/{exerciseId}/distribution/{id}/toggle_visibility", requirements={"id" = "\d+"})
     * @Method({"GET"})
     */
    public function toogleVisibilityAction($exerciseId, $id)
    {
        $criteria = array('exerciseId' => $exerciseId, 'id' => $id);
        /** @var Entity\CQuizDistribution $distribution */
        $distribution = $this->getRepository()->findOneBy($criteria);

        $distribution->setActive(!$distribution->getActive());

        $this->getManager()->persist($distribution);
        $this->getManager()->flush();

        $this->get('session')->getFlashBag()->add('success', "Visibility changed");
        $url = $this->createUrl('list_link');
        return $this->redirect($url);
    }

    /**
     * @Route("/{exerciseId}/distribution/{id}/apply")
     * @Method({"GET"})
     */
    public function applyDistributionAction($exerciseId, $id)
    {
        $em = $this->getManager();
        $criteria = array('exerciseId' => $exerciseId);
        $distributionRelSession = $em->getRepository('Entity\CQuizDistributionRelSession')->findOneBy($criteria);

        if ($distributionRelSession) {
            $em->remove($distributionRelSession);
            $em->flush();
        }

        $distributionRelSession = new Entity\CQuizDistributionRelSession();
        /*$distributionRelSession->setCId($this->getCourse()->getId());
        $distributionRelSession->setSessionId($this->getSession()->getId());*/

        $distributionRelSession->setCId(api_get_course_int_id());
        $distributionRelSession->setSessionId(api_get_session_id());

        $distributionRelSession->setQuizDistributionId($id);
        $distributionRelSession->setExerciseId($exerciseId);
        $em->persist($distributionRelSession);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', "Distribution applied");
        $url = $this->createUrl('list_link');
        return $this->redirect($url);
    }

    /**
     * @Route("/{exerciseId}/distribution/add")
     * @Method({"GET"})
     */
    public function addDistributionAction($exerciseId)
    {
        $template = $this->get('template');
        $em = $this->getManager();
        $this->exerciseId = $exerciseId;
        $template->assign('exerciseId', $exerciseId);

        $request = $this->getRequest();
        $distribution = $this->getDefaultEntity();
        $form = $this->createForm($this->getFormType(), $distribution);

        $form->handleRequest($request);

        if ($form->isValid()) {

            $exercise = new \Exercise();
            $exercise->read($exerciseId);
            $questionList = $exercise->getQuestionList();
            $exercise->get_categories_in_exercise();

            /** @var Entity\CQuizDistribution $distribution */
            $distribution = $form->getData();

            $distribution->setDataTracking(implode(',', $questionList));
            $distribution->setAuthorUserId($this->getUser()->getUserId());

            $em->persist($distribution);
            // Registering quiz distribution + quiz distribution questions
            if ($distribution) {
                foreach ($questionList as $questionId) {
                    $distributionQuestion = new Entity\CQuizDistributionQuestions();

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

            // Checking from all distributions
            $em->flush();

            $categoriesInExercise = $exercise->get_categories_in_exercise();

            $questionsPerCategory = array();
            foreach ($categoriesInExercise as $categoryInfo) {
                $categoryId = $categoryInfo['category_id'];
                $questions = \Testcategory::getQuestionsByCategory($categoryId);
                $questionsPerCategory[$categoryId] = $questions;
            }

            $criteria = array('quizDistributionId' => $distribution->getId());
            $currentDistributionQuestions = $this->getManager()->getRepository('Entity\CQuizDistributionQuestions')->findBy($criteria);

            /** @var Entity\CQuizDistributionQuestions $question */
            foreach ($currentDistributionQuestions as $question) {
                $criteria = array('categoryId' => $question->getCategoryId(), 'questionId' => $question->getQuestionId());
                $result = $this->getManager()->getRepository('Entity\CQuizDistributionQuestions')->findBy($criteria);

                // doubles found !
                if (count($result) > 1) {

                    // Question list of this category
                    $questionList = $questionsPerCategory[$question->getCategoryId()];

                    // Checking if there are questions that are not added yet
                    $qb = $this->getManager()->getRepository('Entity\CQuizDistributionQuestions')->createQueryBuilder('e');
                    $qb->where('e.categoryId = :categoryId')
                        ->andWhere($qb->expr()->notIn('e.questionId', $questionList))
                        ->setParameters(array('categoryId' => $question->getCategoryId()));

                    $result = $qb->getQuery()->getArrayResult();
                    // Found some questions
                    if (count($result) > 0) {
                        shuffle($result);
                        $selected = current($result);
                    } else {
                        // Nothing found take one question
                        shuffle($questionList);
                        $selected = current($questionList);
                    }
                    // $selected contains the new question id
                    if (!empty($selected)) {
                        //remove the old and create a new one
                        $newQuestionDistribution = $question;
                        $em->remove($question);
                        $newQuestionDistribution->setQuestionId($selected);
                        $em->persist($newQuestionDistribution);
                        $em->flush();
                    }
                }
            }

            $currentDistributionQuestions = $this->getManager()->getRepository('Entity\CQuizDistributionQuestions')->findBy($criteria);
            $questionList = array();
            foreach($currentDistributionQuestions as $question) {
                $questionList[] = $question->getQuestionId();
            }

            // Rebuild question list
            $distribution->setDataTracking(implode(',', $questionList));
            $em->persist($distribution);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', "Added");
            $url = $this->createUrl('list_link');
            return $this->redirect($url);
        }

        $template = $this->get('template');
        $template->assign('links', $this->generateLinks());
        $template->assign('form', $form->createView());
        $response = $template->render_template($this->getTemplatePath().'add.tpl');
        return new Response($response, 200, array());
    }


    /**
     *
     * @Route("/{exerciseId}/distribution/{id}/edit", requirements={"id" = "\d+"})
     * @Method({"GET"})
     */
    public function editDistributionAction($exerciseId, $id)
    {
        $repo = $this->getRepository();
        $request = $this->getRequest();
        $item = $repo->findOneById($id);

        $this->exerciseId = $exerciseId;
        $template = $this->get('template');
        $template->assign('exerciseId', $exerciseId);
        $template->assign('id', $id);

        if ($item) {

            $form = $this->createForm($this->getFormType(), $item);

            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $this->updateAction($data);
                $this->get('session')->getFlashBag()->add('success', "Updated");
                $url = $this->createUrl('list_link');
                return $this->redirect($url);
            }

            $template->assign('item', $item);
            $template->assign('form', $form->createView());
            $template->assign('links', $this->generateLinks());
            $response = $template->render_template($this->getTemplatePath().'edit.tpl');
            return new Response($response, 200, array());
        } else {
            return $this->createNotFoundException();
        }
    }

    /**
     *
     * @Route("/{exerciseId}/distribution/{id}/delete", requirements={"id" = "\d+"})
     * @Method({"GET"})
     */
    public function deleteDistributionAction($exerciseId, $id)
    {
        $this->exerciseId = $exerciseId;
        $template = $this->get('template');
        $template->assign('exerciseId', $exerciseId);

        $result = $this->removeEntity($id);
        if ($result) {
            $url = $this->createUrl('list_link');
            $this->get('session')->getFlashBag()->add('success', "Deleted");

            return $this->redirect($url);
        }
    }





    protected function getExtraParameters()
    {
        return array('exerciseId');
    }

    protected function getControllerAlias()
    {
        return 'exercise_distribution.controller';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplatePath()
    {
        return 'admin/questionmanager/exercise_distribution/';
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
