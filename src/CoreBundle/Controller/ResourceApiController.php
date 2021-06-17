<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceComment;
use Chamilo\CoreBundle\Form\Type\ResourceCommentType;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\CoreBundle\Traits\CourseControllerTrait;
use Chamilo\CoreBundle\Traits\ResourceControllerTrait;
use Chamilo\CourseBundle\Controller\CourseControllerInterface;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ResourceApiController.
 * RouteResource("Resource").
 *
 * @deprecated experimental code
 * debug api routes with: bin/console debug:router | grep api
 */
class ResourceApiController extends AbstractController implements CourseControllerInterface
{
    use CourseControllerTrait;
    use ResourceControllerTrait;
    use ControllerTrait;

    /*public function getResourcesListAction($id, Request $request)
    {
        $repository = $this->getRepositoryFromRequest($request);
        $parentNode = $repository->getResourceNodeRepository()->find($id);

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::VIEW,
            $parentNode,
            'Unauthorised access to resource'
        );

        $course = $this->getCourse();
        $session = $this->getSession();

        $qb = $repository->getResources($this->getUser(), $parentNode, $course, $session, null);

        return $qb->getQuery()->getResult();
    }*/

    public function getResourceAction($id, Request $request)
    {
        $repository = $this->getRepositoryFromRequest($request);

        /** @var AbstractResource $resource */
        $resource = $repository->getResourceFromResourceNode($id);
        $this->denyAccessUnlessValidResource($resource);

        return $resource;
    }

    public function getResourceCommentsAction($id, Request $request, $paramFetcher)
    {
        $repository = $this->getRepositoryFromRequest($request);

        /** @var AbstractResource $resource */
        $resource = $repository->getResourceFromResourceNode($id);
        $this->denyAccessUnlessValidResource($resource);

        $orderBy = $paramFetcher->get('orderBy');
        $criteria = Criteria::create()->orderBy([
            $orderBy => Criteria::DESC,
        ]);

        return $resource->getResourceNode()->getComments()->matching($criteria);
    }

    public function postResourceCommentAction($id, Request $request): void
    {
        $repository = $this->getRepositoryFromRequest($request);

        /** @var AbstractResource $resource */
        $resource = $repository->getResourceFromResourceNode($id);
        $this->denyAccessUnlessValidResource($resource);

        $comment = new ResourceComment();
        $form = $this->createForm(ResourceCommentType::class, $comment, [
            'method' => 'POST',
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ResourceComment $comment */
            $comment = $form->getData();
            $comment->setAuthor($this->getUser());
            $resource->getResourceNode()->addComment($comment);
            $repository->getEntityManager()->persist($resource);
            $repository->getEntityManager()->flush();

            //return View::create($comment, Response::HTTP_CREATED);
        }
    }
}
