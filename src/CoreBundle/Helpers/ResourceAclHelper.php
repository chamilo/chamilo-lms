<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceRight;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Resource\GenericResource;
use Laminas\Permissions\Acl\Role\GenericRole;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class ResourceAclHelper
{
    public function __construct(
        private Security $security,
    ) {}

    /**
     * @param iterable<int, ResourceRight> $rights
     */
    private function init(
        ResourceLink $resourceLink,
        iterable $rights,
    ): Acl {
        // Creating roles
        $anon = new GenericRole('IS_AUTHENTICATED_ANONYMOUSLY');
        $userRole = new GenericRole('ROLE_USER');
        $student = new GenericRole('ROLE_STUDENT');
        $teacher = new GenericRole('ROLE_TEACHER');
        $studentBoss = new GenericRole('ROLE_STUDENT_BOSS');

        $currentStudent = new GenericRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_STUDENT);
        $currentTeacher = new GenericRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER);

        $currentStudentGroup = new GenericRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_GROUP_STUDENT);
        $currentTeacherGroup = new GenericRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_GROUP_TEACHER);

        $currentStudentSession = new GenericRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_STUDENT);
        $currentTeacherSession = new GenericRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_TEACHER);

        // Setting Simple ACL.
        $acl = (new Acl())
            ->addRole($anon)
            ->addRole($userRole)
            ->addRole($student)
            ->addRole($teacher)
            ->addRole($studentBoss)

            ->addRole($currentStudent)
            ->addRole($currentTeacher, ResourceNodeVoter::ROLE_CURRENT_COURSE_STUDENT)

            ->addRole($currentStudentSession)
            ->addRole($currentTeacherSession, ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_STUDENT)

            ->addRole($currentStudentGroup)
            ->addRole($currentTeacherGroup, ResourceNodeVoter::ROLE_CURRENT_COURSE_GROUP_STUDENT)
        ;

        // Add a security resource.
        $acl->addResource(new GenericResource((string) $resourceLink->getId()));

        // Check all the right this link has.
        // Set rights from the ResourceRight.
        foreach ($rights as $right) {
            $acl->allow($right->getRole(), null, (string) $right->getMask());
        }

        return $acl;
    }

    /**
     * @param iterable<int, ResourceRight> $rights
     */
    public function isAllowed(
        string $attribute,
        ResourceLink $resourceLink,
        iterable $rights,
    ): bool {
        $acl = $this->init($resourceLink, $rights);

        $askedMask = (string) self::getPermissionMask([$attribute]);

        if ($this->security->getToken() instanceof NullToken) {
            return (bool) $acl->isAllowed('IS_AUTHENTICATED_ANONYMOUSLY', $resourceLink->getId(), $askedMask);
        }

        $user = $this->security->getUser();

        $roles = $user instanceof UserInterface ? $user->getRoles() : [];

        foreach ($roles as $role) {
            if ($acl->isAllowed($role, $resourceLink->getId(), $askedMask)) {
                return true;
            }
        }

        return false;
    }

    public static function getPermissionMask(array $attributes): int
    {
        $builder = new MaskBuilder();

        foreach ($attributes as $attribute) {
            $builder->add($attribute);
        }

        return $builder->get();
    }
}
