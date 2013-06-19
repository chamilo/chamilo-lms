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
        $role = $roleRepo->findOneById($id);
        if ($role) {
            $form = $this->get('form.factory')->create(new RoleType(), $role);

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
        $form = $this->get('form.factory')->create(new RoleType());

        $form->bind($this->getRequest());

        if ($form->isValid()) {
            $em = $this->get('orm.em');
            $role = $form->getData();
            $em->persist($role);
            $em->flush();

            $params = array('id' => $role->getId());
            $url = $this->get('url_generator')->generate('admin_administrator_roles_read', $params);
            return $this->redirect($url);
        }

        $template = $this->get('template');
        $template->assign('form', $form->createView());
        $response = $template->render_template('admin/administrator/role/add.tpl');
        return new Response($response, 200, array());
    }

    /**
     * @Route("", name="api_events_create")
     * @Method({"POST"})
     */
    public function createAction()
    {
        return parent::createAction();
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
