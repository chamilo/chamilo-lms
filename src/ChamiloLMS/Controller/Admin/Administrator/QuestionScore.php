<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Admin\Administrator;

use ChamiloLMS\Controller\CommonController;
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
class QuestionScore extends CommonController
{
    public function indexAction()
    {
        return parent::indexAction();
    }

    public function readAction($id)
    {
        return parent::readAction($id);
    }

    public function addAction()
    {
        return parent::addAction();
    }

    public function editAction($id)
    {
        return parent::editAction($id);
    }

    public function deleteAction($id)
    {
        return parent::deleteAction($id);
    }

    /**
     * Return an array with the string that are going to be generating by twig.
     * @return array
     */
    protected function generateLinks()
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
     * {@inheritdoc}
     */
    protected function getRepository()
    {
        return $this->get('orm.em')->getRepository('Entity\QuestionScore');
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewEntity()
    {
        return new Entity\QuestionScore();
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
