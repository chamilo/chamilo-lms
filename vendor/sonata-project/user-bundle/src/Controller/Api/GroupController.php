<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Controller\Api;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View as FOSRestView;
use FOS\UserBundle\Model\GroupInterface;
use JMS\Serializer\SerializationContext;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sonata\DatagridBundle\Pager\PagerInterface;
use Sonata\UserBundle\Model\GroupManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Hugo Briand <briand@ekino.com>
 */
class GroupController
{
    /**
     * @var GroupManagerInterface
     */
    protected $groupManager;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @param GroupManagerInterface $groupManager Sonata group manager
     * @param FormFactoryInterface  $formFactory  Symfony form factory
     */
    public function __construct(GroupManagerInterface $groupManager, FormFactoryInterface $formFactory)
    {
        $this->groupManager = $groupManager;
        $this->formFactory = $formFactory;
    }

    /**
     * Returns a paginated list of groups.
     *
     * @ApiDoc(
     *  resource=true,
     *  output={"class"="Sonata\DatagridBundle\Pager\PagerInterface", "groups"={"sonata_api_read"}}
     * )
     *
     * @QueryParam(name="page", requirements="\d+", default="1", description="Page for groups list pagination (1-indexed)")
     * @QueryParam(name="count", requirements="\d+", default="10", description="Number of groups by page")
     * @QueryParam(name="enabled", requirements="0|1", nullable=true, strict=true, description="Enabled/disabled groups only?")
     *
     * @View(serializerGroups={"sonata_api_read"}, serializerEnableMaxDepthChecks=true)
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return PagerInterface
     */
    public function getGroupsAction(ParamFetcherInterface $paramFetcher)
    {
        $orderByQueryParam = new QueryParam();
        $orderByQueryParam->name = 'orderBy';
        $orderByQueryParam->requirements = 'ASC|DESC';
        $orderByQueryParam->nullable = true;
        $orderByQueryParam->strict = true;
        $orderByQueryParam->description = 'Query groups order by clause (key is field, value is direction)';
        if (property_exists($orderByQueryParam, 'map')) {
            $orderByQueryParam->map = true;
        } else {
            $orderByQueryParam->array = true;
        }

        $paramFetcher->addParam($orderByQueryParam);

        $supportedFilters = [
            'enabled' => '',
        ];

        $page = $paramFetcher->get('page');
        $limit = $paramFetcher->get('count');
        $sort = $paramFetcher->get('orderBy');
        $criteria = array_intersect_key($paramFetcher->all(), $supportedFilters);

        foreach ($criteria as $key => $value) {
            if (null === $value) {
                unset($criteria[$key]);
            }
        }

        if (!$sort) {
            $sort = [];
        } elseif (!is_array($sort)) {
            $sort = [$sort, 'asc'];
        }

        return $this->groupManager->getPager($criteria, $page, $limit, $sort);
    }

    /**
     * Retrieves a specific group.
     *
     * @ApiDoc(
     *  requirements={
     *      {"name"="id", "dataType"="integer", "requirement"="\d+", "description"="group id"}
     *  },
     *  output={"class"="FOS\UserBundle\Model\GroupInterface", "groups"={"sonata_api_read"}},
     *  statusCodes={
     *      200="Returned when successful",
     *      404="Returned when group is not found"
     *  }
     * )
     *
     * @View(serializerGroups={"sonata_api_read"}, serializerEnableMaxDepthChecks=true)
     *
     * @param $id
     *
     * @return GroupInterface
     */
    public function getGroupAction($id)
    {
        return $this->getGroup($id);
    }

    /**
     * Adds a group.
     *
     * @ApiDoc(
     *  input={"class"="sonata_user_api_form_group", "name"="", "groups"={"sonata_api_write"}},
     *  output={"class"="Sonata\UserBundle\Model\Group", "groups"={"sonata_api_read"}},
     *  statusCodes={
     *      200="Returned when successful",
     *      400="Returned when an error has occurred while group creation",
     *  }
     * )
     *
     * @param Request $request A Symfony request
     *
     * @return GroupInterface
     *
     * @throws NotFoundHttpException
     */
    public function postGroupAction(Request $request)
    {
        return $this->handleWriteGroup($request);
    }

    /**
     * Updates a group.
     *
     * @ApiDoc(
     *  requirements={
     *      {"name"="id", "dataType"="integer", "requirement"="\d+", "description"="group identifier"}
     *  },
     *  input={"class"="sonata_user_api_form_group", "name"="", "groups"={"sonata_api_write"}},
     *  output={"class"="Sonata\UserBundle\Model\Group", "groups"={"sonata_api_read"}},
     *  statusCodes={
     *      200="Returned when successful",
     *      400="Returned when an error has occurred while group creation",
     *      404="Returned when unable to find group"
     *  }
     * )
     *
     * @param int     $id      Group identifier
     * @param Request $request A Symfony request
     *
     * @return GroupInterface
     *
     * @throws NotFoundHttpException
     */
    public function putGroupAction($id, Request $request)
    {
        return $this->handleWriteGroup($request, $id);
    }

    /**
     * Deletes a group.
     *
     * @ApiDoc(
     *  requirements={
     *      {"name"="id", "dataType"="integer", "requirement"="\d+", "description"="group identifier"}
     *  },
     *  statusCodes={
     *      200="Returned when group is successfully deleted",
     *      400="Returned when an error has occurred while group deletion",
     *      404="Returned when unable to find group"
     *  }
     * )
     *
     * @param int $id A Group identifier
     *
     * @return \FOS\RestBundle\View\View
     *
     * @throws NotFoundHttpException
     */
    public function deleteGroupAction($id)
    {
        $group = $this->getGroup($id);

        $this->groupManager->deleteGroup($group);

        return ['deleted' => true];
    }

    /**
     * Write a Group, this method is used by both POST and PUT action methods.
     *
     * @param Request  $request Symfony request
     * @param int|null $id      A Group identifier
     *
     * @return FormInterface
     */
    protected function handleWriteGroup($request, $id = null)
    {
        $groupClassName = $this->groupManager->getClass();
        $group = $id ? $this->getGroup($id) : new $groupClassName('');

        $form = $this->formFactory->createNamed(null, 'sonata_user_api_form_group', $group, [
            'csrf_protection' => false,
        ]);

        $form->submit($request);

        if ($form->isValid()) {
            $group = $form->getData();
            $this->groupManager->updateGroup($group);

            $view = FOSRestView::create($group);

            if (class_exists('FOS\RestBundle\Context\Context')) {
                $context = new Context();
                $context->setGroups(['sonata_api_read']);
                $view->setContext($context);
            } else {
                $serializationContext = SerializationContext::create();
                $serializationContext->setGroups(['sonata_api_read']);
                $serializationContext->enableMaxDepthChecks();
                $view->setSerializationContext($serializationContext);
            }

            return $view;
        }

        return $form;
    }

    /**
     * Retrieves group with id $id or throws an exception if it doesn't exist.
     *
     * @param $id
     *
     * @return GroupInterface
     *
     * @throws NotFoundHttpException
     */
    protected function getGroup($id)
    {
        $group = $this->groupManager->findGroupBy(['id' => $id]);

        if (null === $group) {
            throw new NotFoundHttpException(sprintf('Group (%d) not found', $id));
        }

        return $group;
    }
}
