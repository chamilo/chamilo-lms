<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Controller\Admin\Administrator;

use ChamiloLMS\CoreBundle\Controller\CrudController;
use ChamiloLMS\CoreBundle\Entity;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class QuestionScoreController
 * @package ChamiloLMS\CoreBundle\Controller\Admin\Administrator
 * @author Julio Montoya <gugli100@gmail.com>
 */

class QuestionScoreNameController
{
    public function getClass()
    {
        return 'ChamiloLMS\CoreBundle\Entity\QuestionScoreName';
    }

    public function getType()
    {
        return 'ChamiloLMS\CoreBundle\Form\QuestionScoreNameType';
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

