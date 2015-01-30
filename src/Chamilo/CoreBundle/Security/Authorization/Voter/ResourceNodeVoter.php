<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Resource\ResourceRights;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\ToolResourceRights;
use Doctrine\Common\Collections\ArrayCollection;
use Sonata\AdminBundle\Security\Acl\Permission\AdminPermissionMap;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Core\Authorization\Voter\AbstractVoter;
use Symfony\Component\Security\Core\User\UserInterface;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Role\GenericRole as Role;
use Zend\Permissions\Acl\Resource\GenericResource as Resource;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
//use Sonata\AdminBundle\Security\Acl\Permission\MaskBuilder;

/**
 * Class ResourceNodeVoter
 * @package Chamilo\CoreBundle\Security\Authorization\Voter
 */
class ResourceNodeVoter extends AbstractVoter
{
    private $container;

    const VIEW = 'VIEW';
    const CREATE = 'CREATE';
    const EDIT = 'EDIT';
    const DELETE = 'DELETE';
    const EXPORT = 'EXPORT';

    const ROLE_CURRENT_COURSE_TEACHER = 'ROLE_CURRENT_COURSE_TEACHER';
    const ROLE_CURRENT_COURSE_STUDENT = 'ROLE_CURRENT_COURSE_STUDENT';

    /**
     * Constructor
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSupportedAttributes()
    {
        return array(
            self::VIEW,
            self::CREATE,
            self::EDIT,
            self::DELETE,
            self::EXPORT
        );
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
     *
     * @return bool
     */
    protected function isGranted($attribute, $resourceNode, $user = null)
    {
        // Make sure there is a user object (i.e. that the user is logged in)
        if (!$user instanceof UserInterface) {
            return false;
        }

        // Checking admin roles
        $authChecker = $this->container->get('security.authorization_checker');

        // Admins have access to everything
        if ($authChecker->isGranted('ROLE_ADMIN')) {
             // return true;
        }

        // Check if I'm the owner
        $creator = $resourceNode->getCreator();

        if ($creator instanceof UserInterface &&
            $user->getUsername() == $creator->getUsername()) {

            //return true;
        }

        // Checking possible links connected to this resource
        $request = $this->container->get('request_stack')->getCurrentRequest();

        $courseCode = $request->get('course');
        $sessionId = $request->get('session');

        $links = $resourceNode->getLinks();
        $linkFound = false;

        /** @var ResourceLink $link */
        foreach ($links as $link) {
            $linkUser = $link->getUser();
            $linkCourse = $link->getCourse();
            $linkSession = $link->getSession();
            $linkUserGroup = $link->getUserGroup();

            // Check if resource was sent to the current user
            if ($linkUser instanceof UserInterface &&
                $linkUser->getUsername() == $creator->getUsername()
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
                $session = $this->container->get('chamilo_core.manager.session')->find($sessionId);
                $course = $this->container->get('chamilo_core.manager.course')->findOneByCode($courseCode);
                if ($session instanceof Session &&
                    $course instanceof Course &&
                    $linkCourse->getCode() == $course->getCode() &&
                    $linkSession->getId() == $session->getId()
                ) {
                    $linkFound = true;
                    break;
                }
            }

            // Check if resource was sent to a course
            if ($linkCourse instanceof Course && !empty($courseCode)) {
                $course = $this->container->get('chamilo_core.manager.course')->findOneByCode($courseCode);
                if ($course instanceof Course &&
                    $linkCourse->getCode() == $course->getCode()
                ) {
                    $linkFound = true;
                    break;
                }
            }
        }

        // No link was found!
        if ($linkFound === false) {
            return false;
        }

        // Getting rights from the link
        $rightFromResourceLink = $link->getRights();

        if ($rightFromResourceLink->count()) {
            // Taken rights from the link
            $rights = $rightFromResourceLink;
        } else {
            // Taken the rights from the default tool
            $rights = $link->getResourceNode()->getTool()->getToolResourceRights();
        }

        // Asked mask
        $mask = new MaskBuilder();
        $mask->add($attribute);
        $askedMask = $mask->get();

        // Check all the right this link has.
        $roles = array();
        foreach ($rights as $right) {
            $roles[$right->getMask()] = $right->getRole();
        }

        // Setting zend simple ACL
        $acl = new Acl();

        // Creating roles
        // @todo move this in a service
        $userRole = new Role('ROLE_USER');
        $teacher = new Role(self::ROLE_CURRENT_COURSE_TEACHER);
        $student = new Role(self::ROLE_CURRENT_COURSE_STUDENT);
        $superAdmin = new Role('ROLE_SUPER_ADMIN');
        $admin = new Role('ROLE_ADMIN');

        // Adding roles to the ACL
        // User role
        $acl->addRole($userRole);
        // Adds role student
        $acl->addRole($student);
        // Adds teacher role, inherit student role
        $acl->addRole($teacher, $student);
        $acl->addRole($superAdmin);
        $acl->addRole($admin);

        // Adds a resource
        $resource = new Resource($link);
        $acl->addResource($resource);

        // Role and permissions settings
        // Students can view

        // Student can just view (read)
        $acl->allow($student, null, self::getReaderMask());

        // Teacher can view/edit
        $acl->allow(
            $teacher,
            null,
            array(
                self::getReaderMask(),
                self::getEditorMask()
            )
        );

        // Admin can do everything
        $acl->allow($admin);
        $acl->allow($superAdmin);

        foreach ($user->getRoles() as $role) {
            if ($acl->isAllowed($role, $resource, $askedMask)) {
                dump('passed');
                return true;
            }
        }

        dump('not allowed to '.$attribute);

        return false;
    }

    /**
     * @return int
     */
    public static function getReaderMask()
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
    public static function getEditorMask()
    {
        $builder = new MaskBuilder();
        $builder
            ->add(self::EDIT)
        ;

        return $builder->get();
    }

}
