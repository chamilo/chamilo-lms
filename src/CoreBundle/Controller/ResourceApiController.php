<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Block\BreadcrumbBlockService;
use Chamilo\CoreBundle\Component\Utils\Glide;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Repository\ResourceFactory;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\CoreBundle\Traits\CourseControllerTrait;
use Chamilo\CoreBundle\Traits\ResourceControllerTrait;
use Chamilo\CourseBundle\Controller\CourseControllerInterface;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ResourceApiController.
 *
 * @RouteResource("resources/{tool}/{type}/{id}")
 */
class ResourceApiController extends AbstractFOSRestController implements CourseControllerInterface
{
    use CourseControllerTrait;
    use ResourceControllerTrait;
    use ControllerTrait;

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['translator'] = TranslatorInterface::class;
        $services['breadcrumb'] = BreadcrumbBlockService::class;
        $services['resource_factory'] = ResourceFactory::class;
        $services['glide'] = Glide::class;

        return $services;
    }

    /**
     * Route("/{tool}/{type}/{id}/list", name="chamilo_core_api_resource_list").
     *
     * @Rest\View(serializerGroups={"list"})
     */
    public function getResourcesAction(Request $request)
    {
        $repository = $this->getRepositoryFromRequest($request);

        $resourceNodeId = $request->get('id');
        $parentNode = $repository->getResourceNodeRepository()->find($resourceNodeId);

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
     * Rest\Get("/{tool}/{type}/{id}").
     *
     * @Rest\View(serializerGroups={"list"})
     */
    public function getResourceAction(Request $request)
    {
        $repository = $this->getRepositoryFromRequest($request);
        $nodeId = $request->get('id');

        /** @var AbstractResource $resource */
        $resource = $repository->getResourceFromResourceNode($nodeId);
        $this->denyAccessUnlessValidResource($resource);

        return $resource;
    }

    /**
     * @Rest\View(serializerGroups={"list"})
     */
    public function getResourceCommentsAction(Request $request)
    {
        $repository = $this->getRepositoryFromRequest($request);
        $nodeId = $request->get('id');

        /** @var AbstractResource $resource */
        $resource = $repository->getResourceFromResourceNode($nodeId);
        $this->denyAccessUnlessValidResource($resource);

        return $resource->getResourceNode()->getComments();
    }
}
