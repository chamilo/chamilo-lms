<?php

/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Query;
use Doctrine\ORM\NoResultException;
use Silex\Application;

/**
 * Each entity controller must extends this class.
 *
 * @abstract
 */
abstract class BaseController
{
    protected $app;

    /**
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    public function getRequest()
    {
        return $this->get('request');
    }

    public function redirect($redirect)
    {
        return $this->app->redirect($redirect);
    }


    public function createNotFoundException($message = 'Not Found', \Exception $previous = null)
    {
        return $this->app->abort(404, $message);
    }

    /**
     * @param string $item
     * @return mixed
     */
    public function get($item)
    {
        return $this->app[$item];
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
     * Base "list" action.
     *
     * @return JsonResponse
     */
    protected function listAction($format = 'json')
    {
        return $this->getList($format);
    }

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
     * @return JsonResponse|NotFoundHttpException
     */
    protected function readAction($id)
    {
        $entityInstance = $this->getEntityForJson($id);
        if (false === $entityInstance) {
            return $this->createNotFoundException();
        }

        return new JsonResponse($entityInstance);
    }

    /**
     * Base "create" action.
     *
     * @return JsonResponse|NotFoundHttpException
     */
    protected function createAction()
    {
        $json = $this->getDataFromRequest();


        $object = $this->updateEntity($this->getNewEntity(), $json);

        if (false === $object) {
            throw new \Exception('Unable to create the entity');
        }
        $em = $this->getDoctrine()->getManager();
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
        $json = $this->getDataFromRequest();

        if (false === $json) {
            throw new \Exception('Invalid JSON');
        }

        $object = $this->updateEntity($this->getNewEntity(), $json);

        if (false === $object) {
            throw new \Exception('Unable to create the entity');
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($object);
        $em->flush();

        return new JsonResponse($this->getEntityForJson($object->getId()));
    }

    /**
     * Base "upload" action.
     *
     * @return JsonResponse|NotFoundHttpException
     */
    protected function updateAction($id)
    {
        $object = $this->getEntity($id);
        if (false === $object) {
            return $this->createNotFoundException();
        }
        $json = $this->getDataFromRequest();
        if (false === $json) {
            throw new \Exception('Invalid JSON');
        }
        if (false === $this->updateEntity($object, $json)) {
            throw new \Exception('Unable to update the entity');
        }
        $this->getDoctrine()->getManager()->flush($object);

        return new JsonResponse($this->getEntityForJson($object->getId()));
    }

    /**
     * Base "upload" action.
     *
     * @return JsonResponse|NotFoundHttpException
     */
    protected function updateJsonAction($id)
    {
        $object = $this->getEntity($id);
        if (false === $object) {
            return $this->createNotFoundException();
        }
        $json = $this->getDataFromRequest();
        if (false === $json) {
            throw new \Exception('Invalid JSON');
        }
        if (false === $this->updateEntity($object, $json)) {
            throw new \Exception('Unable to update the entity');
        }
        $this->getDoctrine()->getManager()->flush($object);

        return new JsonResponse($this->getEntityForJson($object->getId()));
    }

    /**
     * Base "delete" action.
     *
     * @return JsonResponse|NotFoundHttpException
     */
    protected function deleteAction($id)
    {
        $object = $this->getEntity($id);
        if (false === $object) {
            return $this->createNotFoundException();
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($object);
        $em->flush();

        return new JsonResponse(array());
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
    protected function getDataFromRequest()
    {
        $data = $this->get("request")->getContent();
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
     * @param string $json
     * @return Object|boolean
     */
    protected function updateEntity($entity, $json)
    {
        $data = json_decode($json);
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
