<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin\Administrator;

use Chamilo\CoreBundle\Controller\CrudController;
use Symfony\Component\HttpFoundation\Response;
use Chamilo\CoreBundle\Entity;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class QuestionScoreController
 * @package Chamilo\CoreBundle\Controller\Admin\Administrator
 * @author Julio Montoya <gugli100@gmail.com>
 * @Route("/question_score")
 */
class QuestionScoreController
{
    /**
     *
     * @Route("/")
     * @Method({"GET"})
     */
    public function indexAction()
    {

    }
    public function getClass()
    {
        return 'Chamilo\CoreBundle\Entity\BranchSync';
    }

    public function getType()
    {
        return 'Chamilo\CoreBundle\Form\QuestionScoreType';
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
