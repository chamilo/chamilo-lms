<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Controller\Tool\Curriculum;

use ChamiloLMS\CoreBundle\Controller\CrudController;
use Silex\Application;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use ChamiloLMS\CoreBundle\Entity\CurriculumCategory;
use ChamiloLMS\CoreBundle\Form\CurriculumCategoryType;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class CurriculumController
 * @package ChamiloLMS\CoreBundle\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class CurriculumCategoryController extends CrudController
{
    public function getClass()
    {
        return 'ChamiloLMS\CoreBundle\Entity\CurriculumCategory';
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplatePath()
    {
        return 'tool/curriculum/category/';
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'ChamiloLMS\CoreBundle\Form\CurriculumCategoryType';
    }

    public function getControllerAlias()
    {
        return 'curriculum_category.controller';
    }

    protected function getDefaultEntity()
    {
        $entity = $this->getNewEntity();
        $entity->setCourse($this->getCourse());
        $session = $this->getSession();
        if (!empty($session)) {
            $entity->setSession($session);
        }
        return $entity;
    }

    /**
     *
     * @Route("/")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        $breadcrumbs = array(
            array(
                'name' => get_lang('Curriculum'),
                'url' => array(
                    'route' => 'curriculum_user.controller:indexAction',
                    'routeParameters' => array(
                        'course' => $this->getCourse()->getCode()
                    )
                )
            ),
            array(
                'name' => get_lang('Categories')
            )
        );
        $this->setBreadcrumb($breadcrumbs);

        $options = array(
            'decorate' => true,
            'rootOpen' => '<ul>',
            'rootClose' => '</ul>',
            'childOpen' => '<li>',
            'childClose' => '</li>',
            'nodeDecorator' => function ($row) {
                $addChildren = null;
                $items = null;

                $editLabel = $this->get('translator')->trans('Edit');
                $deleteLabel = $this->get('translator')->trans('Delete');

                if ($row['lvl'] <= 0) {
                    $label = $this->get('translator')->trans('Add children');
                    $addChildren = '<a class="btn" href="'.$this->createUrl('add_from_parent_link', array('id' => $row['id'])).'">'.$label.'</a>';
                } else {
                    $label = $this->get('translator')->trans('Add items');

                    $addChildren = '<a class="btn" href="'.$this->createUrl('add_from_category', array('id' => $row['id'])).'">'.$label.'</a>';
                    $items = '<ul>';

                    foreach ($row['items'] as $item) {
                        $url = ' <a class="btn" href="'.$this->createUrl('edit_item', array('id' => $item['id'])).'">'.$editLabel.'</a>';
                        $items.= '<li>'.$item['title']." (item) ".$url.'</li>';
                    }
                    $items .= '</ul>';
                }
                $readLink = '<a href="'.$this->createUrl('read_link', array('id' => $row['id'])).'">'.$row['title'].'</a>';
                $editLink = '<a class="btn" href="'.$this->createUrl('update_link', array('id' => $row['id'])).'">'.$editLabel.'</a>';
                $deleteLink = '<a class="btn" href="'.$this->createUrl('delete_link', array('id' => $row['id'])).'"/>'.$deleteLabel.'</a>';

                return $readLink.' '.$addChildren.' '.$editLink.' '.$deleteLink.$items;
            }
            //'representationField' => 'slug',
            //'html' => true
        );

        // @todo put this in a function
        $repo = $this->getRepository();

        $qb = $this->getManager()
            ->createQueryBuilder()
            ->select('node, i')
            ->from('ChamiloLMS\CoreBundle\Entity\CurriculumCategory', 'node')
            ->leftJoin('node.items', 'i')
            ->innerJoin('node.course', 'c')
            ->orderBy('node.root, node.lft', 'ASC');

        $this->setCourseParameters($qb, 'node');

        $query = $qb->getQuery();

        $htmlTree = $repo->buildTree($query->getArrayResult(), $options);

        $this->get('template')->assign('tree', $htmlTree);
        $this->get('template')->assign('links', $this->generateLinks());
        $this->get('template')->assign('isAllowed', api_is_allowed_to_edit(true, true, true));

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
    public function addCategoryAction()
    {
        $breadcrumbs = array(
            array(
                'name' => get_lang('Curriculum'),
                'url' => array(
                    'route' => 'curriculum_user.controller:indexAction',
                    'routeParameters' => array(
                        'course' => $this->getCourse()->getCode()
                    )
                )
            ),
            array(
                'name' => get_lang('Categories'),
                'url' => array(
                    'route' => 'curriculum_category.controller:indexAction',
                    'routeParameters' => array(
                        'course' => $this->getCourse()->getCode()
                    )
                )

            ),
            array(
                'name' => get_lang('Add'),
                'url' => array(
                    'route' => 'curriculum_category.controller:addCategoryAction',
                    'routeParameters' => array(
                        'course' => $this->getCourse()->getCode()
                    )
                )
            )
        );
        $this->setBreadcrumb($breadcrumbs);
        return parent::addAction();
    }

    /**
    * @Route("/{id}/add")
    * @Method({"GET, POST"})
    */
    public function addFromParentAction($id)
    {
        $breadcrumbs = array(
            array(
                'name' => get_lang('Curriculum'),
                'url' => array(
                    'route' => 'curriculum_user.controller:indexAction',
                    'routeParameters' => array(
                        'course' => $this->getCourse()->getCode()
                    )
                )
            ),
            array(
                'name' => get_lang('Categories'),
                'url' => array(
                    'route' => 'curriculum_category.controller:indexAction',
                    'routeParameters' => array(
                        'course' => $this->getCourse()->getCode()
                    )
                )
            ),
            array(
                'name' => get_lang('AddItems')
            )
        );
        $this->setBreadcrumb($breadcrumbs);

        $request = $this->getRequest();
        $formType = $this->getFormType();

        $entity = new CurriculumCategory();
        $parentEntity = $this->getEntity($id);
        $entity->setParent($parentEntity);
        $entity->setCourse($this->getCourse());
        $session = $this->getSession();
        if (!empty($session)) {
            $entity->setSession($this->getSession());
        }

        $form = $this->get('form.factory')->create($formType, $entity);

        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                /** @var CurriculumCategory $item */
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
         $breadcrumbs = array(
            array(
                'name' => get_lang('Curriculum'),
                'url' => array(
                    'route' => 'curriculum_user.controller:indexAction',
                    'routeParameters' => array(
                        'course' => $this->getCourse()->getCode()
                    )
                )
            ),
            array(
                'name' => get_lang('Categories'),
                'url' => array(
                    'route' => 'curriculum_category.controller:indexAction',
                    'routeParameters' => array(
                        'course' => $this->getCourse()->getCode()
                    )
                )

            ),
            array(
                'name' => get_lang('Edit')
            )
        );

        $this->setBreadcrumb($breadcrumbs);
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

    /**
    *
    * @Route("/results")
    * @Method({"GET"})
    */
    public function resultsAction()
    {
        $breadcrumbs = array(
            array(
                'name' => get_lang('Curriculum'),
                'url' => array(
                    'route' => 'curriculum_user.controller:indexAction',
                    'routeParameters' => array(
                        'course' => $this->getCourse()->getCode()
                    )
                )
            ),
            array(
                'name' => get_lang('Categories'),
                'url' => array(
                    'route' => 'curriculum_category.controller:indexAction',
                    'routeParameters' => array(
                        'course' => $this->getCourse()->getCode()
                    )
                )

            ),
            array(
                'name' => get_lang('Results'),
            )
        );
        $this->setBreadcrumb($breadcrumbs);
        if (!api_is_allowed_to_edit(true, true, true)) {
            $this->abort('405');
        }

        $session = $this->getSession();
        $sessionId = 0;
        if ($session) {
            $sessionId =  $this->getSession()->getId();
        }

        // @todo move in a function
        $users = \CourseManager::get_user_list_from_course_code(
            $this->getCourse()->getCode(),
            $sessionId,
            null,
            null,
            STUDENT
        );

        $qb = $this->getManager()
            ->createQueryBuilder()
            ->select('node.id, u.userId, SUM(i.score) as score')
            ->from('ChamiloLMS\CoreBundle\Entity\CurriculumCategory', 'node')
            ->innerJoin('node.course', 'c')
            ->innerJoin('node.items', 'i')
            ->innerJoin('i.userItems', 'u')
            ->groupby('u.userId') ;
        $this->setCourseParameters($qb, 'node');
        $query = $qb->getQuery();
        $userResults = $query->getResult();

        $userResultsByUserId = array();
        if (!empty($userResults)) {
            foreach ($userResults as $item) {
                $userResultsByUserId[$item['userId']] = $item['score'];
            }
        }

        if (!empty($users)) {
            foreach ($users as &$user) {
                if (!empty($userResultsByUserId)) {
                    if (isset($userResultsByUserId[$user['user_id']])) {
                        $user['score'] = $userResultsByUserId[$user['user_id']];
                    } else {
                        $user['score'] = 0;
                    }
                }
            }
        }

        $template = $this->getTemplate();
        $template->assign('users', $users);

        $response = $template->render_template($this->getTemplatePath().'results.tpl');
        return new Response($response, 200, array());
    }


    protected function generateDefaultCrudRoutes()
    {
        $routes = parent::generateDefaultCrudRoutes();
        $routes['add_from_parent_link'] = 'curriculum_category.controller:addFromParentAction';
        $routes['add_from_category'] = 'curriculum_item.controller:addFromCategoryAction';
        $routes['edit_item'] = 'curriculum_item.controller:editAction';

        return $routes ;
    }
}
