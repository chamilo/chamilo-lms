<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Admin\Administrator;

use ChamiloLMS\Controller\BaseController;
use Silex\Application;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Entity;
use ChamiloLMS\Form\RoleType;

/**
 * Class RoleController
 * @todo @route and @method function don't work yet
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class RoleController extends BaseController
{
    /**
     *
     * @param Application $app
     * @Route("/")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        $token = $this->get('security')->getToken();

        if (null !== $token) {
            $user = $token->getUser();
        }

        if ($this->get('security')->isGranted('ROLE_ADMIN')) {
            //var_dump('granted');
        }

        $items = parent::listAction('array');
        $template = $this->get('template');
        $template->assign('items', $items);
        $response = $template->render_template('admin/administrator/role/list.tpl');
        return new Response($response, 200, array());
    }

    /**
     *
     * @Route("/{id}", requirements={"id" = "\d+"}, defaults={"foo" = "bar"})
     * @Method({"GET"})
     */
    public function readAction($id)
    {
        return parent::readAction($id);
    }

    public function editAction($id)
    {
        $roleRepo = $this->getRepository();
        $request = $this->getRequest();

        $role = $roleRepo->findOneById($id);

        if ($role) {
            $form = $this->get('form.factory')->create(new RoleType(), $role);

            if ($request->getMethod() == 'POST') {
                $form->bind($this->getRequest());

                if ($form->isValid()) {
                    $role = $form->getData();
                    parent::updateAction($role);
                    $this->get('session')->getFlashBag()->add('success', "Updated");
                    $url = $this->get('url_generator')->generate('admin_administrator_roles');
                    return $this->redirect($url);
                }
            }

            $template = $this->get('template');
            $template->assign('role', $role);
            $template->assign('form', $form->createView());
            $response = $template->render_template('admin/administrator/role/edit.tpl');
            return new Response($response, 200, array());
        } else {
            return $this->createNotFoundException();
        }
    }

    public function addAction()
    {
        $request = $this->getRequest();
        $form = $this->get('form.factory')->create(new RoleType());

        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $role = $form->getData();
                parent::createAction($role);
                $this->get('session')->getFlashBag()->add('success', "Added");

                // $params = array('id' => $role->getId());
                // $url = $this->get('url_generator')->generate('admin_administrator_roles_read', $params);
                $url = $this->get('url_generator')->generate('admin_administrator_roles');
                return $this->redirect($url);
            }
        }

        $template = $this->get('template');
        $template->assign('form', $form->createView());
        $response = $template->render_template('admin/administrator/role/add.tpl');
        return new Response($response, 200, array());
    }

    public function deleteAction($id)
    {
        $result = parent::deleteAction($id);
        if ($result) {
            $url = $this->get('url_generator')->generate('admin_administrator_roles');
            $this->get('session')->getFlashBag()->add('success', "Deleted");

            return $this->redirect($url);
        }
    }

    /**
     * @see BaseController::getRepository()
     * @return EntityRepository
     */
    protected function getRepository()
    {
        return $this->get('orm.em')->getRepository('Entity\Role');
    }

    /**
     * @see BaseController::getNewEntity()
     * @return Object
     */
    protected function getNewEntity()
    {
        return new Entity\Role();
    }
}
