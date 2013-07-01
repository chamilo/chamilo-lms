<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Admin\JuryPresident;

use ChamiloLMS\Controller\CommonController;
use ChamiloLMS\Form\JuryType;
use ChamiloLMS\Form\JuryUserType;
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
class JuryPresidentController extends CommonController
{
    /**
    * @Route("/")
    * @Method({"GET"})
    */
    public function indexAction()
    {
        $template = $this->get('template');
        $response = $template->render_template($this->getTemplatePath().'index.tpl');
        return new Response($response, 200, array());
    }

    /**
    * @Route("/open-jury")
    * @Method({"GET, POST"})
    */
    public function openJuryAction()
    {
        $this->get('session')->getFlashBag()->add('success', "Comité abierto");
        $url = $this->get('url_generator')->generate('jury_president.controller:indexAction');
        return $this->redirect($url);
    }

    /**
    * @Route("/close-jury")
    * @Method({"GET, POST"})
    */
    public function closeJuryAction()
    {
        $this->get('session')->getFlashBag()->add('success', "Comité cerrado");
        $url = $this->get('url_generator')->generate('jury_president.controller:indexAction');
        return $this->redirect($url);
    }

    /**
    * @Route("/close-score")
    * @Method({"GET, POST"})
    */
    public function closeScoreAction()
    {
        $this->get('session')->getFlashBag()->add('success', "Notas cerradas");
        $url = $this->get('url_generator')->generate('jury_president.controller:indexAction');
        return $this->redirect($url);
    }

    /**
    * @Route("/assign-members")
    * @Method({"GET"})
    */
    public function assignMembersAction()
    {
        $token = $this->get('security')->getToken();

        if (null !== $token) {
            $user = $token->getUser();
            $userId = $user->getUserId();
        }

        $juryUserType = new JuryUserType();
        $form = $this->get('form.factory')->create($juryUserType);

        $jury = $this->getRepository()->getJuryByPresidentId($userId);

        $user1 = new Entity\User(); $user1->setFirstname('111');
        $user2 = new Entity\User(); $user2->setFirstname('222');
        $user3 = new Entity\User(); $user3->setFirstname('333');

        $m1 = new Entity\User(); $m1->setFirstname('m 11');
        $m2 = new Entity\User(); $m2->setFirstname('m 22');
        $m3 = new Entity\User(); $m3->setFirstname('m 33');

        $users = array(
            $user1,
            $user2,
            $user3,
        );

        $members = array(
            $m1,
            $m2,
            $m3
        );

        $template = $this->get('template');
        $template->assign('members', $members);
        $template->assign('students', $users);
        $template->assign('form', $form->createView());
        $response = $template->render_template($this->getTemplatePath().'assign_members.tpl');
        return new Response($response, 200, array());
    }

    /**
    * @Route("/check-answers")
    * @Method({"GET"})
    */
    public function checkAnswersAction()
    {
        $template = $this->get('template');
        $response = $template->render_template($this->getTemplatePath().'check_answers.tpl');
        return new Response($response, 200, array());
    }

    protected function getControllerAlias()
    {
        return 'jury_president.controller';
    }

    /**
    * {@inheritdoc}
    */
    protected function getTemplatePath()
    {
        return 'admin/jury_president/';
    }

    /**
     * @return \Entity\Repository\JuryRepository
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
