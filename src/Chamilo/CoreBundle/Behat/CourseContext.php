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

     /**
     * @When I add student :arg1 to course :arg2 in session :arg3
     */
    public function iAddStudentToCourseInSession($username, $courseTitle, $sessionTitle)
    {
        $user = $this->getUserManager()->findUserByUsername($username);
        $course = $this->getCourseManager()->findOneByTitle($courseTitle);
        $session = $this->getSessionManager()->findOneByName($sessionTitle);

        $this->getSessionManager()->addStudentInCourse($user, $course, $session);
    }

    /**
     * @Then I should find a user :arg1 in course :arg2 in session :arg3
     */
    public function iShouldFindAUserInCourseInSession($username, $courseTitle, $sessionTitle)
    {
        $user = $this->getUserManager()->findUserByUsername($username);
        $course = $this->getCourseManager()->findOneByTitle($courseTitle);
        $session = $this->getSessionManager()->findOneByName($sessionTitle);

        return $session->hasUserInCourse($user, $course, $session::STUDENT);
    }

    /**
    * @When I add user with status :arg1 with username :arg2 in course :arg3 in session :arg4
    */
    public function iAddUserWithStatusWithUsernameInCourseInSession($status, $username, $courseTitle, $sessionTitle)
    {
        $user = $this->getUserManager()->findUserByUsername($username);
        $course = $this->getCourseManager()->findOneByTitle($courseTitle);
        $session = $this->getSessionManager()->findOneByName($sessionTitle);

        switch ($status) {
            case 'student':
                $this->getSessionManager()->addStudentInCourse($user, $course, $session);
                break;
            case 'drh':
                $this->getSessionManager()->addDrh($user, $session);
                break;
            case 'coach':
                $this->getSessionManager()->addCoachInCourse($user, $course, $session);
                break;
        }
    }

    /**
     * @Then I should find a user :arg1 with status :arg2 in course :arg3 in session :arg4
     */
    public function iShouldFindAUserInCourseInSessionWithStatus($username, $status, $courseTitle, $sessionTitle)
    {
        $user = $this->getUserManager()->findUserByUsername($username);
        $course = $this->getCourseManager()->findOneByTitle($courseTitle);
        $session = $this->getSessionManager()->findOneByName($sessionTitle);

        switch ($status) {
            case 'student':
                $this->getSessionManager()->hasStudentInCourse(
                    $user,
                    $course,
                    $session
                );
                break;
            case 'drh':
                $this->getSessionManager()->hasDrh($user, $session);
                break;
            case 'coach':
                $this->getSessionManager()->hasCoachInCourse(
                    $user,
                    $course,
                    $session
                );
                break;
        }
    }

     /**
     * @When I add course :arg1 as user :arg2
     */
    public function iAddCourseAsUser($courseTitle, $username)
    {
        $course = $this->getCourseManager()->findOneByTitle($courseTitle);
        $user = $this->getUserManager()->findUserByUsername($username);
        $course->addTeacher($user);
    }

    /**
     * @Then I should find a course :arg1 in the portal
     */
    public function iShouldFindACourseInThePortal($courseTitle)
    {
        $course = $this->getCourseManager()->findOneByTitle($courseTitle);
        assertTrue($course instanceof Course);
    }

    /**
     * @Then I should not find a course :arg1 in the portal
     */
    public function iShouldNotFindACourseInThePortal($courseTitle)
    {
        $course = $this->getCourseManager()->findOneByTitle($courseTitle);
        assertNull($course);
    }

}
