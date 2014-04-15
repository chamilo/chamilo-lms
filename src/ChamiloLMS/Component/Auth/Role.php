<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Component\Auth;

use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use ChamiloLMS\Entity\CourseRelUser;

/**
 * Class Role
 * @package ChamiloLMS\Component\Auth
 */
class Role implements RoleInterface
{
    private $user;

    /**
     * @param AdvancedUserInterface $user
     * @param string $status
     * @param int $courseId
     */
    public function __construct(AdvancedUserInterface $user, $status, $courseId)
    {
        $this->user = $user;
        $this->status = $status;
        $this->courseId = $courseId;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        $role = 'ROLE';
        $status = $this->status;
        $courseId = $this->courseId;

        switch ($status) {
            case STUDENT:
                $role .= '_STUDENT';
                break;
            case COURSEMANAGER:
                $role .= '_TEACHER';
                break;
        }

        $role .= '_COURSE_'.$courseId.'_SESSION_0';
        return $role;
    }
}
