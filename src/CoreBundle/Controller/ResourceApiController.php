<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceComment;
use Chamilo\CoreBundle\Form\Type\ResourceCommentType;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\CoreBundle\Traits\CourseControllerTrait;
use Chamilo\CoreBundle\Traits\ResourceControllerTrait;
use Chamilo\CourseBundle\Controller\CourseControllerInterface;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ResourceApiController.
 * RouteResource("Resource")
 *
 * debug api routes with: bin/console debug:router | grep api
 */
class ResourceApiController extends AbstractFOSRestController implements CourseControllerInterface
{
    use CourseControllerTrait;
    use ResourceControllerTrait;
    use ControllerTrait;

    /**
     * @Rest\View(serializerGroups={"list"})
     */
    public function getResourcesListAction($id, Request $request)
    {
        $repository = $this->getRepositoryFromRequest($request);
        $parentNode = $repository->getResourceNodeRepository()->find($id);

        $course = $this->getCourse();
        $session = $this->getSession();

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::VIEW,
            $parentNode,
            'Unauthorised access to resource'
        );

        /** @var QueryBuilder $qb */
        $qb = $repository->getResources($this->getUser(), $parentNode, $course, $session, null);

        return $qb->getQuery()->getResult();
    }

    /**
     * @Rest\View(serializerGroups={"list"})
     */
    public function getResourceAction($id, Request $request)
    {
        $repository = $this->getRepositoryFromRequest($request);

        /** @var AbstractResource $resource */
        $resource = $repository->getResourceFromResourceNode($id);
        $this->denyAccessUnlessValidResource($resource);

        return $resource;
    }

    /**
     * @Rest\QueryParam(name="orderBy", nullable=true, description="Ordering")
     * @Rest\View(serializerGroups={"list"})
     */
    public function getResourceCommentsAction($id, Request $request, ParamFetcher $paramFetcher)
    {
        $repository = $this->getRepositoryFromRequest($request);

        /** @var AbstractResource $resource */
        $resource = $repository->getResourceFromResourceNode($id);
        $this->denyAccessUnlessValidResource($resource);

        //$paramFetcher->get('orderBy');

        return $resource->getResourceNode()->getComments();
    }

    /**
     * @Rest\View(serializerGroups={"list"})
     */
    public function postResourceCommentAction($id, Request $request)
    {
        $repository = $this->getRepositoryFromRequest($request);

        /** @var AbstractResource $resource */
        $resource = $repository->getResourceFromResourceNode($id);
        $this->denyAccessUnlessValidResource($resource);

        $comment = new ResourceComment();
        $form = $this->createForm(ResourceCommentType::class, $comment, ['method' => 'POST']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ResourceComment $comment */
            $comment = $form->getData();
            $comment->setAuthor($this->getUser());
            $resource->getResourceNode()->addComment($comment);
            $repository->getEntityManager()->persist($resource);
            $repository->getEntityManager()->flush();

            return View::create($comment, Response::HTTP_CREATED);
        }
    }
}
