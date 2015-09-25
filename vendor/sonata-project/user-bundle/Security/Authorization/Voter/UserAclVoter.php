<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Sonata\UserBundle\Security\Authorization\Voter;

use FOS\UserBundle\Model\UserInterface;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Acl\Voter\AclVoter;

class UserAclVoter extends AclVoter
{
   /**
    * {@InheritDoc}
    */
    public function supportsClass($class)
    {
        // support the Object-Scope ACL
        return is_subclass_of($class, 'FOS\UserBundle\Model\UserInterface');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsAttribute($attribute)
    {
        return $attribute === 'EDIT' || $attribute === 'DELETE';
    }

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!$this->supportsClass(get_class($object))) {
            return self::ACCESS_ABSTAIN;
        }

        foreach ($attributes as $attribute) {
            if ($this->supportsAttribute($attribute) && $object instanceof UserInterface && $token->getUser() instanceof UserInterface) {
                if ($object->isSuperAdmin() && !$token->getUser()->isSuperAdmin()) {
                    // deny a non super admin user to edit or delete a super admin user
                    return self::ACCESS_DENIED;
                }
            }
        }

        // leave the permission voting to the AclVoter that is using the default permission map
        return self::ACCESS_ABSTAIN;
    }
}
