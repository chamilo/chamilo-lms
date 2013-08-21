<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Tool\Curriculum;

use ChamiloLMS\Controller\CommonController;
use Silex\Application;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use Entity;
use ChamiloLMS\Form\CurriculumCategoryType;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class CurriculumController
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class CurriculumCategoryController extends CommonController
{
    /**
     *
     * @Route("/")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        $course = $this->getCourse();

        $options = array(
            'decorate' => true,
            'rootOpen' => '<ul>',
            'rootClose' => '</ul>',
            'childOpen' => '<li>',
            'childClose' => '</li>',
            'nodeDecorator' => function ($row) use ($course) {
                $courseCode = $course->getCode();
                $addChildren = null;
                $items = null;
                if ($row['lvl'] <= 0) {
                    $addChildren = '<a class="btn" href="'.$this->createUrl('add_from_parent_link', array('course' => $courseCode, 'id' => $row['id'])).'">Add children</a>';
                } else {
                    $addChildren = '<a class="btn" href="'.$this->createUrl('add_from_category', array('course' => $courseCode, 'id' => $row['id'])).'">Add items</a>';
                    $items = '<ul>';
                    foreach ($row['items'] as $item) {
                        $url = ' <a class="btn" href="'.$this->createUrl('edit_item', array('course' => $courseCode, 'id' => $item['id'])).'">Edit</a>';
                        $items.= '<li>'.$item['title']." (item) ".$url.'</li>';
                    }
                    $items .= '</ul>';
                }
                $readLink = '<a href="'.$this->createUrl('read_link', array('course' => $courseCode, 'id' => $row['id'])).'">'.$row['title'].'</a>';
                $editLink = '<a class="btn" href="'.$this->createUrl('update_link', array('course' => $courseCode, 'id' => $row['id'])).'">Edit</a>';
                $deleteLink = '<a class="btn" href="'.$this->createUrl('delete_link', array('course' => $courseCode, 'id' => $row['id'])).'"/>Delete</a>';

                return $readLink.' '.$addChildren.' '.$editLink.' '.$deleteLink.$items;
            }
            //'representationField' => 'slug',
            //'html' => true
        );

        // @todo put this in a function
        $repo = $this->getRepository();

        $query = $this->getManager()
            ->createQueryBuilder()
            ->select('node, i')
            ->from('Entity\CurriculumCategory', 'node')
            ->leftJoin('node.items', 'i')
            ->innerJoin('node.course', 'c')
            ->where('node.cId = :id')
            ->orderBy('node.root, node.lft', 'ASC')
            ->setParameters(array('id' => $course->getId()))
            ->getQuery();

        $htmlTree = $repo->buildTree($query->getArrayResult(), $options);

        $this->get('template')->assign('tree', $htmlTree);
        $this->get('template')->assign('links', $this->generateLinks());
        $response = $this->get('template')->render_template($this->getTemplatePath().'list.tpl');

        return new Response($response, 200, array());
    }

    /**
    *
    * @Route("/{id}/show", requirements={"id" = "\d+"})
    * @Method({"GET"})
    */
    public function readCategoryAction($id)
    {
        return parent::readAction($id);
    }

    /**
    * @Route("/add")
    * @Method({"GET"})
    */
    public function addCategoryAction($course)
    {
        return parent::addAction();
    }

    /**
    * @Route("/{id}/add")
    * @Method({"GET, POST"})
    */
    public function addFromParentAction($id)
    {
        $request = $this->getRequest();
        $formType = $this->getFormType();

        $entity = new Entity\CurriculumCategory();
        $parentEntity = $this->getEntity($id);
        $entity->setParent($parentEntity);
        $form = $this->get('form.factory')->create($formType, $entity);

        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                /** @var Entity\CurriculumCategory $item */
                $item = $form->getData();
                $em = $this->getManager();
                $em->persist($item);
                $em->flush();

                $this->get('session')->getFlashBag()->add('success', "Added");
                $url = $this->createUrl('list_link');
                return $this->redirect($url);
            }
        }

        $template = $this->get('template');
        $template->assign('links', $this->generateLinks());
        $template->assign('form', $form->createView());
        $template->assign('parent_id', $id);
        $response = $template->render_template($this->getTemplatePath().'add_from_parent.tpl');

        return new Response($response, 200, array());
    }

    /**
    *
    * @Route("/{id}/edit", requirements={"id" = "\d+"})
    * @Method({"GET"})
    */
    public function editCategoryAction($id)
    {
        return parent::editAction($id);
    }

    /**
    *
    * @Route("/{id}/delete", requirements={"id" = "\d+"})
    * @Method({"GET"})
    */
    public function deleteCategoryAction($id)
    {
        return parent::deleteAction($id);
    }

    protected function getControllerAlias()
    {
        return 'curriculum_category.controller';
    }

    protected function generateDefaultCrudRoutes()
    {
        $routes = parent::generateDefaultCrudRoutes();
        $routes['add_from_parent_link'] = 'curriculum_category.controller:addFromParentAction';
        $routes['add_from_category'] = 'curriculum_item.controller:addFromCategoryAction';
        $routes['edit_item'] = 'curriculum_item.controller:editAction';

        return $routes ;
    }

    /**
    * {@inheritdoc}
    */
    protected function getTemplatePath()
    {
        return 'tool/curriculum/category/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRepository()
    {
        return $this->get('orm.em')->getRepository('Entity\CurriculumCategory');
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewEntity()
    {
        return new Entity\CurriculumCategory();
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return new CurriculumCategoryType();
    }

    protected function getDefaultEntity()
    {
        $entity = $this->getNewEntity();
        $entity->setCourse($this->getCourse());
        $entity->setSessionId(api_get_session_id());
        return $entity;
    }
}
