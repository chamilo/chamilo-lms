<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Resource\ResourceRights;
use Chamilo\CoreBundle\Entity\ToolResourceRights;
use Doctrine\Common\Collections\ArrayCollection;
use Sonata\AdminBundle\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AbstractVoter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class ResourceVoter
 * @package Chamilo\CoreBundle\Security\Authorization\Voter
 */
class ResourceLinkVoter extends AbstractVoter
{
    private $container;

    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';

    /**
     * Constructor
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSupportedAttributes()
    {
        return array(
            self::VIEW,
            self::EDIT,
            self::DELETE
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getSupportedClasses()
    {
        return array('Chamilo\CoreBundle\Entity\Resource\ResourceLink');
    }

    /**
     * @param string $attribute
     * @param ResourceLink $resourceLink
     * @param null $user
     *
     * @return bool
     */
    protected function isGranted($attribute, $resourceLink, $user = null)
    {
        // make sure there is a user object (i.e. that the user is logged in)
        if (!$user instanceof UserInterface) {
            return false;
        }

        // Checking admin roles
        $authChecker = $this->container->get('security.authorization_checker');
        $adminRoles = array(
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        );

        foreach ($adminRoles as $adminRole) {
            if ($authChecker->isGranted($adminRole)) {
                //return true;
            }
        }

        $userSent = $resourceLink->getUser();

        // Owner.
        if ($userSent instanceof UserInterface &&
            $user->getUsername() == $userSent->getUsername()) {

            return true;
        }

        // Getting user rights
        $rightFromResourceLink = $resourceLink->getRights();

        if ($rightFromResourceLink->count()) {
            // Taken rights of the link
            $rights = $rightFromResourceLink;
        } else {
            // Taken the rights from the default tool
            $rights = $resourceLink->getResourceNode()->getTool()->getToolResourceRights();
        }

        $roles = array();
        foreach ($rights as $right) {
            $roles[$right->getRole()] = $right->getMask();
        }

        $mask = new MaskBuilder();
        $mask->add($attribute);
        $code = $mask->get();

        switch ($attribute) {
            case self::VIEW:
                foreach ($user->getRoles() as $role) {
                    if (isset($roles[$role]) && $roles[$role] == $code) {
                        dump('return true');
                        return true;
                    }
                }
                dump('return false');
                return false;
            case self::EDIT:
                break;
        }

        // Course is visible?
        if ($attribute == self::VIEW) {
            return true;
        }

        return false;
    }
}
