<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Admin\Administrator;

use ChamiloLMS\Controller\CommonController;
use Silex\Application;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use ChamiloLMS\Entity;
use ChamiloLMS\Form\QuestionScoreType;
use ChamiloLMS\Entity\QuestionScore;

/**
 * Class QuestionScoreController
 * @todo @route and @method function don't work yet
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class QuestionScoreController extends CommonController
{
    /**
     * @Route("/")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        return parent::listingAction();
    }

    /**
    *
    * @Route("/{id}", requirements={"id" = "\d+"}, defaults={"foo" = "bar"})
    * @Method({"GET"})
    */
    public function readAction($id)
    {
        // return parent::readAction($id);
        $template = $this->get('template');
        $template->assign('links', $this->generateLinks());
        $item = $this->getEntity($id);
        $subItems = $item->getItems();

        $template->assign('item', $item);
        $template->assign('subitems', $subItems);
        $response = $template->render_template($this->getTemplatePath().'read.tpl');
        return new Response($response, 200, array());
    }

    /**
    * @Route("/add")
    * @Method({"GET"})
    */
    public function addAction()
    {
        return parent::addAction();
    }

    /**
    *
    * @Route("/{id}/edit", requirements={"id" = "\d+"})
    * @Method({"GET"})
    */
    public function editAction($id)
    {
        return parent::editAction($id);
    }

    /**
    *
    * @Route("/{id}/delete", requirements={"id" = "\d+"})
    * @Method({"GET"})
    */
    public function deleteAction($id)
    {
        return parent::deleteAction($id);
    }

    protected function generateDefaultCrudRoutes()
    {
        $routes = parent::generateDefaultCrudRoutes();
        $routes['question_score_name_read_link'] = 'question_score_name.controller:readAction';
        return $routes ;
    }

    /**
    * {@inheritdoc}
    */
    protected function getControllerAlias()
    {
        return 'question_score.controller';
    }

    /**
    * {@inheritdoc}
    */
    protected function getRepository()
    {
        return $this->get('orm.em')->getRepository('ChamiloLMS\Entity\QuestionScore');
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewEntity()
    {
        return new QuestionScore();
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return new QuestionScoreType();
    }

    /**
    * {@inheritdoc}
    */
    protected function getTemplatePath()
    {
        return 'admin/administrator/question_score/';
    }
}
