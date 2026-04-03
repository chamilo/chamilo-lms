<?php

use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Testwork\Tester\Result\TestResult;

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
        $this->iAmLoggedAs('admin');
    }

    /**
     * @Given /^I am a teacher$/
     */
    public function iAmATeacher()
    {
        $this->iAmLoggedAs('mmosquera');
    }

    /**
     * @Given /^I am a student$/
     */
    public function iAmAStudent()
    {
        $this->iAmLoggedAs('acostea');
    }

    /**
     * @Given /^I am an HR manager$/
     */
    public function iAmAnHR()
    {
        $this->iAmLoggedAs('ptook');
    }

    /**
     * @Given /^I am a student boss$/
     */
    public function iAmAStudentBoss()
    {
        $this->iAmLoggedAs('abaggins');
    }

    /**
     * @Given /^I am an invitee$/
     */
    public function iAmAnInvitee()
    {
        $this->iAmLoggedAs('bproudfoot');
    }

    /**
     * @Given /^course "([^"]*)" exists$/
     */
    public function courseExists($argument)
    {
        $this->iAmAPlatformAdministrator();
        $this->visit('/admin/course-list?keyword='.$argument);
        $this->assertPageContainsText($argument);
    }

    /**
     * @Given /^course "([^"]*)" is deleted$/
     */
    public function courseIsDeleted($argument): void
    {
        $this->iAmAPlatformAdministrator();
        $this->visit('/admin/course-list?keyword='.$argument);
        $this->clickLink('Delete');
    }

    /**
     * @Given /^I am on course "([^"]*)" homepage$/
     * @deprecated Use iAmOnTheHomepageOfCourseX instead
     */
    public function iAmOnCourseXHomepage($courseCode): void
    {
        $this->visit('/main/course_home/redirect.php?cidReq='.$courseCode);
        $this->waitForThePageToBeLoaded();
        //$this->visit('/courses/'.$courseCode.'/index.php');
        $this->assertElementNotOnPage('.alert-danger');
    }

    /**
     * @Given /^I am on course "([^"]*)" homepage in session "([^"]*)"$/
     * @deprecated Use iAmOnTheHomepageOfCourseXInSessionY instead
     */
    public function iAmOnCourseXHomepageInSessionY($courseCode, $sessionName): void
    {
        $this->visit('/main/course_home/redirect.php?cidReq='.$courseCode.'&session_name='.$sessionName);
        $this->waitForThePageToBeLoaded();
        $this->assertElementNotOnPage('.alert-danger');
    }

    /**
     * @Given /^I am on the homepage of course "([^"]*)"$/
     */
    public function iAmOnTheHomepageOfCourseX($courseId): void
    {
        $this->visit('/course/'.$courseId.'/home');
        $this->waitForThePageToBeLoaded();
        //$this->visit('/courses/'.$courseCode.'/index.php');
        $this->assertElementNotOnPage('.alert-danger');
    }

    /**
     * @Given /^I am on the homepage of course "([^"]*) in session "([^"]*)"$/
     */
    public function iAmOnTheHomepageOfCourseXInSessionY($courseId, $sessionId): void
    {
        $this->visit('/course/'.$courseId.'&sid='.$sessionId);
        $this->waitForThePageToBeLoaded();
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
        //$this->visit('/logout');
        $this->visit('/login');
        $this->waitForThePageToBeLoaded();
        $this->fillField('login', $username);
        $this->fillField('password', $username);
        $this->pressButton('Sign in');
        $this->waitForThePageToBeLoaded();
        //$this->waitForThePageToBeLoaded();
    }

    /**
     * Checks, that element with specified CSS doesn't exist on page
     *
     * @Then /^(?:|I )should not see an error$/
     */
    public function iShouldNotSeeAnError()
    {
        $this->assertSession()->pageTextNotContains('Internal server error');
        $this->assertSession()->pageTextNotContains('error');
        $el = $this->getSession()->getPage()->find(
            'css',
            '.alert-danger'
        );
        if (null !== $el) {
            $this->assertSession()->elementAttributeContains('css', '.alert-danger', 'style', 'display:none;');
        } else {
            $this->assertSession()->elementNotExists('css', '.alert-danger');
        }
        $this->assertSession()->elementNotExists('css', '.p-message-error');
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
                ]
            );

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
        $this->visit('/logout');
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
     * @Then /^I fill in editor field "([^"]*)" with "([^"]*)"$/
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
            "setContentFromEditor(\"$fieldId\", \"$value\");"
        );
    }

    /**
     * @Then /^I fill in tinymce field "([^"]*)" with "([^"]*)"$/
     */
    public function iFillInTinyMceOnFieldWith($locator, $value)
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
            "tinymce.get(\"$fieldId\").getBody().innerHTML = \"$value\";"
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
     * @When /^(?:|I )fill in ajax select2 input "(?P<field>(?:[^"]|\\")*)" with id "(?P<id>(?:[^"]|\\")*)" and value "(?P<value>(?:[^"]|\\")*)"$/
     */
    public function iFillInAjaxSelectInputWithAndSelect($field, $id, $value)
    {
        $this->getSession()->executeScript("
            var newOption = new Option('$value', $id, true, true);
            $('$field').append(newOption).trigger('change');
        ");
    }

    /**
     * @When /^(?:|I )confirm the popup$/
     */
    public function confirmPopup()
    {
       $session = $this->getSession();
        // 1) accept_alert() (alert native)
        try {
            $driver = $session->getDriver();

                try {
                    $driver->getWebDriverSession()->accept_alert();
                    return;
                } catch (\Exception $e) {}

        } catch (\Exception $e) {
            // ignore
        }

        // wait for the HTML modal
        $session->wait(5000, "document.querySelector('.swal2-container') !== null");

        // JS: attempt to click a visible confirmation button inside the modal
        $js = <<<'JS'
        (function(){
         function isVisible(el){
         if(!el) return false;
         var rect = el.getBoundingClientRect();
         return !!(rect.width || rect.height) && window.getComputedStyle(el).visibility !== 'hidden' && window.getComputedStyle(el).display !== 'none';
         }
         function clickEl(el){
         if(!el) return false;
         try { el.style.pointerEvents = 'auto'; el.style.zIndex = 999999; } catch(e){}
        try { if(el.focus) el.focus(); el.click(); return true; } catch(e){
        }
        }
       // attempt to click a visible confirmation button inside the modal
       var modal = document.querySelector('.swal2-container');

       var el = modal.querySelector('.swal2-confirm');
       if (el && isVisible(el)) {
       if (clickEl(el)) return true;
       }
       return false;
       })();
       JS;
        try {
            $clicked = (bool) $session->executeScript($js);
            if ($clicked)
                return;
        } catch (\Exception $e) {
            throw new \Exception('confirmPopup: no confirmation button found or clickable');
        }
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
     * @When /^(?:|I )wait for the page to be loaded$/
     */
    public function waitForThePageToBeLoaded()
    {
        $this->getSession()->wait(4000);
    }

    /**
     * @When /^(?:|I )wait very long for the page to be loaded$/
     */
    public function waitVeryLongForThePageToBeLoaded()
    {
        //$this->getSession()->wait(10000, "document.readyState === 'complete'");
        $this->getSession()->wait(7000);
    }

    /**
     * @When /^(?:|I )wait for the page to be loaded when ready$/
     */
    public function waitForThePageToBeLoadedWhenReady()
    {
        $this->getSession()->wait(9000, "document.readyState === 'complete'");
    }

    /**
     * @When /^I wait for the element "([^"]*)" to appear$/
     */
    public function iWaitForElementToAppear($selector): void
    {
        $escaped = addslashes($selector);
        $this->getSession()->wait(10000, "document.querySelector('{$escaped}') !== null");
    }

    /**
     * @When /^I wait up to 20 seconds for the element "([^"]*)" to appear$/
     */
    public function iWait20SecondsForElementToAppear($selector): void
    {
        $escaped = addslashes($selector);
        $this->getSession()->wait(20000, "document.querySelector('{$escaped}') !== null");
    }


    /**
     * @When /^(?:|I )wait one minute for the page to be loaded$/
     */
    public function waitOneMinuteForThePageToBeLoaded()
    {
        $this->getSession()->wait(60000);
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
     * @Given /^I am a student subscribed to session "([^"]*)"$/
     *
     * @param string$sessionName
     */
    public function iAmStudentSubscribedToXSession($sessionName)
    {
        $this->iAmAPlatformAdministrator();
        $this->visit('/main/session/session_add.php');
        $this->fillField('name', $sessionName);
        $this->pressButton('Next step');
        $this->selectOption('NoSessionCoursesList[]', 'TEMP (TEMP)');
        $this->pressButton('add_course');
        $this->pressButton('Next step');
        $this->assertPageContainsText('Update successful');
        $this->fillField('user_to_add', 'acostea');
        $this->waitForThePageToBeLoaded();
        $this->clickLink('Costea Andrea (acostea)');
        $this->pressButton('Finish session creation');
        $this->assertPageContainsText('Session overview');
        //$this->assertPageContainsText('Costea Andrea (acostea)');
        $this->iAmAStudent();
    }

    /**
     * Example: Then I should see the table "#category_results":
     *               | Categories    | Absolute score | Relative score |
     *               | Categoryname2 | 50 / 70        | 71.43%         |
     *               | Categoryname1 | 60 / 60        | 100%           |
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

    /**
     * @Then I click the :selector element
     */
    public function iClickTheElement($selector)
    {
        $page = $this->getSession()->getPage();
        $element = $page->find('css', $selector);

        $element->click();
    }

    /**
     * Check all checkboxes whose name contains the given partial name.
     * Useful for checkbox groups like form[show_tabs][] where IDs are dynamic.
     *
     * @Then I check all checkboxes with name containing :partialName
     */
    public function iCheckAllCheckboxesWithNameContaining($partialName)
    {
        $js = <<<JS
(function() {
    var checkboxes = document.querySelectorAll('input[type="checkbox"][name*="$partialName"]');
    checkboxes.forEach(function(cb) { if (!cb.checked) cb.click(); });
    return checkboxes.length;
})();
JS;
        $count = $this->getSession()->evaluateScript($js);
        if ($count === 0) {
            throw new \Exception(sprintf('No checkboxes found with name containing "%s"', $partialName));
        }
    }

    /**
     * Uncheck all checkboxes whose name contains the given partial name.
     *
     * @Then I uncheck all checkboxes with name containing :partialName
     */
    public function iUncheckAllCheckboxesWithNameContaining($partialName)
    {
        $js = <<<JS
(function() {
    var checkboxes = document.querySelectorAll('input[type="checkbox"][name*="$partialName"]');
    checkboxes.forEach(function(cb) { if (cb.checked) cb.click(); });
    return checkboxes.length;
})();
JS;
        $count = $this->getSession()->evaluateScript($js);
        if ($count === 0) {
            throw new \Exception(sprintf('No checkboxes found with name containing "%s"', $partialName));
        }
    }

    /**
     * @Then /^(?:I see|I should see|And I see)\s+"?([^\"]+)"?\s+in the element "([^\"]+)"$/
     */
    public function iSeeInElement($expected, $elementSelector)
    {
        $page = $this->getSession()->getPage();
        $el = null;

        // If the selector contains a comma or starts with #/. or contains CSS combinators, use CSS directly
        $useCssDirectly = (strpos($elementSelector, ',') !== false)
            || strpos($elementSelector, '#') === 0
            || strpos($elementSelector, '.') === 0
            || preg_match('/[>\s\[\]\:\,\+]/', $elementSelector);

        if ($useCssDirectly) {
            $el = $page->find('css', $elementSelector);
        } else {
            // try findById if available
            if (method_exists($page, 'findById')) {
                $el = $page->findById($elementSelector);
            }

            // fallback to CSS id
            if (!$el) {
                $el = $page->find('css', '#'.$elementSelector);
            }
        }

        if (!$el) {
            throw new \Exception(sprintf('Element with selector/id "%s" not found on the page.', $elementSelector));
        }

        // getText() returns visible text (includes children)
        $textRaw = $el->getText();

        // normalization: trim, replace NBSP and compress spaces/newlines
        $text = trim($textRaw);
        $text = str_replace("\xC2\xA0", ' ', $text); // NBSP UTF-8 -> space
        $text = preg_replace('/\s+/u', ' ', $text);

        // case-insensitive check
        if (mb_stripos($text, $expected) === false) {
            throw new \Exception(sprintf('Expected "%s" inside element "%s" but found "%s".', $expected, $elementSelector, $text));
        }

        return true;
    }

    // php

    /**
     * @When /^I zoom out to maximum$/
     */
    public function zoomOutMax()
    {
        $script = <<<'JS'
(function() {
    var scale = 0.25;
    if (typeof document.body.style.zoom !== 'undefined') {
        document.body.style.zoom = scale;
    } else {
        document.documentElement.style.transform = 'scale(' + scale + ')';
    }
})();
JS;
        $this->getSession()->executeScript($script);
        $this->getSession()->wait(300);
        return true;
    }

    /**
     * @AfterStep
     *
     * When a step fails, dump the full HTML of the page and a form-summary
     * into tests/behat/behat_debug/ so Claude (or a developer) can analyse
     * the real state of the page and find the correct selectors.
     */
    public function dumpHtmlOnFailure(AfterStepScope $scope): void
    {
        // Only act on failed steps
        if ($scope->getTestResult()->getResultCode() !== TestResult::FAILED) {
            return;
        }

        try {
            $session = $this->getSession();
            $page = $session->getPage();

            // Try to get the RENDERED DOM (after JavaScript execution) via Selenium/ChromeDriver.
            // This captures the final state including Vue.js/PrimeVue dynamic components.
            // Falls back to getContent() (server-side HTML source) if JS evaluation fails.
            try {
                $driver = $session->getDriver();
                $html = $driver->evaluateScript('return document.documentElement.outerHTML');
            } catch (\Exception $jsEx) {
                $html = $page->getContent();
            }

            if (empty($html)) {
                $html = $page->getContent();
            }
        } catch (\Exception $e) {
            // If we can't even get the page content, bail out silently
            return;
        }

        // Build output directory
        $debugDir = __DIR__ . '/../../behat_debug';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0777, true);
        }

        // Build a unique filename from scenario + step line
        $feature = basename($scope->getFeature()->getFile(), '.feature');
        $line = $scope->getStep()->getLine();
        $timestamp = date('Ymd_His');
        $baseName = "{$feature}_line{$line}_{$timestamp}";

        // --- 1) Full HTML dump ---
        file_put_contents("{$debugDir}/{$baseName}_full.html", $html);

        // --- 2) Form-summary: extract all form elements so we can see
        //         the real field names, types, ids, options, etc. ---
        $summary = $this->extractFormSummary($html, $session);
        file_put_contents("{$debugDir}/{$baseName}_form_summary.txt", $summary);

        // --- 3) Current URL ---
        try {
            $url = $session->getCurrentUrl();
        } catch (\Exception $e) {
            $url = '(unable to retrieve URL)';
        }

        // --- 4) Meta file with context ---
        $stepText = $scope->getStep()->getText();
        $meta = "BEHAT DEBUG — Step failure\n";
        $meta .= "==========================\n\n";
        $meta .= "Feature : {$scope->getFeature()->getFile()}\n";
        $meta .= "Step    : {$stepText}\n";
        $meta .= "Line    : {$line}\n";
        $meta .= "URL     : {$url}\n";
        $meta .= "Time    : {$timestamp}\n\n";
        $meta .= "Files generated:\n";
        $meta .= "  - {$baseName}_full.html        (complete page HTML)\n";
        $meta .= "  - {$baseName}_form_summary.txt (extracted form fields)\n";
        file_put_contents("{$debugDir}/{$baseName}_meta.txt", $meta);
    }

    /**
     * Parse the HTML and extract a human-readable summary of all form fields.
     * This includes: inputs, selects (with their options), textareas, buttons.
     */
    private function extractFormSummary(string $html, $session): string
    {
        $lines = [];
        $lines[] = "=== FORM FIELDS SUMMARY ===";
        $lines[] = "URL: " . (method_exists($session, 'getCurrentUrl') ? $session->getCurrentUrl() : 'N/A');
        $lines[] = str_repeat('=', 60);
        $lines[] = '';

        // Use DOMDocument to parse
        $dom = new \DOMDocument();
        @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($dom);

        // --- INPUTS ---
        $inputs = $xpath->query('//input');
        if ($inputs->length > 0) {
            $lines[] = "--- INPUT FIELDS ({$inputs->length}) ---";
            foreach ($inputs as $input) {
                $type = $input->getAttribute('type') ?: 'text';
                $name = $input->getAttribute('name');
                $id = $input->getAttribute('id');
                $value = $input->getAttribute('value');
                $checked = $input->getAttribute('checked') ? ' [CHECKED]' : '';
                $disabled = $input->getAttribute('disabled') ? ' [DISABLED]' : '';
                $placeholder = $input->getAttribute('placeholder');

                $info = "  <input type=\"{$type}\"";
                if ($name) $info .= " name=\"{$name}\"";
                if ($id) $info .= " id=\"{$id}\"";
                if ($value && strlen($value) < 100) $info .= " value=\"{$value}\"";
                if ($placeholder) $info .= " placeholder=\"{$placeholder}\"";
                $info .= "{$checked}{$disabled}>";
                $lines[] = $info;
            }
            $lines[] = '';
        }

        // --- SELECTS ---
        $selects = $xpath->query('//select');
        if ($selects->length > 0) {
            $lines[] = "--- SELECT FIELDS ({$selects->length}) ---";
            foreach ($selects as $select) {
                $name = $select->getAttribute('name');
                $id = $select->getAttribute('id');
                $multiple = $select->getAttribute('multiple') ? ' [MULTIPLE]' : '';

                $lines[] = "  <select name=\"{$name}\" id=\"{$id}\"{$multiple}>";

                $options = $xpath->query('.//option', $select);
                foreach ($options as $option) {
                    $optValue = $option->getAttribute('value');
                    $optText = trim($option->textContent);
                    $selected = $option->getAttribute('selected') ? ' *SELECTED*' : '';
                    $lines[] = "    <option value=\"{$optValue}\"{$selected}>{$optText}</option>";
                }
                $lines[] = "  </select>";
                $lines[] = '';
            }
        }

        // --- TEXTAREAS ---
        $textareas = $xpath->query('//textarea');
        if ($textareas->length > 0) {
            $lines[] = "--- TEXTAREA FIELDS ({$textareas->length}) ---";
            foreach ($textareas as $ta) {
                $name = $ta->getAttribute('name');
                $id = $ta->getAttribute('id');
                $content = substr(trim($ta->textContent), 0, 200);
                $lines[] = "  <textarea name=\"{$name}\" id=\"{$id}\">{$content}...</textarea>";
            }
            $lines[] = '';
        }

        // --- BUTTONS ---
        $buttons = $xpath->query('//button | //input[@type="submit"] | //input[@type="button"]');
        if ($buttons->length > 0) {
            $lines[] = "--- BUTTONS ({$buttons->length}) ---";
            foreach ($buttons as $btn) {
                $tag = $btn->nodeName;
                $type = $btn->getAttribute('type') ?: '';
                $name = $btn->getAttribute('name');
                $id = $btn->getAttribute('id');
                $text = trim($btn->textContent);
                $classes = $btn->getAttribute('class');

                $info = "  <{$tag}";
                if ($type) $info .= " type=\"{$type}\"";
                if ($name) $info .= " name=\"{$name}\"";
                if ($id) $info .= " id=\"{$id}\"";
                if ($classes) $info .= " class=\"{$classes}\"";
                $info .= ">";
                if ($text) $info .= "{$text}</{$tag}>";
                $lines[] = $info;
            }
            $lines[] = '';
        }

        // --- LABELS (useful to map labels to field IDs) ---
        $labels = $xpath->query('//label[@for]');
        if ($labels->length > 0) {
            $lines[] = "--- LABELS WITH 'for' ATTRIBUTE ({$labels->length}) ---";
            foreach ($labels as $label) {
                $for = $label->getAttribute('for');
                $text = trim($label->textContent);
                if ($text) {
                    $lines[] = "  <label for=\"{$for}\">{$text}</label>";
                }
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    public function visit($page): void
    {
        parent::visit($page);

        $this->waitForThePageToBeLoaded();
    }
}
