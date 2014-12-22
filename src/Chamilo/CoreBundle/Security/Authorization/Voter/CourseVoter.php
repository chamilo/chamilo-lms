<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authorization\Voter\AbstractVoter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class CourseVoter
 * @package Chamilo\CoreBundle\Security\Authorization\Voter
 */
class CourseVoter extends AbstractVoter
{
    const VIEW = 'view';
    const EDIT = 'edit';

    /**
     * {@inheritdoc}
     */
    protected function getSupportedAttributes()
    {
        return array(self::VIEW, self::EDIT);
    }

    /**
     * {@inheritdoc}
     */
    protected function getSupportedClasses()
    {
        return array('Chamilo\CoreBundle\Entity\Course');
    }

    /**
     * @param string $attribute
     * @param Course $course
     * @param User $user
     * @return bool
     */
    protected function isGranted($attribute, $course, $user = null)
    {
        // make sure there is a user object (i.e. that the user is logged in)
        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::VIEW:
                $session = $course->getCurrentSession();
                if (empty($session)) {
                    if ($course->isActive()) {
                        return true;
                    }
                } else {
                    if ($session->isActive() && $course->isActive()) {
                        return true;
                    }
                }
                return false;
            case self::EDIT:
                // Teacher
                if ($user->getId() === $course->getOwner()->getId()) {
                    return true;
                }
                return false;
        }

        return false;
    }
}
