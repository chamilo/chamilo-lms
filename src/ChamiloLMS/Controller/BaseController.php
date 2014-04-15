<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\NoResultException;
use Silex\Application;
use Flint\Controller\Controller as FlintController;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Each entity controller must extends this class.
 *
 * @abstract
 */
abstract class BaseController extends FlintController
{
    protected $app;
    protected $pimple;

    /**
     * This method should return the entity's repository.
     *
     * @abstract
     * @return \Doctrine\ORM\EntityRepository
     */
    abstract protected function getRepository();

    /**
     * This method should return a new entity instance to be used for the "create" action.
     *
     * @abstract
     * @return Object
     */
    abstract protected function getNewEntity();

    /**
     * Returns a new Form Type
     * @return AbstractType
     */
    abstract protected function getFormType();

    /**
     * Returns the template path
     * */
    abstract protected function getTemplatePath();

    /**
     * Returns the controller alias
     * @example for QuestionScoreController: question_score_controller
     */
    abstract protected function getControllerAlias();

    /**
     * Array with links
     * @return array
     */
    abstract protected function generateLinks();

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        // In order to use the Flint Controller.
        $this->pimple = $app;
    }

    /**
     * @return \ChamiloLMS\Entity\Course
     */
    protected function getCourse()
    {
        //if (isset($this->app['course'])) {
            return $this->app['course'];
        //}
        return false;
    }

    /**
     * @return \ChamiloLMS\Entity\Session
     */
    protected function getSession()
    {
        if (isset($this->app['course_session']) && !empty($this->app['course_session'])) {
            return $this->app['course_session'];
        }
        return false;
    }

    /**
     * @return \Template
     */
    protected function getTemplate()
    {
        return $this->get('template');
    }

    /**
     * @return \ChamiloLMS\Component\Editor\Editor
     */
    protected function getHtmlEditor()
    {
        return $this->get('html_editor');
    }

    /**
     * @return \ChamiloLMS\Component\Editor\Connector
     */
    protected function getEditorConnector()
    {
        return $this->get('editor_connector');
    }

    /**
     * @return \ChamiloLMS\Component\DataFilesystem\DataFilesystem
     */
    protected function getDataFileSystem()
    {
        return $this->get('chamilo.filesystem');
    }

    /**
     * @return \ChamiloLMS\Entity\User
     */
    public function getUser()
    {
        $user = parent::getUser();
        if (empty($user)) {
            return $this->abort(404, 'Login required.');
        }
        return $user;
    }

    /**
     * @return \Symfony\Component\Security\Core\SecurityContext
     */
    public function getSecurity()
    {
        return $this->get('security');
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getManager()
    {
        return $this->get('orm.em');
    }

    public function sendFile($file, $status = 200, $headers = array(), $contentDisposition = null)
    {
        return $this->pimple->sendFile($file, $status, $headers, $contentDisposition);
    }

    /**
     * Converts an array of URL to absolute URLs using the url_generator service
     * @param string $label
     * @param array
     * @return mixed
     */
    protected function createUrl($label, $parameters = array())
    {
        $links = $this->generateLinks();
        $course = $this->getCourse();

        if (!empty($course)) {
            $parameters['course'] = $course->getCode();
        }
        $session = $this->getSession();
        if (!empty($session)) {
            $parameters['id_session'] = $session->getId();
        }

        $extraParams = $this->getExtraParameters();

        if (!empty($extraParams)) {
            $request = $this->getRequest();
            $dynamicParams = array();
            foreach ($extraParams as $param) {
                $value = $request->get($param);
                if (!empty($value)) {
                    $dynamicParams[$param] = $value;
                }
            }
            $parameters = array_merge($parameters, $dynamicParams);
        }

        if (isset($links) && is_array($links) && isset($links[$label])) {
            $url = $this->generateUrl($links[$label], $parameters);
            return $url;
        }
        return $url = $this->generateUrl($links['list_link']);
    }

    /**
     * Add extra parameters when generating URLs
     * @return array
     */
    protected function getExtraParameters()
    {
        return array();
    }

    /**
     * @see Symfony\Component\Routing\RouterInterface::generate()
     */
    public function generateUrl($name, array $parameters = array(), $reference = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        $course = $this->getCourse();
        if (!empty($course)) {
            $parameters['course'] = $course->getCode();
        }
        $session = $this->getSession();
        if (!empty($session)) {
            $parameters['id_session'] = $session->getId();
        }

        return parent::generateUrl($name, $parameters, $reference);
    }

    // CRUD default actions

    /**
     * @Route("/")
     * @Method({"GET"})
     */
    public function listingAction()
    {
        $items = $this->listAction('array');
        $template = $this->getTemplate();
        $template->assign('items', $items);
        $template->assign('links', $this->generateLinks());
        $response = $template->renderTemplate($this->getTemplatePath().'list.tpl');
        return new Response($response, 200, array());
    }

    /**
     *
     * @Route("/add")
     * @Method({"GET"})
     */
    public function addAction()
    {
        $request = $this->getRequest();
        $form = $this->createForm($this->getFormType(), $this->getDefaultEntity());

        $form->handleRequest($request);

        if ($form->isValid()) {
            $item = $form->getData();
            $this->createAction($item);
            $this->get('session')->getFlashBag()->add('success', "Added");
            $url = $this->createUrl('list_link');
            return $this->redirect($url);
        }

        $template = $this->getTemplate();
        $template->assign('links', $this->generateLinks());
        $template->assign('form', $form->createView());
        $response = $template->renderTemplate($this->getTemplatePath().'add.tpl');
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
     *
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
                $this->get('session')->getFlashBag()->add('success', "Updated");
                $url = $this->createUrl('list_link');
                return $this->redirect($url);
            }

            $template = $this->getTemplate();
            $template->assign('item', $item);
            $template->assign('form', $form->createView());
            $template->assign('links', $this->generateLinks());
            $response = $template->renderTemplate($this->getTemplatePath().'edit.tpl');
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
            $url = $this->createUrl('list_link');
            $this->get('session')->getFlashBag()->add('success', "Deleted");

            return $this->redirect($url);
        }
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
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param string
     */
    protected function setCourseParameters(\Doctrine\ORM\QueryBuilder & $qb, $prefix)
    {
        $course = $this->getCourse();
        if ($course) {
            $qb->andWhere($prefix.'.cId = :id');
            $qb->setParameter('id', $course->getId());

            $session = $this->getSession();
            if (!empty($session)) {
                $qb->andWhere($prefix.'.sessionId = :session_id');
                $qb->setParameter('session_id', $session->getId());
            }
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
     * @param $object
     * @return JsonResponse|NotFoundHttpException
     */
    protected function createAction($object)
    {
        if (false === $object) {
            throw new \Exception('Unable to create the entity');
        }

        $em = $this->getManager();
        $em->persist($object);
        $em->flush();

        return new JsonResponse($this->getEntityForJson($object->getId()));
    }

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

        return new JsonResponse($this->getEntityForJson($object->getId()));
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

    /**
     * Returns an entity from its ID as an associative array, or FALSE in case of error.
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

    /**
     * @return null
     */
    protected function getDefaultEntity()
    {
        return null;
    }
}
