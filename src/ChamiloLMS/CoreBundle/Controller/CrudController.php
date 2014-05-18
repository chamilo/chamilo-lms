<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Controller;

use Doctrine\ORM\Query;
use Doctrine\ORM\NoResultException;
use MyProject\Proxies\__CG__\OtherProject\Proxies\__CG__\stdClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\View\TwitterBootstrap3View;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class CrudController
 * @package ChamiloLMS\CoreBundle\Controller
 */
abstract class CrudController extends BaseController implements CrudControllerInterface
{
    public $maxPerPage = 2;
    //public $crudController = true;

    /**
     * Returns the entity's repository.
     *
     * @abstract
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->get('orm.em')->getRepository($this->getClass());
    }

    /**
     * Returns a new entity instance to be used for the "create/edit" actions.
     *
     * @return stdClass
     */
    public function getNewEntity()
    {
        $class = $this->getClass();

        return new $class;
    }

    /**
     * Returns a new Form Type
     * @return \Symfony\Component\Form\AbstractType
     */
    public function getFormType()
    {
        $class = $this->getType();

        return new $class;
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
                        //'course' => $this->getCourse()->getCode()
                    )
                )
            ),
            array(
                'name' => get_lang('Categories')
            )
        );
        //$this->setBreadcrumb(array());

        return $this->listingAction();
    }

    /**
     * @Route("/")
     * @Method({"GET"})
     */
    public function listingAction()
    {
        $template = $this->getTemplate();

        // Getting query
        $qb = $this->getRepository()->createQueryBuilder('e');
        $query = $qb->getQuery();

        // Process query in a pagination mode.
        $adapter = new DoctrineORMAdapter($query);
        $pager = new Pagerfanta($adapter);

        $page = $this->getRequest()->get('page');

        if (empty($page)) {
            $page = 1;
        }

        $routeGenerator = function ($page) {
            return $this->generateControllerUrl('listingAction', array('page' => $page));
        };

        $pager->setMaxPerPage($this->maxPerPage);
        $pager->setCurrentPage($page);

        $view = new TwitterBootstrap3View();
        $pagination = $view->render($pager, $routeGenerator, array(
            'proximity' => 3,
        ));

        $template->assign('items', $pager);
        $template->assign('grid_pagination', $pagination);
        $template->assign('links', $this->generateLinks());
        $response = $this->renderTemplate('list.tpl');

        return new Response($response, 200, array());
    }

    /**
     * {@inheritdoc}
     */
    protected function generateLinks()
    {
        return $this->generateDefaultCrudRoutes();
    }

    protected function generateDefaultCrudRoutes()
    {
        $className = $this->getControllerAlias();

        return
            array(
            'create_link' => $className.':addAction',
            'read_link' => $className.':readAction',
            'update_link' => $className.':editAction',
            'delete_link' => $className.':deleteAction',
            'list_link' => $className.':indexAction'
        );
    }

    public function getLink($action)
    {

    }

    /**
     * @Route("/add")
     * @Method({"GET"})
     */
    public function addAction()
    {
        $request = $this->getRequest();
        $form = $this->createForm($this->getFormType(), $this->getNewEntity());

        $form->handleRequest($request);

        if ($form->isValid()) {
            $item = $form->getData();
            $this->createAction($item);
            $this->addMessage('Added', 'success');
            $url = $this->generateControllerUrl('listingAction');

            return $this->redirect($url);
        }

        $template = $this->getTemplate();
        $template->assign('links', $this->generateLinks());
        $template->assign('form', $form->createView());
        $response = $this->renderTemplate('add.tpl');

        return new Response($response, 200, array());
    }

    /**
     *
     * @Route("/{id}", requirements={"id" = "\d+"})
     * @Method({"GET"})
     */
    public function readAction($id)
    {
        $template = $this->getTemplate();
        $template->assign('links', $this->generateLinks());
        return $this->readEntity($id);
    }

    /**
     * @Route("/{id}/edit", requirements={"id" = "\d+"})
     * @Method({"GET"})
     */
    public function editAction($id)
    {
        $repo = $this->getRepository();
        $request = $this->getRequest();
        $item = $repo->findOneById($id);

        if ($item) {
            $form = $this->createForm($this->getFormType(), $item);

            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $this->updateAction($data);
                $this->addMessage('Updated', 'success');
                $url = $this->generateControllerUrl('listingAction');

                return $this->redirect($url);
            }

            $template = $this->getTemplate();
            $template->assign('item', $item);
            $template->assign('form', $form->createView());
            $template->assign('links', $this->generateLinks());
            $response = $this->renderTemplate('edit.tpl');

            return new Response($response, 200, array());
        } else {
            return $this->createNotFoundException();
        }
    }

    /**
     * @Route("/{id}/delete", requirements={"id" = "\d+"})
     * @Method({"GET"})
     */
    public function deleteAction($id)
    {
        $result = $this->removeEntity($id);
        if ($result) {
            $url = $this->generateControllerUrl('listingAction');
            $this->addMessage('Deleted', 'success');

            return $this->redirect($url);
        }
    }

    /**
    * @Route("/export/{format}")
    * @Method({"GET"})
    */
    public function exportAction($format = 'csv')
    {
        $qb = $this->getRepository()->createQueryBuilder('e');
        $query = $qb->getQuery();

        $source = new \Exporter\Source\DoctrineORMQuerySourceIterator($query, array('id'));

        // Prepare the writer
        $writer = new \Exporter\Writer\CsvWriter('data2.csv');

        $filename = sprintf('export_%s_%s.%s',
            strtolower(substr($this->getClassNameLabel(), strripos($this->getClassNameLabel(), '\\') + 1)),
            date('Y_m_d_H_i_s', strtotime('now')),
            $format
        );


        return $this->get('exporter')->getResponse(
            $format,
            $filename,
            $this->getDataSourceIterator()
        );
    }

    public function getDataSourceIterator()
    {

    }

    /**
     * Base "read" action.
     *
     * @param int $id
     * @return JsonResponse|NotFoundHttpException
     */
    protected function readEntity($id)
    {
        $entityInstance = $this->getEntityForJson($id);
        if (false === $entityInstance) {
            return $this->createNotFoundException();
        }

        return new JsonResponse($entityInstance);
    }

    /**
     * Base "delete" action.
     * @param int id
     * @return JsonResponse|NotFoundHttpException
     */
    protected function removeEntity($id)
    {
        $object = $this->getEntity($id);
        if (false === $object) {
            return $this->createNotFoundException();
        }
        $em = $this->getManager();
        $em->remove($object);
        $em->flush();

        return new JsonResponse(array());
    }

    /**
     * Base "list" action.
     * @param string format
     * @return JsonResponse
     */
    protected function listAction($format = 'json')
    {
        return $this->getList($format);
    }


    /**
     * @param string $format
     * @return JsonResponse
     */
    protected function getList($format = 'json')
    {
        $qb = $this->getRepository()->createQueryBuilder('e');
        $list = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);

        switch ($format) {
            case 'json':
                return new JsonResponse($list);
                break;
            default:
                return $list;
                break;
        }
    }

    /**
     * Base "read" action.
     *
     * @param int $id
     * @param string format
     *
     * @return JsonResponse|NotFoundHttpException
     */
    protected function readActionByFormat($id, $format = 'array')
    {
        $entityInstance = $this->getEntityForJson($id);
        if (false === $entityInstance) {
            return $this->createNotFoundException();
        }
        switch($format) {
            case 'json':
                return new JsonResponse($entityInstance);
            case 'array':
                return $entityInstance;
        }

        return $entityInstance;
    }

    /**
    * Base "create" action.
    * @param stdClass $object
    *
    * @throws \Exception
    */
    protected function createAction($object)
    {
        if (false === $object) {
            throw new \Exception('Unable to create the entity');
        }

        $em = $this->getManager();
        $em->persist($object);
        $em->flush();
    }

    /**
     * Base "upload" action.
     * @param int id
     * @return JsonResponse|NotFoundHttpException
     */
    protected function updateAction($object)
    {
        if (false === $object) {
            return $this->createNotFoundException();
        }
        $this->getManager()->flush($object);
    }

    /**
     * Returns an entity from its ID, or FALSE in case of error.
     *
     * @param int $id
     * @return Object|boolean
     */
    protected function getEntity($id)
    {
        try {
            return $this->getRepository()->find($id);
        } catch (NoResultException $ex) {
            return false;
        }

        return false;
    }

    // json actions

    /**
     * Base "create" action.
     *
     * @return JsonResponse|NotFoundHttpException
     */
    protected function createJsonAction()
    {
        $json = $this->getJsonDataFromRequest();

        if (false === $json) {
            throw new \Exception('Invalid JSON');
        }

        $object = $this->updateEntity($this->getNewEntity(), $json);

        if (false === $object) {
            throw new \Exception('Unable to create the entity');
        }
        $em = $this->getManager();
        $em->persist($object);
        $em->flush();

        return new JsonResponse($this->getEntityForJson($object->getId()));
    }

    /**
     * Returns an entity from its ID as an associative array, false in error.
     *
     * @param int $id
     * @return array|boolean
     */
    protected function getEntityForJson($id)
    {
        try {
            return $this->getRepository()->createQueryBuilder('e')
                ->where('e.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->getSingleResult(Query::HYDRATE_ARRAY);
        } catch (NoResultException $ex) {
            return false;
        }

        return false;
    }

    /**
     * Returns the request's JSON content, or FALSE in case of error.
     *
     * @return string|boolean
     */
    protected function getJsonDataFromRequest()
    {
        $data = $this->getRequest()->getContent();
        if (!$data) {
            return false;
        }

        return $data;
    }

    /**
     * Base "upload" action.
     * @param int id
     * @return JsonResponse|NotFoundHttpException
     */
    protected function updateJsonAction($id, $data)
    {
        $object = $this->getEntity($id);
        if (false === $object) {
            return $this->createNotFoundException();
        }

        $json = $this->getJsonDataFromRequest();

        if (false === $json) {
            throw new \Exception('Invalid JSON');
        }
        if (false === $this->updateEntity($object, $json)) {
            throw new \Exception('Unable to update the entity');
        }
        $this->getManager()->flush($object);

        return new JsonResponse($this->getEntityForJson($object->getId()));
    }

    /**
     * Updates an entity with data from a JSON string.
     * Returns the entity, or FALSE in case of error.
     *
     * @param Object $entity
     * @param string $data
     * @return Object|boolean
     */
    protected function updateEntity($entity, $data)
    {
        $data = json_decode($data, true);

        if ($data == null) {
            return false;
        }

        foreach ($data as $name => $value) {
            if ($name != 'id') {
                $setter = 'set'.ucfirst($name);
                if (method_exists($entity, $setter)) {
                    call_user_func_array(array($entity, $setter), array($value));
                }
            }
        }

        return $entity;
    }
}
