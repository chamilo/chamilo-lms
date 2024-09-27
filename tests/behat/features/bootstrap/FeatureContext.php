<?php

use Behat\Gherkin\Node\TableNode;
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
        $this->iAmOnHomepage();
        $this->fillField('login', 'admin');
        $this->fillField('password', 'admin');
        $this->pressButton('submitAuth');
        $this->getSession()->back();
    }

    /**
     * @Given /^I am a teacher$/
     */
    public function iAmATeacher()
    {
        $this->iAmOnHomepage();
        $this->fillField('login', 'mmosquera');
        $this->fillField('password', 'mmosquera');
        $this->pressButton('submitAuth');
    }

    /**
     * @Given /^I am a student$/
     */
    public function iAmAStudent()
    {
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
        $this->visit('/main/admin/course_list.php?keyword='.$argument);
        $this->assertPageContainsText($argument);
    }

    /**
     * @Given /^I am on course "([^"]*)" homepage$/
     */
    public function iAmOnCourseXHomepage($courseCode)
    {
        $this->visit('/courses/'.$courseCode.'/index.php');
        $this->assertElementNotOnPage('.alert-danger');
    }

    /**
     * @Given /^I am on course "([^"]*)" homepage in session "([^"]*)"$/
     */
    public function iAmOnCourseXHomepageInSessionY($courseCode, $sessionName)
    {
        $this->visit('/main/course_home/redirect.php?cidReq='.$courseCode.'&session_name='.$sessionName);
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
        $this->fillFields(
            new TableNode(
                [
                    ['login', $username],
                    ['password', $username],
                ]
            )
        );
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

        $sendInvitationURL = '/main/inc/ajax/message.ajax.php?'.
            http_build_query(
                [
                    'a' => 'send_invitation',
                    'user_id' => $friendId,
                    'content' => 'Add me',
                ]
            );
        $acceptInvitationURL = '/main/inc/ajax/social.ajax.php?'.
            http_build_query(
                [
                    'a' => 'add_friend',
                    'friend_id' => $adminId,
                    'is_my_friend' => 'friend',
                    'invitation_sec_token' => Security::get_token('invitation'),
                ]
            );

        $this->visit('/index.php?logout=logout');
        $this->iAmAPlatformAdministrator();
        $this->visit($sendInvitationURL);
        $this->iAmLoggedAs($friendUsername);
        $this->visit($acceptInvitationURL);
        $this->visit('/index.php?logout=logout');
        $this->iAmAPlatformAdministrator();
    }

    /**
     * @Given /^I have a public password-protected course named "([^"]*)" with password "([^"]*)"$/
     */
    public function iHaveAPublicPasswordProtectedCourse($code, $password)
    {
        $this->visit('/main/admin/course_add.php');
        $this->fillFields(
            new TableNode(
                [
                    ['title', 'Password Protected'],
                    ['visual_code', $code],
                    ['visibility', 3],
                ]
            )
        );
        $this->pressButton('submit');
        $this->visit('/main/course_info/infocours.php?cidReq='.$code);
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
    public function iInviteAFriendToASocialGroup($friendId, $groupId)
    {
        $this->visit('/main/social/group_invitation.php?id='.$groupId);
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
        $this->visit('/main/social/group_view.php?id='.$groupId);
    }

    /**
     * @When /^I try delete a friend with id "([^"]*)" from the social group with id "([^"]*)"$/
     */
    public function iTryDeleteAFriendFromSocialGroup($friendId, $groupId)
    {
        $this->visit(
            '/main/social/group_members.php?'.http_build_query(
                [
                    'id' => $groupId,
                    'u' => $friendId,
                    'action' => 'delete',
                ]
            )
        );
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
     * @Then /^I fill the only ckeditor in the page with "([^"]*)"$/
     */
    public function iFillTheOnlyEditorInThePage($value)
    {
        // Just in case wait that ckeditor is loaded
        $this->getSession()->wait(2000);


        $this->getSession()->executeScript(
            "
                var textarea = $('textarea');
                var id = textarea.attr('id');
                CKEDITOR.instances[id].setData(\"$value\");
                "
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
     * @When /^(?:|I )fill in select bootstrap static by text "(?P<field>(?:[^"]|\\")*)" select "(?P<value>(?:[^"]|\\")*)"$/
     */
    public function iFillInSelectStaticBootstrapInputWithAndSelectByText($field, $value)
    {
        $this->getSession()->wait(1000);
        $this->getSession()->executeScript("
           $('$field > option').each(function(index, option) {
                if (option.text == '$value') {
                    $('$field').selectpicker('val', option.value);
                }
            });
        ");
    }

    /**
     * @When /^(?:|I )fill in select "(?P<field>(?:[^"]|\\")*)" with option value "(?P<value>(?:[^"]|\\")*)" with class "(?P<id>(?:[^"]|\\")*)"$/
     */
    public function iFillInSelectWithOptionValue($field, $value, $class)
    {
        $this->getSession()->wait(1000);
        $this->getSession()->executeScript("
            var input = $('$field').filter('$class');
            var id = input.attr('id');
            var input = $('#'+id);
            input.val($value);
        ");
    }

    /**
     * @When /^wait for the page to be loaded$/
     */
    public function waitForThePageToBeLoaded()
    {
        $this->getSession()->wait(2000);
    }

    /**
     * @When /^wait very long for the page to be loaded$/
     */
    public function waitVeryLongForThePageToBeLoaded()
    {
        //$this->getSession()->wait(10000, "document.readyState === 'complete'");
        $this->getSession()->wait(4000);
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

    /**
     * @Given /^I check the "([^"]*)" radio button selector$/
     */
    public function iCheckTheRadioButtonBasedInSelector($element)
    {
        $this->getSession()->executeScript("
            $(function() {
                $('$element').prop('checked', true);
            });
        ");

        return true;
    }

    /**
     * @Then /^I should see an icon with title "([^"]*)"$/
     */
    public function iShouldSeeAnIconWithTitle($value)
    {
        $el = $this->getSession()->getPage()->find('xpath', "//img[@title='$value']");
        if (null === $el) {
            throw new Exception(
                'Could not find an icon with title: '.$value
            );
        }
        return true;
    }
    /**
     * @Then /^I should not see an icon with title "([^"]*)"$/
     */
    public function iShouldNotSeeAnIconWithTitle($value)
    {
        $el = $this->getSession()->getPage()->find('xpath', "//img[@title='$value']");
        if (null === $el) {
            return true;
        }
        return false;
    }


    /**
     * @Then /^I save current URL with name "([^"]*)"$/
     */
    public function saveUrlWithName($name)
    {

        $url = $this->getSession()->getCurrentUrl();
        $this->getSession()->setCookie($name, $url);
    }

    /**
     * @Then /^I visit URL saved with name "([^"]*)"$/
     */
    public function visitSavedUrlWithName($name)
    {
        $url = $this->getSession()->getCookie($name);
        echo $url;
        if (empty($url)) {
            throw new Exception("Url with name: $name not found");
        }
        $this->visit($url);
    }

    /**
     * Example: Then I should see the table "#category_results":
     *               | Categories    | Absolute score | Relative score |
     *               | Categoryname2 | 50 / 70        | 71.43 %         |
     *               | Categoryname1 | 60 / 60        | 100 %           |
     *
     * @Then /^I should see the table "([^"]*)":$/
     *
     * @param string    $tableId
     * @param TableNode $tableData
     *
     * @throws Exception
     */
    public function assertPageContainsTable($tableId, TableNode $tableData)
    {
        $table = $this->getSession()->getPage()->find('css', $tableId);
        $rows = $tableData->getRowsHash();
        $i = 1;

        $right = array_keys($rows);

        foreach ($right as $text) {
            $cell = $table->find('css', 'tr:nth-child('.$i.') :nth-child(1)');
            $i++;

            if (!$cell) {
                throw new Exception('Cell not found.');
            }

            if ($cell->getText() != $text) {
                throw new Exception('Table text not found.');
            }
        }

        $i = 1;

        foreach ($rows as $field => $cols) {
            if (is_array($cols)) {
                $j = 2;

                foreach ($cols as $col) {
                    $cell = $table->find('css', 'tr:nth-child('.$i.') :nth-child('.$j.')');
                    $j++;

                    if (!$cell) {
                        throw new Exception('Cell not found.');
                    }

                    if ($cell->getText() != $col) {
                        throw new Exception('Table text not found. Found "'.$cell->getText().'" <> "'.$col.'"');
                    }
                }
            } else {
                $cell = $table->find('css', 'tr:nth-child('.$i.') :nth-child(2)');

                if (!$cell) {
                    throw new Exception('Cell not found.');
                }

                if ($cell->getText() != $cols) {
                    throw new Exception('Table text not found. Found "'.$cell->getText().'" <> "'.$cols.'"');
                }
            }

            $i++;
        }
    }
}
