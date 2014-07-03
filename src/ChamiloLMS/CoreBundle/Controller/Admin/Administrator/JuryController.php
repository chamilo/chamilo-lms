<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Controller\Admin\Administrator;

use ChamiloLMS\CoreBundle\Controller\CrudController;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use Entity;
use ChamiloLMS\CoreBundle\Form\JuryType;
use ChamiloLMS\CoreBundle\Form\JuryUserType;
use ChamiloLMS\CoreBundle\Form\JuryMembersType;
use Symfony\Component\HttpFoundation\JsonResponse;
use ChamiloLMS\CoreBundle\Entity\JuryMembers;
use ChamiloLMS\CoreBundle\Entity\Jury;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class JuryController
 * @package ChamiloLMS\CoreBundle\Controller\Admin\Administrator
 * @author Julio Montoya <gugli100@gmail.com>
 */
class JuryController
{
    public function getClass()
    {
        return 'ChamiloLMS\CoreBundle\Entity\Jury';
    }

    public function getControllerAlias()
    {
        return 'jury.controller';
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'ChamiloLMS\CoreBundle\Form\JuryType';
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplatePath()
    {
        return 'admin/administrator/juries/';
    }

    /**
    *
    * @Route("/{id}", requirements={"id" = "\d+"})
    * @Method({"GET"})
    */
    public function readAction($id)
    {
        $entity = $this->getEntity($id);

        $template = $this->get('template');
        $template->assign('item', $entity);
        $template->assign('links', $this->generateDefaultCrudRoutes());

        $response = $template->render_template($this->getTemplatePath().'read.tpl');
        return new Response($response, 200, array());
    }

     /**
    * @Route("/{id}/remove-member", requirements={"id" = "\d+"})
    * @Method({"GET"})
    */
    public function removeMemberAction($id)
    {
        $juryMembers = $this->getManager()->getRepository('ChamiloLMS\CoreBundle\Entity\JuryMembers')->find($id);
        if ($juryMembers) {
            $em = $this->getManager();
            $em->remove($juryMembers);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', "Deleted");

            $url = $this->get('url_generator')->generate('jury.controller:readAction', array('id' => $juryMembers->getJuryId()));
            return $this->redirect($url);
        }
    }

    /**
    * @Route("/search-user/" )
    * @Method({"GET"})
    */
    public function searchUserAction()
    {
        $request = $this->getRequest();
        $keyword = $request->get('tag');

        $role = $request->get('role');
        /** @var \ChamiloLMS\CoreBundle\Entity\Repository\UserRepository $repo */
        $repo = $this->getManager()->getRepository('Application\Sonata\UserBundle\Entity\User');

        if (empty($role)) {
            $entities = $repo->searchUserByKeyword($keyword);
        } else {
            $entities = $repo->searchUserByKeywordAndRole($keyword, $role);
        }

        $data = array();
        if ($entities) {
            /** @var \ChamiloLMS\UserBundle\Entity\User $entity */
            foreach ($entities as $entity) {
                $data[] = array(
                    'key' => (string) $entity->getUserId(),
                    'value' => $entity->getCompleteName(),
                );
            }
        }
        return new JsonResponse($data);
    }

    /**
    * @Route("/{id}/add-members", requirements={"id" = "\d+"})
    * @Method({"GET"})
    */
    public function addMembersAction(Application $app, $id)
    {
        $juryUserType = new JuryMembersType();
        $juryMember =  new JuryMembers();
        $juryMember->setJuryId($id);
        $form = $this->createForm($juryUserType, $juryMember);
        $request = $this->getRequest();
        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                /** @var JuryMembers $item */
                $item = $form->getData();

                $userIdList = $item->getUserId();
                $userId = ($userIdList[0]);
                $user = $this->getManager()->getRepository('ChamiloLMS\UserBundle\Entity\User')->find($userId);
                if (!$user) {
                    throw new \Exception('Unable to found User');
                }

                $jury = $this->getRepository()->find($id);

                if (!$jury) {
                    throw new \Exception('Unable to found Jury');
                }

                $juryMember->setUser($user);
                $juryMember->setJury($jury);

                $em = $this->getManager();
                $em->persist($juryMember);
                $em->flush();

                $this->get('session')->getFlashBag()->add('success', "Saved");
                $url = $this->get('url_generator')->generate('jury.controller:readAction', array('id' => $id));
                return $this->redirect($url);
            }
        }

        $template = $this->get('template');
        $template->assign('jury_id', $id);
        $template->assign('form', $form->createView());
        $response = $template->render_template($this->getTemplatePath().'add_members.tpl');
        return new Response($response, 200, array());
    }

    protected function generateDefaultCrudRoutes()
    {
        $routes = parent::generateDefaultCrudRoutes();
        $routes['add_members_link'] = 'jury.controller:addMembersAction';
        return $routes ;
    }


}
