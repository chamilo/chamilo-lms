<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Resource\ResourceRights;
use Chamilo\CoreBundle\Entity\ToolResourceRights;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Authorization\Voter\AbstractVoter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class ResourceVoter
 * @package Chamilo\CoreBundle\Security\Authorization\Voter
 */
class ResourceLinkVoter extends AbstractVoter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';

    /**
     * {@inheritdoc}
     */
    protected function getSupportedAttributes()
    {
        return array(self::VIEW, self::EDIT, self::DELETE);
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
     * .
     * @return bool
     */
    protected function isGranted($attribute, $resourceLink, $user = null)
    {
        // make sure there is a user object (i.e. that the user is logged in)
        if (!$user instanceof UserInterface) {
            return false;
        }

        $userSent = $resourceLink->getUser();

        // Owner.
        if (isset($userSent) &&
            $user->getUsername() == $userSent->getUsername()) {
            return true;
        }

        $rightFromResourceLink = $resourceLink->getRights();

        if ($rightFromResourceLink->count()) {
            /** @var ResourceRights $right */
            $rights = $rightFromResourceLink;
        } else {
            $rights = $resourceLink->getResourceNode()->getTool()->getToolResourceRights();
        }

        $roles = array();
        foreach ($rights as $right) {
            $roles[$right->getRole()] = $right->getMask() ;
        }

        $mask = new MaskBuilder();
        $mask->add($attribute);
        $code = $mask->get();

        switch ($attribute) {
            case self::VIEW:

                if ($user->getRoles())
                var_dump($code);
                exit;
                break;
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
