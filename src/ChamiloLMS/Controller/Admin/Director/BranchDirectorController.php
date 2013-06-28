<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Admin\Director;

use ChamiloLMS\Controller\CommonController;
use ChamiloLMS\Form\BranchType;
use Entity;
use Silex\Application;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RoleController
 * @todo @route and @method function don't work yet
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class BranchDirectorController extends CommonController
{
    /**
     * @Route("/")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        $token = $this->get('security')->getToken();

        if (null !== $token) {
            $user = $token->getUser();
        }

        $userId = $user->getUserId();

        $options = array(
            'decorate' => true,
            'rootOpen' => '<ul>',
            'rootClose' => '</ul>',
            'childOpen' => '<li>',
            'childClose' => '</li>',
            'nodeDecorator' => function($row) {
                //$addChildren = '<a class="btn" href="'.$this->createUrl('add_from_parent_link', array('id' => $row['id'])).'">Add children</a>';
                $readLink = '<a href="'.$this->createUrl('read_link', array('id' => $row['id'])).'">'.$row['branchName'].'</a>';
                return $readLink;
            }
            //'representationField' => 'slug',
            //'html' => true
        );

        // @todo put this in a function
        $repo = $this->getRepository();

        $query = $this->getManager()
            ->createQueryBuilder()
            ->select('node')
            ->from('Entity\BranchSync', 'node')
            //->where('node.cId = 0')
            ->orderBy('node.root, node.lft', 'ASC')
            ->getQuery();

        $htmlTree = $repo->buildTree($query->getArrayResult(), $options);
        $this->get('template')->assign('tree', $htmlTree);
        $this->get('template')->assign('links', $this->generateLinks());
        $response = $this->get('template')->render_template($this->getTemplatePath().'list.tpl');
        return new Response($response, 200, array());
    }

    /**
    *
    * @Route("/{id}", requirements={"id" = "\d+"}, defaults={"foo" = "bar"})
    * @Method({"GET"})
    */
    public function readAction($id)
    {
        $template = $this->get('template');
        $template->assign('links', $this->generateLinks());
        $repo = $this->getRepository();
        $item = $this->getEntity($id);
        $children = $repo->children($item);
        $template->assign('item', $item);
        $template->assign('subitems', $children);
        $response = $template->render_template($this->getTemplatePath().'read.tpl');
        return new Response($response, 200, array());
    }

    protected function getControllerAlias()
    {
        return 'branch_director.controller';
    }

    /**
    * {@inheritdoc}
    */
    protected function getTemplatePath()
    {
        return 'admin/director/branches/';
    }

    /**
     * @return \Entity\Repository\BranchSyncRepository
     */
    protected function getRepository()
    {
        return $this->get('orm.em')->getRepository('Entity\BranchSync');
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewEntity()
    {
        return new Entity\BranchSync();
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return new BranchType();
    }
}
