<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Manager\CourseManager;
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
    private $courseManager;

    /**
     * @param EntityManager $entityManager
     * @param CourseManager $courseManager
     */
    public function __construct(
        EntityManager $entityManager,
        CourseManager $courseManager
    )
    {
        $this->entityManager = $entityManager;
        $this->courseManager = $courseManager;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @return CourseManager
     */
    public function getCourseManager()
    {
        return $this->courseManager;
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

        $courseManager = $this->getCourseManager();

        switch ($attribute) {
            case self::VIEW:
                $session = $course->getCurrentSession();
                if (empty($session)) {

                    // "Open to the world"
                    if ($course->isPublic()) {
                        //return true;
                    }

                    // User is subscribed in the user list.
                    $userIsSubscribed = $courseManager->isUserSubscribedInCourse(
                        $user,
                        $course
                    );

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
                break;
            case self::EDIT:
                // Teacher
                // @todo
                if ($user->getId() === $course->getOwner()->getId()) {
                    return true;
                }
                break;
        }
        dump('Course voter false!');
        return true;
    }
}
