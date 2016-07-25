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

use JMS\Serializer\SerializationContext;
use Sonata\UserBundle\Model\UserInterface;

use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sonata\UserBundle\Model\GroupManagerInterface;
use Sonata\UserBundle\Model\UserManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\View\View as FOSRestView;

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
     * @var GroupManagerInterface
     */
    protected $groupManager;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * Constructor
     *
     * @param UserManagerInterface  $userManager
     * @param GroupManagerInterface $groupManager
     * @param FormFactoryInterface  $formFactory
     */
    public function __construct(UserManagerInterface $userManager, GroupManagerInterface $groupManager, FormFactoryInterface $formFactory)
    {
        $this->userManager  = $userManager;
        $this->groupManager = $groupManager;
        $this->formFactory  = $formFactory;
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
     * Adds an user
     *
     * @ApiDoc(
     *  input={"class"="sonata_user_api_form_user", "name"="", "groups"={"sonata_api_write"}},
     *  output={"class"="Sonata\UserBundle\Model\User", "groups"={"sonata_api_read"}},
     *  statusCodes={
     *      200="Returned when successful",
     *      400="Returned when an error has occurred while user creation",
     *  }
     * )
     *
     * @param Request $request A Symfony request
     *
     * @return UserInterface
     *
     * @throws NotFoundHttpException
     */
    public function postUserAction(Request $request)
    {
        return $this->handleWriteUser($request);
    }

    /**
     * Updates an user
     *
     * @ApiDoc(
     *  requirements={
     *      {"name"="id", "dataType"="integer", "requirement"="\d+", "description"="user identifier"}
     *  },
     *  input={"class"="sonata_user_api_form_user", "name"="", "groups"={"sonata_api_write"}},
     *  output={"class"="Sonata\UserBundle\Model\User", "groups"={"sonata_api_read"}},
     *  statusCodes={
     *      200="Returned when successful",
     *      400="Returned when an error has occurred while user creation",
     *      404="Returned when unable to find user"
     *  }
     * )
     *
     * @param int     $id      User id
     * @param Request $request A Symfony request
     *
     * @return UserInterface
     *
     * @throws NotFoundHttpException
     */
    public function putUserAction($id, Request $request)
    {
        return $this->handleWriteUser($request, $id);
    }

    /**
     * Deletes an user
     *
     * @ApiDoc(
     *  requirements={
     *      {"name"="id", "dataType"="integer", "requirement"="\d+", "description"="user identifier"}
     *  },
     *  statusCodes={
     *      200="Returned when user is successfully deleted",
     *      400="Returned when an error has occurred while user deletion",
     *      404="Returned when unable to find user"
     *  }
     * )
     *
     * @param integer $id An User identifier
     *
     * @return \FOS\RestBundle\View\View
     *
     * @throws NotFoundHttpException
     */
    public function deleteUserAction($id)
    {
        $user = $this->getUser($id);

        $this->userManager->deleteUser($user);

        return array('deleted' => true);
    }

    /**
     * Attach a group to a user
     *
     * @ApiDoc(
     *  requirements={
     *      {"name"="userId", "dataType"="integer", "requirement"="\d+", "description"="user identifier"},
     *      {"name"="groupId", "dataType"="integer", "requirement"="\d+", "description"="group identifier"}
     *  },
     *  output={"class"="Sonata\UserBundle\Model\User", "groups"={"sonata_api_read"}},
     *  statusCodes={
     *      200="Returned when successful",
     *      400="Returned when an error has occurred while user/group attachment",
     *      404="Returned when unable to find user or group"
     *  }
     * )
     *
     * @param integer $userId  A User identifier
     * @param integer $groupId A Group identifier
     *
     * @return UserInterface
     *
     * @throws NotFoundHttpException
     * @throws \RuntimeException
     */
    public function postUserGroupAction($userId, $groupId)
    {
        $user = $this->getUser($userId);
        $group = $this->getGroup($groupId);

        if ($user->hasGroup($group)) {
            return FOSRestView::create(array(
                'error' => sprintf('User "%s" already has group "%s"', $userId, $groupId)
            ), 400);
        }

        $user->addGroup($group);
        $this->userManager->updateUser($user);

        return array('added' => true);
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

    /**
     * Retrieves user with id $id or throws an exception if it doesn't exist
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

    /**
     * Write an User, this method is used by both POST and PUT action methods
     *
     * @param Request      $request Symfony request
     * @param integer|null $id      An User identifier
     *
     * @return \FOS\RestBundle\View\View|FormInterface
     */
    protected function handleWriteUser($request, $id = null)
    {
        $user = $id ? $this->getUser($id) : null;

        $form = $this->formFactory->createNamed(null, 'sonata_user_api_form_user', $user, array(
            'csrf_protection' => false
        ));

        $form->bind($request);

        if ($form->isValid()) {
            $user = $form->getData();
            $this->userManager->updateUser($user);

            $view = FOSRestView::create($user);
            $serializationContext = SerializationContext::create();
            $serializationContext->setGroups(array('sonata_api_read'));
            $serializationContext->enableMaxDepthChecks();
            $view->setSerializationContext($serializationContext);

            return $view;
        }

        return $form;
    }
}
