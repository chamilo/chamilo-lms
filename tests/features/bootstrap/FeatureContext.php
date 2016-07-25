<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\MinkExtension\Context\MinkContext;

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
     */
    public function __construct()
    {
    }

    /**
     * @Given /^I am a platform administrator$/
     */
    public function iAmAPlatformAdministrator()
    {
        $this->visit('/index.php?logout=logout');
        $this->iAmOnHomepage();
        $this->fillField('login', 'admin');
        $this->fillField('password', 'admin');
        $this->pressButton('submitAuth');
        $this->getSession()->back();
    }
    /**
     * @Given /^I am a session administrator$/
     */
    public function iAmASessionAdministrator()
    {
        $this->visit('/index.php?logout=logout');
        $this->iAmOnHomepage();
        $this->fillFields(new \Behat\Gherkin\Node\TableNode([
            ['login', 'amaurichard'],
            ['password', 'amaurichard']
        ]));
        $this->pressButton('submitAuth');
    }
    /**
     * @Given /^I am a teacher$/
     */
    public function iAmATeacher()
    {
        $this->visit('/index.php?logout=logout');
        $this->iAmOnHomepage();
        $this->fillField('login', 'mmosquera');
        $this->fillField('password', 'mmosquera');
        $this->pressButton('submitAuth');
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
        $this->visit('/index.php?logout=logout');
        $this->iAmOnHomepage();
        $this->fillField('login', 'mbrandybuck');
        $this->fillField('password', 'mbrandybuck');
        $this->pressButton('submitAuth');
    }
    /**
     * @Given /^I am an HR manager$/
     */
    public function iAmAnHR()
    {
        $this->visit('/index.php?logout=logout');
        $this->iAmOnHomepage();
        $this->fillField('login', 'ptook');
        $this->fillField('password', 'ptook');
        $this->pressButton('submitAuth');
    }
    /**
     * @Given /^I am a student boss$/
     */
    public function iAmAStudentBoss()
    {
        $this->visit('/index.php?logout=logout');
        $this->iAmOnHomepage();
        $this->fillField('login', 'abaggins');
        $this->fillField('password', 'abaggins');
        $this->pressButton('submitAuth');
    }
    /**
     * @Given /^I am an invitee$/
     */
    public function iAmAnInvitee()
    {
        $this->visit('/index.php?logout=logout');
        $this->iAmOnHomepage();
        $this->fillField('login', 'bproudfoot');
        $this->fillField('password', 'bproudfoot');
        $this->pressButton('submitAuth');
    }
    /**
     * @Given /^course "([^"]*)" exists$/
     */
    public function courseExists($argument)
    {
        $this->iAmAPlatformAdministrator();
        $this->visit('/main/admin/course_list.php?keyword=' . $argument);
        $this->assertPageContainsText($argument);
    }
    /**
     * @Given /^course "([^"]*)" is deleted$/
     */
    public function courseIsDeleted($argument)
    {
        $this->iAmAPlatformAdministrator();
        $this->visit('/main/admin/course_list.php?keyword=' . $argument);
        $this->clickLink('Delete');
    }
    /**
     * @Given /^I am in course "([^"]*)"$/
     * @Todo redefine function to be different from I am on course TEMP homepage
     */
    public function iAmInCourse($argument)
    {
        $this->visit('/main/course_home/course_home.php?cDir=' . $argument);
        $this->assertElementNotOnPage('.alert-danger');
    }
    /**
     * @Given /^I am on course "([^"]*)" homepage$/
     */
    public function iAmOnCourseXHomepage($argument)
    {
        $this->visit('/main/course_home/course_home.php?cDir=' . $argument);
        $this->assertElementNotOnPage('.alert-danger');
    }
    /**
     * @Given /^I am a "([^"]*)" user$/
     */
    public function iAmAXUser($argument)
    {
        $this->visit('/main/auth/profile.php');
        $this->assertFieldContains('language', $argument);
    }

    /**
     * @Given /^I am logged as "([^"]*)"$/
     */
    public function iAmLoggedAs($username)
    {
        $this->visit('/index.php?logout=logout');
        $this->iAmOnHomepage();
        $this->fillFields(new \Behat\Gherkin\Node\TableNode([
            ['login', $username],
            ['password', $username]
        ]));
        $this->pressButton('submitAuth');
    }

    /**
     * @Given /^I have a friend named "([^"]*)" with id "([^"]*)"$/
     */
    public function iHaveAFriend($friendUsername, $friendId)
    {
        $adminId = 1;
        $friendId = $friendId;
        $friendUsername = $friendUsername;

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

        $this->iAmAPlatformAdministrator();
        $this->visit($sendInvitationURL);
        $this->iAmLoggedAs($friendUsername);
        $this->visit($acceptInvitationURL);
        $this->iAmAPlatformAdministrator();
    }

    /**
     * @Given /^I have a public password-protected course named "([^"]*)" with password "([^"]*)"$/
     */
    public function iHaveAPublicPasswordProtectedCourse($code, $password)
    {
        $this->visit('/main/admin/course_add.php');
        $this->fillFields(new \Behat\Gherkin\Node\TableNode([
            ['title', 'Password Protected'],
            ['visual_code', $code],
            ['visibility', 3]
        ]));
        $this->pressButton('submit');
        $this->visit('/main/course_info/infocours.php?cidReq=' . $code);
        $this->assertPageContainsText('Course registration password');
        $this->fillField('course_registration_password', $password);
        $this->pressButton('submit_save');
        $this->assertFieldContains('course_registration_password', $password);
    }

    /**
     * @Given /^I am not logged$/
     */
    public function iAmNotLogged()
    {
        $this->visit('/index.php?logout=logout');
        $this->visit('I am on homepage');
    }

    /**
     * @When /^I invite to a friend with id "([^"]*)" to a social group with id "([^"]*)"$/
     */
    public function iInviteAFrienToASocialGroup($friendId, $groupId)
    {
        $this->visit('/main/social/group_invitation.php?id=' . $groupId);
        $this->fillField('invitation[]', $friendId);
        $this->pressButton('submit');
    }

    /**
     * Sometimes the top admin toolbar has form buttons
     * that conflicts with the main page forms so we need
     * to disable it
     * @Given /^Admin top bar is disabled$/
     */
    public function adminTopBarIsDisabled()
    {
        $this->iAmAPlatformAdministrator();
        $this->visit('/main/admin/settings.php');
        $this->fillField('search_field', 'show_admin_toolbar');
        $this->pressButton('submit_button');
        $this->selectOption('show_admin_toolbar', 'do_not_show');
        $this->pressButton('submit');
    }
    /**
     * @Given /^Admin top bar is enabled$/
     */
    public function adminTopBarIsEnabled()
    {
        $this->iAmAPlatformAdministrator();
        $this->visit('/main/admin/settings.php');
        $this->fillField('search_field', 'show_admin_toolbar');
        $this->pressButton('submit_button');
        $this->selectOption('show_admin_toolbar', 'show_to_admin_and_teachers');
        $this->pressButton('submit');
    }

    /**
     * @Given /^I am on the social group members page with id "([^"]*)"$/
     */
    public function iAmOnSocialGroupMembersPageWithId($groupId)
    {
        $this->visit('/main/social/group_view.php?id=' . $groupId);
    }

    /**
     * @When /^I try delete a friend with id "([^"]*)" from the social group with id "([^"]*)"$/
     */
    public function iTryDeleteAFriendFromSocialGroup($friendId, $groupId)
    {
        $this->visit('/main/social/group_members.php?' . http_build_query([
            'id' => $groupId,
            'u' => $friendId,
            'action' => 'delete'
        ]));
    }
}
