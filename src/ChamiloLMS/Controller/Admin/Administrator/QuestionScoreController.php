<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Admin\Administrator;

use ChamiloLMS\Controller\CrudController;
use Symfony\Component\HttpFoundation\Response;
use ChamiloLMS\Entity;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class QuestionScoreController
 * @package ChamiloLMS\Controller\Admin\Administrator
 * @author Julio Montoya <gugli100@gmail.com>
 */
class QuestionScoreController extends CrudController
{
    public function getClass()
    {
        return 'ChamiloLMS\Entity\BranchSync';
    }

    public function getType()
    {
        return 'ChamiloLMS\Form\QuestionScoreType';
    }

    /**
     * {@inheritdoc}
     */
    public function getControllerAlias()
    {
        return 'question_score.controller';
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplatePath()
    {
        return 'admin/administrator/question_score/';
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
        $item = $this->getEntity($id);
        $subItems = $item->getItems();

        $template->assign('item', $item);
        $template->assign('subitems', $subItems);
        $response = $template->render_template($this->getTemplatePath().'read.tpl');
        return new Response($response, 200, array());
    }

    protected function generateDefaultCrudRoutes()
    {
        $routes = parent::generateDefaultCrudRoutes();
        $routes['question_score_name_read_link'] = 'question_score_name.controller:readAction';
        return $routes ;
    }
}
