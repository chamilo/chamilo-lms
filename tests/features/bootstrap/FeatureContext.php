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
     * @Given /^I am a teacher in course "([^"]*)"$/
     * @Todo implement
     */
    public function iAmATeacherInCourse($course)
    {
        //$sql = "SELECT * FROM course_rel_user WHERE c_id = X AND user_id = ";
        //$result = ...
        //if ($result !== false) { ... }
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
     * @Given /^course "([^"]*)" exists$/
     */
    public function courseExists($argument)
    {
        return array(
            new Given('I am a platform administrator'),
            new Given('I am on "/main/admin/course_list.php?keyword=' . $argument . '"'),
            new Given('I should see "' . $argument . '"'),
        );
    }
    /**
     * @Given /^course "([^"]*)" is deleted$/
     */
    public function courseIsDeleted($argument)
    {
        return array(
            new Given('I am a platform administrator'),
            new Given('I am on "/main/admin/course_list.php?keyword=' . $argument . '"'),
            new Given('I follow "Delete"')
        );
    }
    /**
     * @Given /^I am in course "([^"]*)"$/
     * @Todo redefine function to be different from I am on course TEMP homepage
     */
    public function iAmInCourse($argument)
    {
        return array(
            new Given('I am on "/main/course_home/course_home.php?cDir=' . $argument . '"'),
            new Given('I should not see an ".alert-danger" element')
        );
    }
    /**
     * @Given /^I am on course "([^"]*)" homepage$/
     */
    public function iAmOnCourseXHomepage($argument)
    {
        return array(
            new Given('I am on "/main/course_home/course_home.php?cDir=' . $argument . '"'),
            new Given('I should not see an ".alert-danger" element')
        );
    }
    /**
     * @Given /^I am a "([^"]*)" user$/
     */
    public function iAmAXUser($argument)
    {
        return array(
            new Given('I am on "/main/auth/profile.php"'),
            new Given('the "language" field should contain "' . $argument . '"')
        );
    }

    /**
     * @Given /^I am logged as "([^"]*)"$/
     */
    public function iAmLoggedAs($username)
    {
        return [
            new Given('I am on "/index.php?logout=logout"'),
            new Given('I am on homepage'),
            new Given('I fill in "login" with "' . $username . '"'),
            new Given('I fill in "password" with "' . $username . '"'),
            new Given('I press "submitAuth"')
        ];
    }

    /**
     * @Given /^I have a friend$/
     */
    public function iHaveAFriend()
    {
        $adminId = 1;
        $friendId = 11;
        $friendUsername = 'fbaggins';

        $sendInvitationURL = '/main/inc/ajax/message.ajax.php?' . http_build_query([
            'a' => 'send_invitation',
            'user_id' => $friendId,
            'content' => 'Add me'
        ]);
        $acceptInvitationURL = '/main/inc/ajax/social.ajax.php?' . http_build_query([
            'a' => 'add_friend',
            'friend_id' => $adminId,
            'is_my_friend' => 'friend'
        ]);

        return array(
            new Given('I am a platform administrator'),
            new Given('I am on "' . $sendInvitationURL . '"'),
            new Given('I am logged as "' . $friendUsername . '"'),
            new Given('I am on "' . $acceptInvitationURL . '"'),
            new Given('I am a platform administrator')
        );
    }

    /**
     * @Given /^I have a public password-protected course named "([^"]*)" with password "([^"]*)"$/
     */
    public function iHaveAPublicPasswordProtectedCourse($code, $password)
    {
        return [
            new Given('I am on "/main/admin/course_add.php"'),
            new Given('I fill in "title" with "Password Protected"'),
            new Given('I fill in "visual_code" with "' . $code . '"'),
            new Given('I fill in "visibility" with "3"'),
            new Given('I press "submit"'),
            new Given('I am on "/main/course_info/infocours.php?cidReq=' . $code . '"'),
            new Given('I should see "Course registration password"'),
            new Given('I fill in "course_registration_password" with "' . $password . '"'),
            new Given('I press "submit_save"'),
            new Given('the "course_registration_password" field should contain "' . $password . '"')
        ];
    }

    /**
     * @Given /^I am not logged$/
     */
    public function iAmNotLogged()
    {
        return [
            new Given('I am on "/index.php?logout=logout"'),
            new Given('I am on homepage')
        ];
    }
}
