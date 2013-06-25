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
use ChamiloLMS\Form\QuestionScoreNameType;

/**
 * Class QuestionScoreController
 * @todo @route and @method function don't work yet
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class QuestionScoreName extends BaseController
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
        $response = $template->render_template('admin/administrator/question_score_name/list.tpl');
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
        $template->assign('item', $item);
        $response = $template->render_template('admin/administrator/question_score_name/read.tpl');
        return new Response($response, 200, array());
    }

    public function editAction($id)
    {
        $repo = $this->getRepository();
        $request = $this->getRequest();

        $item = $repo->findOneById($id);

        if ($item) {
            $form = $this->get('form.factory')->create(new QuestionScoreNameType(), $item);

            if ($request->getMethod() == 'POST') {
                $form->bind($this->getRequest());

                if ($form->isValid()) {
                    $item = $form->getData();
                    parent::updateAction($item);
                    $this->get('session')->getFlashBag()->add('success', "Updated");
                    $url = $this->get('url_generator')->generate('admin_administrator_question_score_names');
                    return $this->redirect($url);
                }
            }


            $template = $this->get('template');
            $template->assign('item', $item);
            $template->assign('form', $form->createView());
            $template->assign('links', $this->generateLinks());
            $response = $template->render_template('admin/administrator/question_score_name/edit.tpl');
            return new Response($response, 200, array());
        } else {
            return $this->createNotFoundException();
        }
    }

    public function addAction()
    {
        $request = $this->getRequest();
        $form = $this->get('form.factory')->create(new QuestionScoreNameType());

        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $item = $form->getData();
                parent::createAction($item);
                $this->get('session')->getFlashBag()->add('success', "Added");
                $url = $this->get('url_generator')->generate('admin_administrator_question_score_names');
                return $this->redirect($url);
            }
        }

        $template = $this->get('template');
        $template->assign('links', $this->generateLinks());
        $template->assign('form', $form->createView());
        $response = $template->render_template('admin/administrator/question_score_name/add.tpl');
        return new Response($response, 200, array());
    }

    public function deleteAction($id)
    {
        $result = parent::deleteAction($id);
        if ($result) {
            $url = $this->get('url_generator')->generate('admin_administrator_question_score_names');
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
            'create_link' => 'admin_administrator_question_score_names_add',
            'read_link' => 'admin_administrator_question_score_names_read',
            'update_link' => 'admin_administrator_question_score_names_edit',
            'delete_link' => 'admin_administrator_question_score_names_delete',
            'list_link' => 'admin_administrator_question_score_names'
        );
    }

    /**
     * @see BaseController::getRepository()
     * @return EntityRepository
     */
    protected function getRepository()
    {
        return $this->get('orm.em')->getRepository('Entity\QuestionScoreName');
    }

    /**
     * @see BaseController::getNewEntity()
     * @return Object
     */
    protected function getNewEntity()
    {
        return new Entity\QuestionScoreName();
    }
}
