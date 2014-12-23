<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
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

    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

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

                    // "Open to the world"
                    if ($course->isPublic()) {
                        //return true;
                    }

                    // User is subscribed in the user list.
                    $userIsSubscribed = $this->getEntityManager()
                        ->getRepository('ChamiloCoreBundle:Course')
                        ->isUserSubscribedInCourse($user, $course);

                    if ($userIsSubscribed) {
                        dump('user_is_subscribed');
                        return true;
                    }

                    // Is an active course
                    if ($course->isActive()) {
                        //return true;
                    }

                } else {
                    // Course in a session.
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
