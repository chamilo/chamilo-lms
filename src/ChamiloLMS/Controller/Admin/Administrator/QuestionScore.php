<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Admin\Administrator;

use ChamiloLMS\Controller\BaseController;
use Silex\Application;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Entity;
use ChamiloLMS\Form\QuestionScoreType;

/**
 * Class QuestionScoreController
 * @todo @route and @method function don't work yet
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class QuestionScore extends BaseController
{
    /**
     *
     * @param Application $app
     * @Route("/")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        $items = parent::listAction('array');
        $template = $this->get('template');
        $template->assign('items', $items);
        $template->assign('links', $this->generateLinks());
        $response = $template->render_template('admin/administrator/question_score/list.tpl');
        return new Response($response, 200, array());
    }

    /**
     *
     * @Route("/{id}", requirements={"id" = "\d+"}, defaults={"foo" = "bar"})
     * @Method({"GET"})
     */
    public function readAction($id)
    {
        $template = $this->get('template');
        $template->assign('links', $this->generateLinks());
        $item = parent::getEntity($id);
        $subItems = $item->getItems();

        $template->assign('item', $item);
        $template->assign('subitems', $subItems);
        $response = $template->render_template('admin/administrator/question_score/read.tpl');
        return new Response($response, 200, array());
    }

    public function editAction($id)
    {
        $roleRepo = $this->getRepository();
        $request = $this->getRequest();

        $role = $roleRepo->findOneById($id);

        if ($role) {
            $form = $this->get('form.factory')->create(new QuestionScoreType(), $role);

            if ($request->getMethod() == 'POST') {
                $form->bind($this->getRequest());

                if ($form->isValid()) {
                    $role = $form->getData();
                    parent::updateAction($role);
                    $this->get('session')->getFlashBag()->add('success', "Updated");
                    $url = $this->get('url_generator')->generate('admin_administrator_question_scores');
                    return $this->redirect($url);
                }
            }

            $template = $this->get('template');
            $template->assign('item', $role);
            $template->assign('form', $form->createView());
            $template->assign('links', $this->generateLinks());
            $response = $template->render_template('admin/administrator/question_score/edit.tpl');
            return new Response($response, 200, array());
        } else {
            return $this->createNotFoundException();
        }
    }

    public function addAction()
    {
        $request = $this->getRequest();
        $form = $this->get('form.factory')->create(new QuestionScoreType());

        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $role = $form->getData();
                parent::createAction($role);
                $this->get('session')->getFlashBag()->add('success', "Added");
                $url = $this->get('url_generator')->generate('admin_administrator_question_scores');
                return $this->redirect($url);
            }
        }

        $template = $this->get('template');
        $template->assign('links', $this->generateLinks());
        $template->assign('form', $form->createView());
        $response = $template->render_template('admin/administrator/question_score/add.tpl');
        return new Response($response, 200, array());
    }

    public function deleteAction($id)
    {
        $result = parent::deleteAction($id);
        if ($result) {
            $url = $this->get('url_generator')->generate('admin_administrator_question_scores');
            $this->get('session')->getFlashBag()->add('success', "Deleted");

            return $this->redirect($url);
        }
    }

    /**
     * Return an array with the string that are going to be generating by twig.
     * @return array
     */
    private function generateLinks()
    {
        return array(
            'create_link' => 'admin_administrator_question_score_add',
            'read_link' => 'admin_administrator_question_score_read',
            'update_link' => 'admin_administrator_question_score_edit',
            'delete_link' => 'admin_administrator_question_score_delete',
            'list_link' => 'admin_administrator_question_scores',
            'question_score_name_read_link' => 'admin_administrator_question_score_names_read'
        );
    }

    /**
     * @see BaseController::getRepository()
     * @return EntityRepository
     */
    protected function getRepository()
    {
        return $this->get('orm.em')->getRepository('Entity\QuestionScore');
    }

    /**
     * @see BaseController::getNewEntity()
     * @return Object
     */
    protected function getNewEntity()
    {
        return new Entity\QuestionScore();
    }
}
