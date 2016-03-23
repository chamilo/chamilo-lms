<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Sonata\UserBundle\Controller\Api;

use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sonata\UserBundle\Model\GroupManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


/**
 * Class GroupController
 *
 * @package Sonata\UserBundle\Controller\Api
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class GroupController
{
    /**
     * @var GroupManagerInterface
     */
    protected $groupManager;

    /**
     * Constructor
     *
     * @param GroupManagerInterface $groupManager
     */
    public function __construct(GroupManagerInterface $groupManager)
    {
        $this->groupManager = $groupManager;
    }

    /**
     * Returns a paginated list of groups.
     *
     * @ApiDoc(
     *  resource=true,
     *  output={"class"="FOS\UserBundle\Model\GroupInterface", "groups"="sonata_api_read"}
     * )
     *
     * @QueryParam(name="page", requirements="\d+", default="1", description="Page for groups list pagination (1-indexed)")
     * @QueryParam(name="count", requirements="\d+", default="10", description="Number of groups by page")
     * @QueryParam(name="orderBy", array=true, requirements="ASC|DESC", nullable=true, strict=true, description="Query groups order by clause (key is field, value is direction")
     * @QueryParam(name="enabled", requirements="0|1", nullable=true, strict=true, description="Enabled/disabled groups only?")
     *
     * @View(serializerGroups="sonata_api_read", serializerEnableMaxDepthChecks=true)
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return GroupInterface[]
     */
    public function getGroupsAction(ParamFetcherInterface $paramFetcher)
    {
        $supportedFilters = array(
            'enabled' => "",
        );

        $page    = $paramFetcher->get('page') - 1;
        $count   = $paramFetcher->get('count');
        $orderBy = $paramFetcher->get('orderBy');
        $filters = array_intersect_key($paramFetcher->all(), $supportedFilters);

        foreach ($filters as $key => $value) {
            if (null === $value) {
                unset($filters[$key]);
            }
        }

        return $this->groupManager->findGroupsBy($filters, $orderBy, $count, $page);
    }

    /**
     * Retrieves a specific group
     *
     * @ApiDoc(
     *  requirements={
     *      {"name"="id", "dataType"="integer", "requirement"="\d+", "description"="group id"}
     *  },
     *  output={"class"="FOS\UserBundle\Model\GroupInterface", "groups"="sonata_api_read"},
     *  statusCodes={
     *      200="Returned when successful",
     *      404="Returned when group is not found"
     *  }
     * )
     *
     * @View(serializerGroups="sonata_api_read", serializerEnableMaxDepthChecks=true)
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
     * Retrieves group with id $id or throws an exception if it doesn't exist
     *
     * @param $id
     *
     * @return GroupInterface
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function getGroup($id)
    {
        $group = $this->groupManager->findGroupBy(array('id' => $id));

        if (null === $group) {
            throw new NotFoundHttpException(sprintf('Group (%d) not found', $id));
        }

        return $group;
    }

}