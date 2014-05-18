<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Controller\Admin\Director;

use ChamiloLMS\CoreBundle\Controller\CrudController;
use ChamiloLMS\CoreBundle\Form\DirectorJuryUserType;
use ChamiloLMS\CoreBundle\Form\JuryType;
use ChamiloLMS\CoreBundle\Entity;
use ChamiloLMS\CoreBundle\Entity\BranchSync;
use ChamiloLMS\CoreBundle\Entity\Jury;
use ChamiloLMS\CoreBundle\Entity\User;
use ChamiloLMS\CoreBundle\Entity\JuryMembers;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class RoleController
 * @package ChamiloLMS\CoreBundle\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class BranchDirectorController extends CrudController
{
    public function getClass()
    {
        return 'ChamiloLMS\CoreBundle\Entity\BranchSync';
    }

    public function getType()
    {
        return 'ChamiloLMS\CoreBundle\Form\DirectorJuryUserType';
    }

    public function getControllerAlias()
    {
        return 'branch_director.controller';
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplatePath()
    {
        return 'admin/director/branches/';
    }

    /**
     * @Route("/")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        $userId = $this->getUser()->getUserId();

        $options = array(
            'decorate' => true,
            'rootOpen' => '<ul>',
            'rootClose' => '</ul>',
            'childOpen' => '<li>',
            'childClose' => '</li>',
            'nodeDecorator' => function ($row) {
                /**  @var BranchSync $branch */
                $branch = $this->getManager()->getRepository('ChamiloLMS\CoreBundle\Entity\BranchSync')->find($row['id']);
                $juries = $branch->getJuries();
                /** @var Jury $jury */
                $juryList = null;
                foreach ($juries as $jury) {
                    $juryId = $jury->getId();

                    $url = $this->generateUrl(
                        'branch_director.controller:listUsersAction',
                        array('juryId' => $juryId, 'branchId' => $row['id'])
                    );
                    $viewUsers = ' <a class="btn" href="'.$url.'">User list</a>';
                    $url = $this->generateUrl(
                        'branch_director.controller:addUsersAction',
                        array('juryId' => $juryId, 'branchId' => $row['id'])
                    );
                    $addUserLink = ' <a class="btn" href="'.$url.'">Add users</a>';

                    $juryList  .= $jury->getName() . ' '.$addUserLink.$viewUsers.'<br />';
                }
                return $row['branchName'].' <br />'.$juryList;
            }
        );

        // @todo add director filters
        $repo = $this->getRepository();

        $query = $this->getManager()
            ->createQueryBuilder()
            ->select('node')
            ->from('ChamiloLMS\CoreBundle\Entity\BranchSync', 'node')
            ->innerJoin('node.users', 'u')
            ->where('u.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('node.root, node.lft', 'ASC')
            ->getQuery();

        $htmlTree = $repo->buildTree($query->getArrayResult(), $options);

        if (empty($htmlTree)) {
            $this->get('session')->getFlashBag()->add(
                'warning',
                $this->get('translator')->trans("You don't have any branches.")
            );
        }

        $this->get('template')->assign('tree', $htmlTree);
        $this->get('template')->assign('links', $this->generateLinks());
        $response = $this->get('template')->render_template($this->getTemplatePath().'list.tpl');
        return new Response($response, 200, array());
    }

    /**
    *
    * @Route("/{id}", requirements={"id" = "\d+"})
    * @Method({"GET"})
    */
    public function readAction($id)
    {
        $template = $this->get('template');
        $request = $this->getRequest();

        $template->assign('links', $this->generateLinks());

        $item = $this->getEntity($id);
        $template->assign('item', $item);

        $form = $this->createForm(new JuryType());

        if ($request->isMethod('POST')) {
            $form->bind($this->getRequest());

            if ($form->isValid()) {


            }
        }

        $template->assign('form', $form->createView());
        $response = $template->render_template($this->getTemplatePath().'read.tpl');
        return new Response($response, 200, array());
    }

    /**
    *
    * @Route("branches/{branchId}/jury/{juryId}/add-user")
    * @Method({"GET"})
    */
    public function addUsersAction($juryId, $branchId)
    {
        $template = $this->get('template');
        $request = $this->getRequest();

        $type = new DirectorJuryUserType();
        $user = new User();
        $form = $this->createForm($type, $user);

        $form->handleRequest($request);
        if ($form->isValid()) {

            $jury = $this->getManager()->getRepository('ChamiloLMS\CoreBundle\Entity\Jury')->find($juryId);

            $factory = $this->get('security.encoder_factory');
            $encoder = $factory->getEncoder($user);
            $pass = $encoder->encodePassword($user->getPassword(), $user->getSalt());
            $user->setPassword($pass);
            $user->setStatus(STUDENT);
            $user->setChatcallUserId(0);
            $user->setChatcallDate(null);
            $user->setCurriculumItems(null);
            $user->setChatcallText(' ');
            $user->setActive(true);

            $em = $this->getManager();
            $em->persist($user);

            $user->getUserId();
            $role = current($user->getRoles());

            $juryMember = new JuryMembers();
            $juryMember->setJury($jury);
            $juryMember->setRole($role);
            $juryMember->setUser($user);

            $em->persist($juryMember);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', "User saved");
            //return $this->redirect($url);

        }

        $template->assign('form', $form->createView());
        $template->assign('juryId', $juryId);
        $template->assign('branchId', $branchId);
        $response = $template->render_template($this->getTemplatePath().'add_user.tpl');
        return new Response($response, 200, array());
    }

    /**
    *
    * @Route("branches/{branchId}/jury/{juryId}/list-user")
    * @Method({"GET"})
    */
    public function listUsersAction()
    {

    }
}
