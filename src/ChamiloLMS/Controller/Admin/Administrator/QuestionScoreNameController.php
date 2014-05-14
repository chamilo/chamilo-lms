<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Admin\Administrator;

use ChamiloLMS\Controller\CrudController;
use ChamiloLMS\Entity;

/**
 * Class QuestionScoreController
 * @todo @route and @method function don't work yet
 * @package ChamiloLMS\Controller\Admin\Administrator
 * @author Julio Montoya <gugli100@gmail.com>
 */

class QuestionScoreNameController extends CrudController
{
    public function getClass()
    {
        return 'ChamiloLMS\Entity\QuestionScoreName';
    }

    public function getType()
    {
        return 'ChamiloLMS\Form\QuestionScoreNameType';
    }

    public function getControllerAlias()
    {
        return 'question_score_name.controller';
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplatePath()
    {
        return 'admin/administrator/question_score_name/';
    }
}

