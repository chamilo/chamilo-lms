<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Resource\ResourceRight;
use Chamilo\CoreBundle\Entity\Session;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Zend\Permissions\Acl\Acl;
//use Zend\Permissions\Acl\Resource\GenericResource as Resource;
use Zend\Permissions\Acl\Resource\GenericResource as SecurityResource;
use Zend\Permissions\Acl\Role\GenericRole as Role;

//use Sonata\AdminBundle\Security\Acl\Permission\MaskBuilder;

/**
 * Class ResourceNodeVoter.
 *
 * @package Chamilo\CoreBundle\Security\Authorization\Voter
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

    protected $container;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return int
     */
    public static function getReaderMask(): int
    {
        $builder = new MaskBuilder();
        $builder
            ->add(self::VIEW)
        ;

        return $builder->get();
    }

    /**
     * @return int
     */
    public static function getEditorMask(): int
    {
        $builder = new MaskBuilder();
        $builder
            ->add(self::VIEW)
            ->add(self::EDIT)
        ;

        return $builder->get();
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
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
     * @param string         $attribute
     * @param ResourceNode   $resourceNode
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $resourceNode, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Make sure there is a user object (i.e. that the user is logged in)
        if (!$user instanceof UserInterface) {
            return false;
        }

        // Checking admin roles
        $authChecker = $this->container->get('security.authorization_checker');

        // Admins have access to everything
        if ($authChecker->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Check if I'm the owner
        $creator = $resourceNode->getCreator();

        if ($creator instanceof UserInterface &&
            $user->getUsername() === $creator->getUsername()) {
            return true;
        }

        // Checking possible links connected to this resource
        $request = $this->container->get('request_stack')->getCurrentRequest();

        $courseCode = $request->get('course');
        $sessionId = $request->get('session');

        $links = $resourceNode->getResourceLinks();
        $linkFound = false;

        $courseManager = $this->container->get('chamilo_core.entity.manager.course_manager');

        /** @var ResourceLink $link */
        foreach ($links as $link) {
            // Block access if visibility is deleted.
            if ($link->getVisibility() === ResourceLink::VISIBILITY_DELETED) {
                $linkFound = false;
                break;
            }
            $linkUser = $link->getUser();
            $linkCourse = $link->getCourse();
            $linkSession = $link->getSession();
            $linkUserGroup = $link->getUserGroup();

            // Check if resource was sent to the current user.
            if ($linkUser instanceof UserInterface &&
                $linkUser->getUsername() === $creator->getUsername()
            ) {
                $linkFound = true;
                break;
            }

            // @todo Check if resource was sent to a usergroup
            // @todo Check if resource was sent to a group inside a course

            // Check if resource was sent to a course inside a session
            if ($linkSession instanceof Session && !empty($sessionId) &&
                $linkCourse instanceof Course && !empty($courseCode)
            ) {
                $session = $this->container->get('chamilo_core.entity.manager.session_manager')->find($sessionId);
                $course = $courseManager->findOneByCode($courseCode);
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
            if ($linkCourse instanceof Course && !empty($courseCode)) {
                $course = $courseManager->findOneByCode($courseCode);
                if ($course instanceof Course &&
                    $linkCourse->getCode() === $course->getCode()
                ) {
                    $linkFound = true;
                    break;
                }
            }
        }

        // No link was found or not available
        if (false === $linkFound) {
            return false;
        }

        // Getting rights from the link
        $rightFromResourceLink = $link->getResourceRight();

        if ($rightFromResourceLink->count()) {
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

            $resourceRight = new ResourceRight();
            $resourceRight
                ->setMask($editorMask)
                ->setRole(self::ROLE_CURRENT_COURSE_TEACHER)
            ;
            $rights[] = $resourceRight;

            $resourceRight = new ResourceRight();
            $resourceRight
                ->setMask($readerMask)
                ->setRole(self::ROLE_CURRENT_COURSE_STUDENT)
            ;
            $rights[] = $resourceRight;
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
        $superAdmin = new Role('ROLE_SUPER_ADMIN');
        $admin = new Role('ROLE_ADMIN');

        // Adding roles to the ACL
        $acl
            ->addRole($userRole)
            ->addRole($student)
            ->addRole($teacher)
            ->addRole($currentStudent)
            ->addRole($currentTeacher, self::ROLE_CURRENT_COURSE_STUDENT)
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
