<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Symfony\Component\Security\Core\Authorization\Voter\AbstractVoter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class ResourceVoter
 * @package Chamilo\CoreBundle\Security\Authorization\Voter
 */
class ResourceVoter extends AbstractVoter
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
        return array('Chamilo\CoreBundle\Entity\Resource\ResourceNode');
    }

    /**
     * @param string $attribute
     * @param ResourceNode $resourceNode
     * @param null $user
     * @return bool
     */
    protected function isGranted($attribute, $resourceNode, $user = null)
    {
        // make sure there is a user object (i.e. that the user is logged in)
        if (!$user instanceof UserInterface) {
            return false;
        }

        // Owner.
        if ($user->getUsername() == $resourceNode->getCreator()->getUsername()) {
            return true;
        }

        switch ($attribute) {
            case self::VIEW:

                break;
            case self::EDIT:
                break;
        }

        // Course is visible?
        if ($attribute == self::VIEW && $resourceNode->isActive()) {
            return true;
        }

        // Teacher
        if ($attribute == self::EDIT && $user->getId() === $course->getOwner()->getId()) {
            return true;
        }

        return false;
    }
}
