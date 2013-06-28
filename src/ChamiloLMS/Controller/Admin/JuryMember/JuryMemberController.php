<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Admin\JuryMember;

use ChamiloLMS\Controller\CommonController;
use ChamiloLMS\Form\JuryType;
use Entity;
use Silex\Application;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class RoleController
 * @todo @route and @method function don't work yet
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class JuryMemberController extends CommonController
{
    /**
    * @Route("/")
    * @Method({"GET"})
    */
    public function indexAction()
    {

    }

    /**
    * @Route("/users")
    * @Method({"GET"})
    */
    public function listUsersAction()
    {

    }

    /**
    * @Route("/answers/{userId}")
    * @Method({"GET"})
    */
    public function answersPerUserAction($userId)
    {

    }

    /**
    * @Route("/save-score")
    * @Method({"POST"})
    */
    public function saveScoreAction()
    {

    }



    protected function getControllerAlias()
    {
        return 'jury_member.controller';
    }

    /**
    * {@inheritdoc}
    */
    protected function getTemplatePath()
    {
        return 'admin/jury_president/jury/';
    }

    /**
     * @return \Entity\Repository\BranchSyncRepository
     */
    protected function getRepository()
    {
        return $this->get('orm.em')->getRepository('Entity\Jury');
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewEntity()
    {
        return new Entity\Jury();
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return new JuryType();
    }
}
