<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceRight;
use Chamilo\CoreBundle\Entity\Session;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Resource\GenericResource as SecurityResource;
use Laminas\Permissions\Acl\Role\GenericRole as Role;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class ResourceNodeVoter.
 */
class ResourceNodeVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const CREATE = 'CREATE';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';
    public const EXPORT = 'EXPORT';

    public const ROLE_CURRENT_COURSE_TEACHER = 'ROLE_CURRENT_COURSE_TEACHER';
    public const ROLE_CURRENT_COURSE_STUDENT = 'ROLE_CURRENT_COURSE_STUDENT';
    public const ROLE_CURRENT_SESSION_COURSE_TEACHER = 'ROLE_CURRENT_SESSION_COURSE_TEACHER';
    public const ROLE_CURRENT_SESSION_COURSE_STUDENT = 'ROLE_CURRENT_SESSION_COURSE_STUDENT';

    private $container;
    private $security;

    /**
     * Constructor.
     */
    public function __construct(ContainerInterface $container, Security $security)
    {
        $this->container = $container;
        $this->security = $security;
    }

    public static function getReaderMask(): int
    {
        $builder = new MaskBuilder();
        $builder
            ->add(self::VIEW)
        ;

        return $builder->get();
    }

    public static function getEditorMask(): int
    {
        $builder = new MaskBuilder();
        $builder
            ->add(self::VIEW)
            ->add(self::EDIT)
        ;

        return $builder->get();
    }

    protected function supports(string $attribute, $subject): bool
    {
        $options = [
            self::VIEW,
            self::CREATE,
            self::EDIT,
            self::DELETE,
            self::EXPORT,
        ];

        // if the attribute isn't one we support, return false
        if (!in_array($attribute, $options)) {
            return false;
        }

        // only vote on Post objects inside this voter
        if (!$subject instanceof ResourceNode) {
            return false;
        }

        return true;
    }

    /**
     * @param ResourceNode $resourceNode
     */
    protected function voteOnAttribute(string $attribute, $resourceNode, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Make sure there is a user object (i.e. that the user is logged in)
        if (!$user instanceof UserInterface) {
            return false;
        }

        if (!$resourceNode instanceof ResourceNode) {
            return false;
        }

        // Illustrations are always visible.
        if ('illustrations' === $resourceNode->getResourceType()->getName()) {
            return true;
        }

        // Course access are protected using the CourseVoter not by ResourceNodeVoter.
        if ('courses' === $resourceNode->getResourceType()->getName()) {
            return true;
        }

        // Checking admin role.
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Check if I'm the owner.
        $creator = $resourceNode->getCreator();

        if ($creator instanceof UserInterface &&
            $user->getUsername() === $creator->getUsername()) {
            return true;
        }

        // Checking links connected to this resource.
        $request = $this->container->get('request_stack')->getCurrentRequest();

        // @todo fix parameters.
        $courseId = $request->get('cid');
        $sessionId = $request->get('sid');

        $links = $resourceNode->getResourceLinks();
        $linkFound = false;
        $courseManager = $this->container->get('Chamilo\CoreBundle\Repository\CourseRepository');

        foreach ($links as $link) {
            // Block access if visibility is deleted.
            if (ResourceLink::VISIBILITY_DELETED === $link->getVisibility()) {
                $linkFound = false;

                break;
            }

            $linkUser = $link->getUser();
            // Check if resource was sent to the current user.
            if ($linkUser instanceof UserInterface &&
                $linkUser->getUsername() === $creator->getUsername()
            ) {
                $linkFound = true;

                break;
            }

            $linkCourse = $link->getCourse();
            $linkSession = $link->getSession();
            //$linkUserGroup = $link->getUserGroup();

            // Course found, but courseId not set.
            if ($linkCourse instanceof Course && empty($courseId)) {
                continue;
            }

            // @todo Check if resource was sent to a usergroup
            // @todo Check if resource was sent to a group inside a course
            // Check if resource was sent to a course inside a session
            if ($linkSession instanceof Session && !empty($sessionId) &&
                $linkCourse instanceof Course && !empty($courseId)
            ) {
                $session = $this->container->get('chamilo_core.entity.manager.session_manager')->find($sessionId);
                $course = $courseManager->find($courseId);
                if ($session instanceof Session &&
                    $course instanceof Course &&
                    $linkCourse->getCode() === $course->getCode() &&
                    $linkSession->getId() === $session->getId()
                ) {
                    $linkFound = true;

                    break;
                }
            }

            // Check if resource was sent to a course
            if ($linkCourse instanceof Course && !empty($courseId)) {
                $course = $courseManager->find($courseId);

                if ($course instanceof Course &&
                    $linkCourse->getCode() === $course->getCode()
                ) {
                    $linkFound = true;

                    break;
                }
            }

            if (ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility()) {
                $linkFound = true;

                break;
            }
        }

        // No link was found or not available
        if (false === $linkFound) {
            return false;
        }

        // Getting rights from the link
        $rightFromResourceLink = $link->getResourceRight();

        $rights = [];
        if ($rightFromResourceLink->count() > 0) {
            // Taken rights from the link
            $rights = $rightFromResourceLink;
        } else {
            // Taken the rights from the default tool
            //$rights = $link->getResourceNode()->getTool()->getToolResourceRight();
            //$rights = $link->getResourceNode()->getResourceType()->getTool()->getToolResourceRight();
            // By default the rights are:
            // teacher: CRUD
            // student: read
            $readerMask = self::getReaderMask();
            $editorMask = self::getEditorMask();

            if (!empty($courseId)) {
                $resourceRight = new ResourceRight();
                $resourceRight
                    ->setMask($editorMask)
                    ->setRole(self::ROLE_CURRENT_COURSE_TEACHER);
                $rights[] = $resourceRight;

                $resourceRight = new ResourceRight();
                $resourceRight
                    ->setMask($readerMask)
                    ->setRole(self::ROLE_CURRENT_COURSE_STUDENT);
                $rights[] = $resourceRight;
            }

            if (!empty($sessionId)) {
                $resourceRight = new ResourceRight();
                $resourceRight
                    ->setMask($editorMask)
                    ->setRole(self::ROLE_CURRENT_SESSION_COURSE_TEACHER)
                ;
                $rights[] = $resourceRight;

                $resourceRight = new ResourceRight();
                $resourceRight
                    ->setMask($readerMask)
                    ->setRole(self::ROLE_CURRENT_SESSION_COURSE_STUDENT)
                ;
                $rights[] = $resourceRight;
            }
            if (empty($rights) && ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility()) {
                // Give just read access
                $resourceRight = new ResourceRight();
                $resourceRight
                    ->setMask($readerMask)
                    ->setRole('ROLE_USER')
                ;
                $rights[] = $resourceRight;
            }
        }

        // Asked mask
        $mask = new MaskBuilder();
        $mask->add($attribute);
        $askedMask = $mask->get();

        // Setting zend simple ACL
        $acl = new Acl();

        // Creating roles
        // @todo move this in a service
        $userRole = new Role('ROLE_USER');
        $teacher = new Role('ROLE_TEACHER');
        $student = new Role('ROLE_STUDENT');
        $currentTeacher = new Role(self::ROLE_CURRENT_COURSE_TEACHER);
        $currentStudent = new Role(self::ROLE_CURRENT_COURSE_STUDENT);

        $currentTeacherSession = new Role(self::ROLE_CURRENT_SESSION_COURSE_TEACHER);
        $currentStudentSession = new Role(self::ROLE_CURRENT_SESSION_COURSE_STUDENT);

        $superAdmin = new Role('ROLE_SUPER_ADMIN');
        $admin = new Role('ROLE_ADMIN');

        // Adding roles to the ACL
        $acl
            ->addRole($userRole)
            ->addRole($student)
            ->addRole($teacher)
            ->addRole($currentStudent)
            ->addRole($currentTeacher, self::ROLE_CURRENT_COURSE_STUDENT)
            ->addRole($currentStudentSession)
            ->addRole($currentTeacherSession, self::ROLE_CURRENT_SESSION_COURSE_STUDENT)
            ->addRole($superAdmin)
            ->addRole($admin)
        ;

        // Adds a resource
        $resource = new SecurityResource($link);
        $acl->addResource($resource);

        // Check all the right this link has.
        // $roles = [];
        // Set rights from the ResourceRight
        foreach ($rights as $right) {
            //$roles[$right->getMask()] = $right->getRole();
            $acl->allow($right->getRole(), null, $right->getMask());
        }

        // var_dump($askedMask, $roles);
        // Role and permissions settings
        // Student can just view (read)
        //$acl->allow($student, null, self::getReaderMask());

        // Teacher can view/edit
        $acl->allow(
            $teacher,
            null,
            [
                self::getReaderMask(),
                self::getEditorMask(),
            ]
        );

        // Admin can do everything
        $acl->allow($admin);
        $acl->allow($superAdmin);
        foreach ($user->getRoles() as $role) {
            //var_dump($acl->isAllowed($role, $resource, $askedMask), $role);
            if ($acl->isAllowed($role, $resource, $askedMask)) {
                return true;
            }
        }

        //dump('not allowed to '.$attribute);
        return false;
    }
}
