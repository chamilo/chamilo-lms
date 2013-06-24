<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
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
     *
     * @return Request
     */
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

    public function getManager()
    {
        return $this->app['orm.em'];
    }


    /**
     * Base "list" action.
     * @param format
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
        $em = $this->getManager();
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
