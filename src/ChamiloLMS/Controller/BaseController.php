<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        // In order to use the Flint Controller
        $this->pimple = $app;
    }

    /**
     * This method should return the entity's repository.
     *
     * @abstract
     * @return EntityRepository
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
     *
     * @return Request
     */
    protected function getRequest()
    {
        return $this->get('request');
    }


    protected function createNotFoundException($message = 'Not Found', \Exception $previous = null)
    {
        return $this->app->abort(404, $message);
    }


    protected function getManager()
    {
        return $this->get('orm.em');
    }

    /**
     * Converts an array of URL to absolute URLs using the url_generator service
     * @param string $label
     * @param array
     * @return mixed
     */
    protected function createUrl($label, $params = array())
    {
        $links = $this->generateLinks();
        $courseCode = $this->getRequest()->get('courseCode');
        $params['courseCode'] = $courseCode;
        if (isset($links) && is_array($links) && isset($links[$label])) {
            $url = $this->generateUrl($links[$label], $params);
            return $url;
        }
        return $url = $this->generateUrl($links['list_link']);
    }


    // CRUD default actions

    /**
     * @Route("/")
     * @Method({"GET"})
     */
    public function listingAction()
    {
        $items = $this->listAction('array');
        $template = $this->get('template');
        $template->assign('items', $items);
        $template->assign('links', $this->generateLinks());
        $response = $template->render_template($this->getTemplatePath().'list.tpl');
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
        $form = $this->get('form.factory')->create($this->getFormType());

        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $item = $form->getData();
                $this->createAction($item);
                $this->get('session')->getFlashBag()->add('success', "Added");
                $url = $this->createUrl('list_link');
                return $this->redirect($url);
            }
        }

        $template = $this->get('template');
        $template->assign('links', $this->generateLinks());
        $template->assign('form', $form->createView());
        $response = $template->render_template($this->getTemplatePath().'add.tpl');
        return new Response($response, 200, array());
    }

    /**
     *
     * @Route("/{id}", requirements={"id" = "\d+"})
     * @Method({"GET"})
     */
    public function readAction($id)
    {
        $template = $this->get('template');
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
            $form = $this->get('form.factory')->create($this->getFormType(), $item);

            if ($request->getMethod() == 'POST') {
                $form->bind($this->getRequest());

                if ($form->isValid()) {
                    $data = $form->getData();
                    $this->updateAction($data);
                    $this->get('session')->getFlashBag()->add('success', "Updated");
                    $url = $this->createUrl('list_link');
                    return $this->redirect($url);
                }
            }

            $template = $this->get('template');
            $template->assign('item', $item);
            $template->assign('form', $form->createView());
            $template->assign('links', $this->generateLinks());
            $response = $template->render_template($this->getTemplatePath().'edit.tpl');
            return new Response($response, 200, array());
        } else {
            return $this->createNotFoundException();
        }
    }

    /**
     *
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
        $list = $this->getRepository()
            ->createQueryBuilder('e')
            ->getQuery()->getResult(Query::HYDRATE_ARRAY);

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
     *
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
                ->getQuery()->getSingleResult(Query::HYDRATE_ARRAY);
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
}
