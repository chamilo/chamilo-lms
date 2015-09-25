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

use Sonata\UserBundle\Model\UserInterface;

use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sonata\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


/**
 * Class UserController
 *
 * @package Sonata\UserBundle\Controller\Api
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class UserController
{
    /**
     * @var UserManagerInterface
     */
    protected $userManager;

    /**
     * Constructor
     *
     * @param UserManagerInterface $userManager
     */
    public function __construct(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * Returns a paginated list of users.
     *
     * @ApiDoc(
     *  resource=true,
     *  output={"class"="Sonata\UserBundle\Model\UserInterface", "groups"="sonata_api_read"}
     * )
     *
     * @QueryParam(name="page", requirements="\d+", default="1", description="Page for users list pagination (1-indexed)")
     * @QueryParam(name="count", requirements="\d+", default="10", description="Number of users by page")
     * @QueryParam(name="orderBy", array=true, requirements="ASC|DESC", nullable=true, strict=true, description="Query users order by clause (key is field, value is direction")
     * @QueryParam(name="enabled", requirements="0|1", nullable=true, strict=true, description="Enabled/disabled users only?")
     *
     * @View(serializerGroups="sonata_api_read", serializerEnableMaxDepthChecks=true)
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return UserInterface[]
     */
    public function getUsersAction(ParamFetcherInterface $paramFetcher)
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

        return $this->userManager->findUsersBy($filters, $orderBy, $count, $page);
    }

    /**
     * Retrieves a specific user
     *
     * @ApiDoc(
     *  requirements={
     *      {"name"="id", "dataType"="integer", "requirement"="\d+", "description"="user id"}
     *  },
     *  output={"class"="Sonata\UserBundle\Model\UserInterface", "groups"="sonata_api_read"},
     *  statusCodes={
     *      200="Returned when successful",
     *      404="Returned when user is not found"
     *  }
     * )
     *
     * @View(serializerGroups="sonata_api_read", serializerEnableMaxDepthChecks=true)
     *
     * @param $id
     *
     * @return UserInterface
     */
    public function getUserAction($id)
    {
        return $this->getUser($id);
    }

    /**
     * Retrieves user with id $id or throws an exception if it doesn't exist
     *
     * @param $id
     *
     * @return UserInterface
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function getUser($id)
    {
        $user = $this->userManager->findUserBy(array('id' => $id));

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('User (%d) not found', $id));
        }

        return $user;
    }

}