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
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use PHPUnit_Framework_TestCase;

/**
 * Class CourseContext
 * Defines application features from the specific context.
 * DefaultContext class drops the database automatically
 * @package Chamilo\CoreBundle\Behat
 */
class CourseContext extends DefaultContext implements Context, SnippetAcceptingContext
{
    // only in php 5.4 to get access to $this->getContainer()
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
     * @Given I have a session :arg1
     */
    public function iHaveASession($arg1)
    {
        $em = $this->getEntityManager();

        $entity = new Session();
        $entity->setName($arg1);
        $em->persist($entity);
        $em->flush();
    }

    /**
     * @When I add session :arg1 to course :arg2
     */
    public function iAddSessionToCourse($sessionName, $courseTitle)
    {
        $session = $this->getContainer()->get('chamilo_core.manager.session')->findOneByName($sessionName);

        /** @var Course $course */
        $course = $this->getContainer()->get('chamilo_core.manager.course')->findOneByTitle($courseTitle);

        $session->addCourse($course);

        $em = $this->getEntityManager();
        $em->persist($course);
        $em->flush();
    }

    /**
     * @Then I should find a course :arg1 in session :arg2
     */
    public function iShouldFindACourseInSession($courseTitle, $sessionTitle)
    {
        /** @var Session $session */
        $session = $this->getRepository('ChamiloCoreBundle:Session')->findOneByName($sessionTitle);
        $found = false;
        /** @var SessionRelCourse $sessionRelCourse */
        foreach ($session->getCourses() as $sessionRelCourse) {
            if ($courseTitle === $sessionRelCourse->getCourse()->getTitle()) {
                $found = true;
                break;
            }
        }

        PHPUnit_Framework_TestCase::assertTrue($found);
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
        $user = $this->getUserManager()->findUserByUsername($username);

        /** @var Course $course */
        $course = $this->getContainer()->get('chamilo_core.manager.course')->findOneByTitle($courseTitle);

        $course->addStudent($user);

        $em = $this->getEntityManager();
        $em->persist($course);
        $em->flush();
    }

    /**
     * @When I add teacher :arg1 to course :arg2
     */
    public function iAddTeacherToCourse($username, $courseTitle)
    {
        $user = $this->getUserManager()->findUserByUsername($username);

        /** @var Course $course */
        $course = $this->getContainer()->get('chamilo_core.manager.course')->findOneByTitle($courseTitle);

        $course->addTeacher($user);

        $em = $this->getEntityManager();
        $em->persist($course);
        $em->flush();
    }

    /**
     * @When I add student :arg1 to course :arg2
     */
    public function iAddStudentToCourse($username, $courseTitle)
    {
        $user = $this->getUserManager()->findUserByUsername($username);

        /** @var Course $course */
        $course = $this->getContainer()->get('chamilo_core.manager.course')->findOneByTitle($courseTitle);

        $course->addStudent($user);

        $em = $this->getEntityManager();
        $em->persist($course);
        $em->flush();
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
     * @Then I should find a student :arg1 in course :arg2
     */
    public function iShouldFindAStudentInCourse($username, $courseTitle)
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
     * @Then I should find a teacher :arg1 in course :arg2
     */
    public function iShouldFindATeacherInCourse($username, $courseTitle)
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
}
