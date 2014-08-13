<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin\Administrator;

use Chamilo\CoreBundle\Controller\CrudController;
use Chamilo\CoreBundle\Entity;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class QuestionScoreController
 * @package Chamilo\CoreBundle\Controller\Admin\Administrator
 * @author Julio Montoya <gugli100@gmail.com>
 */

class QuestionScoreNameController
{
    public function getClass()
    {
        return 'Chamilo\CoreBundle\Entity\QuestionScoreName';
    }

    public function getType()
    {
        return 'Chamilo\CoreBundle\Form\QuestionScoreNameType';
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

