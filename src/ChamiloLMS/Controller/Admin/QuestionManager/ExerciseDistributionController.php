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
use Doctrine\Common\Collections\ArrayCollection;

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
        $template = $this->get('template');
        $em = $this->getManager();
        $course = $this->getCourse();

        if (empty($course)) {
            throw new \Exception('Could not get a valid course.');
        }

        $criteria = array('exerciseId' => $exerciseId);
        $items = $this->getRepository()->findBy($criteria);

        $distributionRelSessions = $em->getRepository('Entity\CQuizDistributionRelSession')->findBy($criteria);

        $selectedExerciseDistributionIdList = array();
        if ($distributionRelSessions) {
            foreach ($distributionRelSessions as $distributionRelSession) {
                $selectedExerciseDistributionIdList[] = $distributionRelSession->getQuizDistributionId();
            }
        }

        $template->assign('selected_distribution_id_list', $selectedExerciseDistributionIdList);
        $template->assign('exerciseId', $exerciseId);
        $template->assign('items', $items);
        $template->assign('exerciseUrl', api_get_path(WEB_CODE_PATH).'exercice/exercice.php?'.api_get_cidreq());

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
    public function toggleVisibilityAction($exerciseId, $id)
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
    * @Route("/{exerciseId}/distribution/{id}/toggle_activation")
    * @Method({"GET"})
    */
    public function toggleActivationAction($exerciseId, $id)
    {
        $em = $this->getManager();
        $distribution = $this->getRepository()->find($id);
        if (!$distribution) {
            return $this->createNotFoundException();
        }
        $criteria = array('exerciseId' => $exerciseId, 'quizDistributionId' => $id);
        $distributionRelSession = $em->getRepository('Entity\CQuizDistributionRelSession')->findOneBy($criteria);

        if ($distributionRelSession) {
            $em->remove($distributionRelSession);
            $em->flush();
            $this->get('session')->getFlashBag()->add('warning', "Distribution removed");
            $url = $this->createUrl('list_link');
            return $this->redirect($url);

        } else {
            $sessionId = $this->getSessionId();

            $distributionRelSession = new Entity\CQuizDistributionRelSession();
            $distributionRelSession->setCId(api_get_course_int_id());
            $distributionRelSession->setSessionId($sessionId);
            $distributionRelSession->setDistribution($distribution);
            $distributionRelSession->setExerciseId($exerciseId);
            $em->persist($distributionRelSession);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', "Distribution applied");
            $url = $this->createUrl('list_link');
            return $this->redirect($url);
        }
    }

    /**
     * @Route("/{exerciseId}/distribution/stats")
     * @Method({"GET"})
     */
    public function showStatsAction($exerciseId)
    {
        $template = $this->get('template');
        $template->assign('exerciseId', $exerciseId);
        $items = $this
            ->getManager()
            ->getRepository('Entity\TrackExercise')
            ->getAverageScorePerForm($exerciseId, api_get_course_int_id(), api_get_session_id());

        $template->assign('items', $items);
        $response = $template->render_template($this->getTemplatePath().'stats.tpl');
        return new Response($response, 200, array());
    }

    /**
     * @Route("/{exerciseId}/distribution/add-many")
     * @Method({"GET"})
     */
    public function addManyDistributionAction($exerciseId)
    {
        $builder = $this->createFormBuilder();
        $builder->add('number_of_distributions', 'text');
        $builder->add('submit', 'submit');
        $form = $builder->getForm();

        $template = $this->get('template');
        $this->exerciseId = $exerciseId;
        $template->assign('exerciseId', $exerciseId);
        $request = $this->getRequest();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            if ($data['number_of_distributions'] > 0) {
                for ($i = 0; $i < $data['number_of_distributions']; $i++) {
                    /** @var Entity\CQuizDistribution $distribution */
                    $distribution = new \Entity\CQuizDistribution();
                    $distribution->setAuthorUserId($this->getUser()->getUserId());
                    $distribution->setExerciseId($exerciseId);
                    $counter = $i + 1;
                    $distribution->setTitle($counter);
                    $distribution->setActive(true);
                    $distribution->setAuthorUserId($this->getUser()->getUserId());
                    $this->getManager()->getRepository('Entity\CQuizDistribution')->addDistribution($distribution, $this->getCourse());
                }
            }

            $this->get('session')->getFlashBag()->add('success', "Added");
            $url = $this->createUrl('list_link');
            return $this->redirect($url);
        }

        $template->assign('links', $this->generateLinks());
        $template->assign('form', $form->createView());

        $response = $template->render_template($this->getTemplatePath().'add_many.tpl');
        return new Response($response, 200, array());
    }

    /**
     * @Route("/{exerciseId}/distribution/add")
     * @Method({"GET"})
     */
    public function addDistributionAction($exerciseId)
    {
        $template = $this->get('template');
        $this->exerciseId = $exerciseId;
        $template->assign('exerciseId', $exerciseId);
        $request = $this->getRequest();

        $distribution = $this->getDefaultEntity();
        $form = $this->createForm($this->getFormType(), $distribution);

        $form->handleRequest($request);

        if ($form->isValid()) {

            /** @var Entity\CQuizDistribution $distribution */
            $distribution = $form->getData();
            $distribution->setAuthorUserId($this->getUser()->getUserId());

            $this->getManager()->getRepository('Entity\CQuizDistribution')->addDistribution($distribution, $this->getCourse());

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
        /*$repo = $this->getRepository();
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
        }*/
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
