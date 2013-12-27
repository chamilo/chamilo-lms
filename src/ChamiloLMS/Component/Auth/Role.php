<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Component\Auth;

use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Entity\CourseRelUser;

/**
 * Class Role
 * @package ChamiloLMS\Component\Auth
 */
class Role implements RoleInterface
{
    private $user;
    private $courseRelUser;

    /**
     * @param AdvancedUserInterface $user
     * @param CourseRelUser $courseRelUser
     */
    public function __construct(AdvancedUserInterface $user, CourseRelUser $courseRelUser)
    {
        $this->user = $user;
        $this->courseRelUser = $courseRelUser;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        $role = 'ROLE';
        if (empty($this->courseRelUser)) {
            return null;
        }
        $status = $this->courseRelUser->getStatus();
        switch ($status) {
            case STUDENT:
                $role .= '_STUDENT';
                break;
            case COURSEMANAGER:
                $role .= '_TEACHER';
                break;
        }
        $courseId = $this->courseRelUser->getCId();
        $role .= '_COURSE_'.$courseId.'_SESSION_0';
        return $role;
    }
}
