<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Context\Step\Given,
    Behat\Behat\Exception\PendingException,
    Behat\Behat\Event\SuiteEvent;

use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

use Behat\MinkExtension\Context\MinkContext;

//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//

/**
 * Features context. (MinkContext extends BehatContext)
 */
class FeatureContext extends MinkContext
{
    /**
     * Initializes context.
     * Every scenario gets its own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        // Initialize your context here
    }
    /**
     * @Given /^I am a platform administrator$/
     */
    public function iAmAPlatformAdministrator()
    {
        return array(
            new Given('I am on "/index.php?logout=logout"'),
            new Given('I am on homepage'),
            new Given('I fill in "login" with "admin"'),
            new Given('I fill in "password" with "admin"'),
            new Given('I press "submitAuth"')
        );
    }
    /**
     * @Given /^I am a session administrator$/
     */
    public function iAmASessionAdministrator()
    {
        return array(
            new Given('I am on "/index.php?logout=logout"'),
            new Given('I am on homepage'),
            new Given('I fill in "login" with "amaurichard"'),
            new Given('I fill in "password" with "amaurichard"'),
            new Given('I press "submitAuth"')
        );
    }
    /**
     * @Given /^I am a teacher$/
     */
    public function iAmATeacher()
    {
        return array(
            new Given('I am on "/index.php?logout=logout"'),
            new Given('I am on homepage'),
            new Given('I fill in "login" with "mmosquera"'),
            new Given('I fill in "password" with "mmosquera"'),
            new Given('I press "submitAuth"')
        );
    }
    /**
     * @Given /^I am a student$/
     */
    public function iAmAStudent()
    {
        return array(
            new Given('I am on "/index.php?logout=logout"'),
            new Given('I am on homepage'),
            new Given('I fill in "login" with "mbrandybuck"'),
            new Given('I fill in "password" with "mbrandybuck"'),
            new Given('I press "submitAuth"')
        );
    }
    /**
     * @Given /^I am an HR manager$/
     */
    public function iAmAnHR()
    {
        return array(
            new Given('I am on "/index.php?logout=logout"'),
            new Given('I am on homepage'),
            new Given('I fill in "login" with "ptook"'),
            new Given('I fill in "password" with "ptook"'),
            new Given('I press "submitAuth"')
        );
    }
    /**
     * @Given /^I am a student boss$/
     */
    public function iAmAStudentBoss()
    {
        return array(
            new Given('I am on "/index.php?logout=logout"'),
            new Given('I am on homepage'),
            new Given('I fill in "login" with "abaggins"'),
            new Given('I fill in "password" with "abaggins"'),
            new Given('I press "submitAuth"')
        );
    }
    /**
     * @Given /^I am an invitee$/
     */
    public function iAmAnInvitee()
    {
        return array(
            new Given('I am on "/index.php?logout=logout"'),
            new Given('I am on homepage'),
            new Given('I fill in "login" with "bproudfoot"'),
            new Given('I fill in "password" with "bproudfoot"'),
            new Given('I press "submitAuth"')
        );
    }
    /**
     * @Given /^course TEMP exists$/
     */
    public function courseTempExists()
    {
        return array(
            new Given('I am a platform administrator'),
            new Given('I am on "/main/admin/course_list.php?keyword=TEMP"'),
            new Given('I should see "TEMP"'),
        );
    }
    /**
     * @Given /^course TEMP is deleted$/
     */
    public function courseTempIsDeleted()
    {
        return array(
            new Given('I am a platform administrator'),
            new Given('I am on "http://my.chamilo110.net/main/admin/course_list.php?keyword=TEMP"'),
            new Given('I follow "Delete"')
        );
    }
}
