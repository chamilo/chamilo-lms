<?php

use Behat\MinkExtension\Context\MinkContext;

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
        $this->fillField('login', 'acostea');
        $this->fillField('password', 'acostea');
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

    /**
     * @Then /^I fill in ckeditor field "([^"]*)" with "([^"]*)"$/
     */
    public function iFillInWysiwygOnFieldWith($locator, $value)
    {
        // Just in case wait that ckeditor is loaded
        $this->getSession()->wait(2000);

        $el = $this->getSession()->getPage()->findField($locator);
        $fieldId = $el->getAttribute('id');

        if (empty($fieldId)) {
            throw new Exception(
                'Could not find an id for field with locator: '.$locator
            );
        }

        $this->getSession()->executeScript(
            "CKEDITOR.instances[\"$fieldId\"].setData(\"$value\");"
        );
    }

    /**
     * @Given /^I fill hidden field "([^"]*)" with "([^"]*)"$/
     */
    public function iFillHiddenFieldWith($field, $value)
    {
        $this->getSession()->getPage()->find(
            'css',
            'input[name="'.$field.'"]'
        )->setValue($value);
    }

    /**
     * @When /^(?:|I )fill in select2 input "(?P<field>(?:[^"]|\\")*)" with id "(?P<id>(?:[^"]|\\")*)" and value "(?P<value>(?:[^"]|\\")*)"$/
     */
    public function iFillInSelectInputWithAndSelect($field, $id, $value)
    {
        $this->getSession()->executeScript("$('$field').select2({data : [{id: $id, text: '$value'}]});");
    }

    /**
     * @When /^(?:|I )confirm the popup$/
     */
    public function confirmPopup()
    {
        // See
        // https://gist.github.com/blazarecki/2888851
        /** @var \Behat\Mink\Driver\Selenium2Driver $driver Needed because no cross-driver way yet */
        $this->getSession()->getDriver()->getWebDriverSession()->accept_alert();
    }

     /**
     * @When /^(?:|I )fill in select bootstrap input "(?P<field>(?:[^"]|\\")*)" with "(?P<value>(?:[^"]|\\")*)" and select "(?P<entry>(?:[^"]|\\")*)"$/
     */
    public function iFillInSelectBootstrapInputWithAndSelect($field, $value, $entry)
    {
        $page = $this->getSession()->getPage();
        $inputField = $page->find('css', $field);
        if (!$inputField) {
            throw new \Exception('No field found');
        }

        $choice = $inputField->getParent()->find('css', '.bootstrap-select');
        if (!$choice) {
            throw new \Exception('No select bootstrap choice found');
        }
        $choice->press();

        $selectInput = $inputField->getParent()->find('css', '.bootstrap-select .form-control');
        if (!$selectInput) {
            throw new \Exception('No input found');
        }

        $selectInput->setValue($value);
        $this->getSession()->wait(3000);

        $chosenResults = $inputField->getParent()->findAll('css', '.dropdown-menu inner li');
        foreach ($chosenResults as $result) {
            //$option = $result->find('css', '.text');
            if ($result->getText() == $entry) {
                $result->click();
                break;
            }
        }
    }

    /**
     * @When /^(?:|I )fill in select bootstrap static input "(?P<field>(?:[^"]|\\")*)" select "(?P<value>(?:[^"]|\\")*)"$/
     */
    public function iFillInSelectStaticBootstrapInputWithAndSelect($field, $value)
    {
        $this->getSession()->wait(1000);
        $this->getSession()->executeScript("
            $(function() {
                $('$field').selectpicker('val', '$value');
            });
        ");
    }

    /**
     * @When /^wait for the page to be loaded$/
     */
    public function waitForThePageToBeLoaded()
    {
        //$this->getSession()->wait(10000, "document.readyState === 'complete'");
        $this->getSession()->wait(3000);
    }

    /**
     * @When /^I check the "([^"]*)" radio button$/
     */
    public function iCheckTheRadioButton($radioLabel)
    {
        $radioButton = $this->getSession()->getPage()->findField($radioLabel);
        if (null === $radioButton) {
            throw new Exception("Cannot find radio button ".$radioLabel);
        }
        //$value = $radioButton->getAttribute('value');
        $this->getSession()->getDriver()->click($radioButton->getXPath());
    }

    /**
     * @When /^I check radio button with label "([^"]*)"$/
     */
    public function iCheckTheRadioButtonWithLabel($label)
    {
        $this->getSession()->executeScript("
            $(function() {
                $(':contains(\$label\")').parent().find('input').prop('checked', true);
            });
        ");
    }

     /**
     * @When /^I press advanced settings$/
     */
    public function iSelectFromSelectWithLabel()
    {
        $this->pressButton('Advanced settings');
    }

     /**
     * Clicks link with specified id|title|alt|text
     * Example: When I follow "Log In"
     * Example: And I follow "Log In"
     *
     * @When /^(?:|I )focus "(?P<link>(?:[^"]|\\")*)"$/
     */
    public function focus($input)
    {
        $input = $this->getSession()->getPage()->findField($input);
        $input->focus();
    }

    /**
     * @Given /^I check the "([^"]*)" radio button with "([^"]*)" value$/
     */
    public function iCheckTheRadioButtonWithValue($element, $value)
    {
        $this->getSession()->executeScript("
            $(function() {
                $('input[type=\"radio\"][name=".$element."][value=".$value."]').prop('checked', true);
            });
        ");

        return true;
    }
}
