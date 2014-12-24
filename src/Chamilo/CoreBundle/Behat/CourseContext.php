<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Sylius\Bundle\ResourceBundle\Behat\DefaultContext;
use PHPUnit_Framework_TestCase;

/**
 * Defines application features from the specific context.
 */
class CourseContext extends DefaultContext implements Context, SnippetAcceptingContext
{
    // only in php 5.4
    //use KernelDictionary;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

     /**
     * @Given there are following users:
     */
    public function thereAreFollowingUsers(TableNode $userTable)
    {
        $userManager = $this->getContainer()->get('fos_user.user_manager');
        $em = $this->getEntityManager();

        foreach ($userTable as $userHash) {
            $user = $userManager->createUser();
            $user->setUsername($userHash['username']);
            $user->setEmail($userHash['email']);
            $user->setPassword($userHash['plain_password']);
            $user->setEnabled(1);
            $em->persist($user);
        }
        $em->flush();
    }

     /**
     * @Given I have a course :arg1
     */
    public function iHaveACourse($arg1)
    {
        $em = $this->getEntityManager();

        $entity = new Course();
        $entity->setTitle($arg1);
        $em->persist($entity);
        $em->flush();
    }

    /**
     * @Given I have a user :arg1
     */
    /*public function iHaveAUser($arg1)
    {
        $userManager = $this->getContainer()->get('fos_user.user_manager');
        $em = $this->getEntityManager();
        $user = $userManager->createUser();
        $user->setUsername($arg1);

        $em->persist($user);
        $em->flush();
    }*/

    /**
     * @When I add user :arg1 to course :arg2
     */
    public function iAddUserToCourse($username, $courseTitle)
    {
        $user = $this->getContainer()->get('fos_user.user_manager')->findUserByUsername($username);

        /** @var Course $course */
        $course = $this->getContainer()->get('chamilo_core.manager.course')->findOneByTitle($courseTitle);

        $course->addStudent($user);

        $this->getEntityManager()->persist($course);
        $this->getEntityManager()->flush();
    }

    /**
     * @When I add teacher :arg1 to course :arg2
     */
    public function iAddTeacherToCourse($username, $courseTitle)
    {
        $user = $this->getContainer()->get('fos_user.user_manager')->findUserByUsername($username);

        /** @var Course $course */
        $course = $this->getContainer()->get('chamilo_core.manager.course')->findOneByTitle($courseTitle);

        $course->addTeacher($user);

        $this->getEntityManager()->persist($course);
        $this->getEntityManager()->flush();
    }

    /**
     * @When I add student :arg1 to course :arg2
     */
    public function iAddStudentToCourse($username, $courseTitle)
    {
        $user = $this->getContainer()->get('fos_user.user_manager')->findUserByUsername($username);

        /** @var Course $course */
        $course = $this->getContainer()->get('chamilo_core.manager.course')->findOneByTitle($courseTitle);

        $course->addStudent($user);

        $this->getEntityManager()->persist($course);
        $this->getEntityManager()->flush();
    }

    /**
     * @Then I should find a user :arg1 in course :arg2
     */
    public function iShouldFindAUserInCourse($username, $courseTitle)
    {
        /** @var Course $course */
        $course = $this->getRepository('ChamiloCoreBundle:Course')->findOneByTitle($courseTitle);
        $found = false;
        /** @var CourseRelUser $user */
        foreach ($course->getUsers() as $user) {
            if ($username === $user->getUser()->getUserName()) {
                $found = true;
                break;
            }
        }

        PHPUnit_Framework_TestCase::assertTrue($found);
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|object
     */
    public function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * Returns the Doctrine repository manager for a given entity.
     *
     * @param string $entityName The name of the entity.
     *
     * @return EntityRepository
     */
    protected function getRepository($entityName)
    {
        return $this->getEntityManager()->getRepository($entityName);
    }
}
