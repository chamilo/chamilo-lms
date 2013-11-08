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
use Pagerfanta\Adapter\DoctrineORMAdapter;

use Pagerfanta\View\TwitterBootstrapView;
use Pagerfanta\Pagerfanta;

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
        // @todo use something better
        if (!api_is_allowed_to_edit(true, true, true)) {
             $this->abort('405');
        }

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

                if ($row['lvl'] <= 1) {
                    $label = $this->get('translator')->trans('Add children');
                    $addChildren = '<a class="btn" href="'.$this->createUrl('add_from_parent_link', array('id' => $row['id'])).'">'.$label.'</a>';
                } else {
                    $label = $this->get('translator')->trans('Add items');

                    $addChildren = '<a class="btn" href="'.$this->createUrl('add_from_category', array('id' => $row['id'])).'">'.$label.'</a>';
                    $items = '<ul>';

                    foreach ($row['items'] as $item) {
                        $url = ' <a class="btn" href="'.$this->createUrl('edit_item', array('id' => $item['id'])).'">'.$editLabel.'</a>';
                        $items.= '<li>'.$item['title']." (Score: {$item['score']}) ".$url.'</li>';
                    }
                    $items .= '</ul>';
                }
                $readLink = '<a href="'.$this->createUrl('read_link', array('id' => $row['id'])).'">'.$row['title'].'</a> (Max score: '.$row['maxScore'].')';
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
            ->from('Entity\CurriculumCategory', 'node')
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
        // @todo use something better
        if (!api_is_allowed_to_edit(true, true, true)) {
            $this->abort('405');
        }
        return parent::readAction($id);
    }

    /**
    * @Route("/add")
    * @Method({"GET"})
    */
    public function addCategoryAction()
    {
        // @todo use something better
        if (!api_is_allowed_to_edit(true, true, true)) {
            $this->abort('405');
        }

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
        // @todo use something better
        if (!api_is_allowed_to_edit(true, true, true)) {
            $this->abort('405');
        }

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

        $entity = new Entity\CurriculumCategory();
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
        // @todo use something better
        if (!api_is_allowed_to_edit(true, true, true)) {
            $this->abort('405');
        }

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
        // @todo use something better
        if (!api_is_allowed_to_edit(true, true, true)) {
            $this->abort('405');
        }

        return parent::deleteAction($id);
    }

    /**
    *
    * @Route("/results")
    * @Method({"GET"})
    */
    public function resultsAction()
    {
        // @todo use something better
        if (!api_is_allowed_to_edit(true, true, true)) {
            $this->abort('405');
        }

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

        $session = $this->getSession();
        $sessionId = 0;
        if ($session) {
            $sessionId =  $this->getSession()->getId();
        }

        /*$qb = $this->getManager()
            ->createQueryBuilder()
            ->select('u, u.userId, u.userId, i, SUM(i.score) as score, node')
            ->from('Entity\CurriculumCategory', 'node')
            ->innerJoin('node.course', 'c')
            ->innerJoin('node.items', 'i')
            ->innerJoin('i.userItems', 'u')
            ->groupby('u.userId') ;*/
        //, SUM(i.score) as score

        $qb = $this->getManager()
            ->createQueryBuilder()
            ->select('u')
            ->from('Entity\User', 'u')
            ->innerJoin('u.curriculumItems', 'ci')
            ->innerJoin('ci.item', 'i')
            ->innerJoin('i.category', 'c')
            ->where('c.cId = :courseId AND (c.sessionId = :sessionId or c.sessionId IS NULL) ')
            ->setParameters(
                array(
                    'courseId' => $this->getCourse()->getId(),
                    'sessionId' => $sessionId
                )
            )
            ->groupby('u.userId') ;

        $maxPerPage = 1;
        $page = intval($this->getRequest()->get('page'));
        $page = empty($page) ? 1 : $page;

        $adapter = new DoctrineORMAdapter($qb);
        $pagination = new Pagerfanta($adapter);
        $pagination->setMaxPerPage($maxPerPage); // 10 by default
        $pagination->setCurrentPage($page);

        $this->app['pagerfanta.view.router.name']   = 'curriculum_category.controller:resultsAction';
        $this->app['pagerfanta.view.router.params'] = array(
            'course'   => $this->getCourse()->getCode(),
            'page'   => $page
        );

        $this->app['template']->assign('pagination', $pagination);
        $template = $this->getTemplate();
        $response = $template->render_template($this->getTemplatePath().'results.tpl');
        return new Response($response, 200, array());
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
        $session = $this->getSession();
        if (!empty($session)) {
            $entity->setSession($session);
        }
        return $entity;
    }
}
