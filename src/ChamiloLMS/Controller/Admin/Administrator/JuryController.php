<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Admin\Administrator;

use ChamiloLMS\Controller\CommonController;
use Silex\Application;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use Entity;
use ChamiloLMS\Form\JuryType;
use ChamiloLMS\Form\JuryUserType;
use ChamiloLMS\Form\JuryMembersType;
use Symfony\Component\HttpFoundation\JsonResponse;
use ChamiloLMS\Entity\JuryMembers;
use ChamiloLMS\Entity\Jury;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class JuryController
 * @todo @route and @method function don't work yet
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class JuryController extends CommonController
{
    /**
     *
     * @Route("/")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        return parent::listingAction();
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
    * @Route("/add")
    * @Method({"GET"})
    */
    public function addAction()
    {
        return parent::addAction();
    }

    /**
    *
    * @Route("/{id}/edit", requirements={"id" = "\d+"})
    * @Method({"GET"})
    */
    public function editAction($id)
    {
        return parent::editAction($id);
    }

    /**
    * @Route("/{id}/delete", requirements={"id" = "\d+"})
    * @Method({"GET"})
    */
    public function deleteAction($id)
    {
        return parent::deleteAction($id);
    }

     /**
    * @Route("/{id}/remove-member", requirements={"id" = "\d+"})
    * @Method({"GET"})
    */
    public function removeMemberAction($id)
    {
        $juryMembers = $this->getManager()->getRepository('ChamiloLMS\Entity\JuryMembers')->find($id);
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
        /** @var \ChamiloLMS\Entity\Repository\UserRepository $repo */
        $repo = $this->getManager()->getRepository('ChamiloLMS\Entity\User');

        if (empty($role)) {
            $entities = $repo->searchUserByKeyword($keyword);
        } else {
            $entities = $repo->searchUserByKeywordAndRole($keyword, $role);
        }

        $data = array();
        if ($entities) {
            /** @var \ChamiloLMS\Entity\User $entity */
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
                $user = $this->getManager()->getRepository('ChamiloLMS\Entity\User')->find($userId);
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

    protected function getControllerAlias()
    {
        return 'jury.controller';
    }

    /**
    * {@inheritdoc}
    */
    protected function getTemplatePath()
    {
        return 'admin/administrator/juries/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRepository()
    {
        return $this->get('orm.em')->getRepository('ChamiloLMS\Entity\Jury');
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewEntity()
    {
        return new Jury();
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return new JuryType();
    }
}
