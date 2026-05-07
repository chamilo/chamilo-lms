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
     * Clean up test users created by createUser.feature before the feature runs.
     * Prevents stale state from a previous aborted run from causing cascading failures.
     *
     * @BeforeFeature @administration
     */
    public static function cleanUpTestUsers(\Behat\Behat\Hook\Scope\BeforeFeatureScope $scope): void
    {
        $testUsernames = ['smarshall', 'hrm', 'teacher', 'student'];
        // Locate .env (two directories up from bootstrap/)
        $envFile = __DIR__.'/../../../../.env';
        $cfg = ['DATABASE_HOST' => 'localhost', 'DATABASE_PORT' => '3306', 'DATABASE_NAME' => '', 'DATABASE_USER' => '', 'DATABASE_PASSWORD' => ''];
        if (is_file($envFile)) {
            foreach (file($envFile) as $line) {
                $line = trim($line);
                if ('' === $line || str_starts_with($line, '#')) {
                    continue;
                }
                foreach (array_keys($cfg) as $key) {
                    if (str_starts_with($line, $key.'=')) {
                        $val = substr($line, strlen($key) + 1);
                        $cfg[$key] = trim($val, "\"' \t");
                    }
                }
            }
        }
        if ('' === $cfg['DATABASE_NAME']) {
            return;
        }
        try {
            $pdo = new \PDO(
                "mysql:host={$cfg['DATABASE_HOST']};port={$cfg['DATABASE_PORT']};dbname={$cfg['DATABASE_NAME']};charset=utf8mb4",
                $cfg['DATABASE_USER'],
                $cfg['DATABASE_PASSWORD']
            );
            $placeholders = implode(',', array_fill(0, count($testUsernames), '?'));
            $pdo->prepare("DELETE FROM user WHERE username IN ($placeholders)")->execute($testUsernames);
        } catch (\Throwable $e) {
            echo "\n[BeforeFeature] Could not clean up test users: ".$e->getMessage()."\n";
        }
    }

    /**
     * @Given /^I am a platform administrator$/
     */
    public function iAmAPlatformAdministrator()
    {
        $this->iAmLoggedAs('admin');
    }

    /**
     * Directly creates a USER_RELATION_TYPE_RRHH (type 7) relationship so an HRM
     * user can "login as" the target user. Bypasses the legacy PHP dual-list UI
     * which is brittle (option text includes "(username)" suffix that Mink can't match).
     *
     * @Given /^"([^"]*)" follows "([^"]*)" as HRM$/
     */
    public function userFollowsAsHrm(string $hrmUsername, string $targetUsername): void
    {
        $pdo = $this->getTestPdo();
        $stmt = $pdo->prepare(
            'SELECT id FROM user WHERE username = ?'
        );
        $stmt->execute([$hrmUsername]);
        $hrmId = $stmt->fetchColumn();
        $stmt->execute([$targetUsername]);
        $targetId = $stmt->fetchColumn();
        if (!$hrmId || !$targetId) {
            throw new \RuntimeException("Could not find hrm='$hrmUsername' (id=$hrmId) or target='$targetUsername' (id=$targetId).");
        }
        // USER_RELATION_TYPE_RRHH = 7.
        // Direction matches UserManager::subscribeUsersToUser: user_id=target, friend_user_id=HRM
        // (getUsersFollowedByUser queries WHERE friend_user_id = HRM_ID)
        $pdo->prepare(
            'INSERT IGNORE INTO user_rel_user (user_id, friend_user_id, relation_type) VALUES (?, ?, 7)'
        )->execute([$targetId, $hrmId]);
    }

    /**
     * Navigate directly to the myStudents tracking page for a given username.
     * Looks up the user ID from the database so tests don't need to hard-code IDs.
     *
     * @Given /^I am on the tracking page for "([^"]*)"$/
     */
    public function iAmOnTrackingPageFor(string $username): void
    {
        $pdo = $this->getTestPdo();
        $stmt = $pdo->prepare('SELECT id FROM user WHERE username = ?');
        $stmt->execute([$username]);
        $userId = $stmt->fetchColumn();
        if (!$userId) {
            throw new \RuntimeException("User '$username' not found in database.");
        }
        $this->visit('/main/my_space/myStudents.php?student='.(int) $userId);
        $this->waitForThePageToBeLoaded();
    }

    private function getTestPdo(): \PDO
    {
        $envFile = __DIR__.'/../../../../.env';
        $cfg = ['DATABASE_HOST' => 'localhost', 'DATABASE_PORT' => '3306', 'DATABASE_NAME' => '', 'DATABASE_USER' => '', 'DATABASE_PASSWORD' => ''];
        if (is_file($envFile)) {
            foreach (file($envFile) as $line) {
                $line = trim($line);
                if ('' === $line || str_starts_with($line, '#')) {
                    continue;
                }
                foreach (array_keys($cfg) as $key) {
                    if (str_starts_with($line, $key.'=')) {
                        $cfg[$key] = trim(substr($line, strlen($key) + 1), "\"' \t");
                    }
                }
            }
        }
        return new \PDO(
            "mysql:host={$cfg['DATABASE_HOST']};port={$cfg['DATABASE_PORT']};dbname={$cfg['DATABASE_NAME']};charset=utf8mb4",
            $cfg['DATABASE_USER'],
            $cfg['DATABASE_PASSWORD']
        );
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
        // Course tools are loaded asynchronously via API after Vue mounts
        $this->waitForSelector('#course-tools a', 10000);
        $this->assertElementNotOnPage('.alert-danger');
    }

    /**
     * @Given /^I am on course "([^"]*)" homepage in session "([^"]*)"$/
     * @deprecated Use iAmOnTheHomepageOfCourseXInSessionY instead
     */
    public function iAmOnCourseXHomepageInSessionY($courseCode, $sessionName): void
    {
        $this->visit('/main/course_home/redirect.php?cidReq='.$courseCode.'&session_name='.$sessionName);
        $this->waitForSelector('#course-tools a', 10000);
        $this->assertElementNotOnPage('.alert-danger');
    }

    /**
     * @Given /^I am on the homepage of course "([^"]*)"$/
     */
    public function iAmOnTheHomepageOfCourseX($courseId): void
    {
        $this->visit('/course/'.$courseId.'/home');
        $this->waitForSelector('#course-tools a', 10000);
        $this->assertElementNotOnPage('.alert-danger');
    }

    /**
     * @Given /^I am on the homepage of course "([^"]*) in session "([^"]*)"$/
     */
    public function iAmOnTheHomepageOfCourseXInSessionY($courseId, $sessionId): void
    {
        $this->visit('/course/'.$courseId.'&sid='.$sessionId);
        $this->waitForSelector('#course-tools a', 10000);
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
        $passwords = [
            'admin' => 'admin11+',
        ];
        $password = $passwords[$username] ?? $username;

        if ($this->getSession()->isStarted()) {
            parent::visit($this->getMinkParameter('base_url').'/logout');
            $this->getSession()->reset();
        }

        // Visit login to establish a fresh PHP session (creates the session cookie)
        parent::visit($this->getMinkParameter('base_url').'/login');
        $this->getSession()->wait(4000, "document.readyState === 'complete'");

        // Clear stale auth data from previous sessions so Vue starts fresh
        $this->getSession()->executeScript(
            'try { localStorage.clear(); sessionStorage.clear(); } catch(e) {}'
        );

        // Authenticate via synchronous XHR — bypasses the Vue form entirely,
        // immune to race conditions, throttle counter resets after success.
        $u = addslashes($username);
        $p = addslashes($password);
        // Retry up to 3 times — PHP session GC intermittently causes 500 when
        // /var/lib/php/sessions is not accessible (Permission denied on gc cleanup).
        $status = 0;
        $body = '';
        for ($attempt = 0; $attempt < 3; $attempt++) {
            if ($attempt > 0) {
                $this->getSession()->wait(1000);
            }
            $result = $this->getSession()->evaluateScript(
                "(function(){
                    var x = new XMLHttpRequest();
                    x.open('POST', '/login_json', false);
                    x.setRequestHeader('Content-Type', 'application/json');
                    x.send(JSON.stringify({username:'$u', password:'$p'}));
                    return {status: x.status, body: x.responseText.substring(0, 300)};
                })()"
            );
            $status = (int) ($result['status'] ?? 0);
            $body = (string) ($result['body'] ?? '');
            if (200 === $status) {
                break;
            }
        }

        if (200 !== $status) {
            throw new \Exception("Login failed for '$username' after 3 attempts — HTTP $status. Body: $body");
        }

        // Warm up the legacy PHP session bridge: LegacyListener sets _user in session
        $this->visit('/admin');
        $this->waitForThePageToBeLoaded();
    }

    /**
     * Checks, that element with specified CSS doesn't exist on page
     *
     * @Then /^(?:|I )should not see an error$/
     */
    public function iShouldNotSeeAnError()
    {
        // Wait for page to have visible content before checking (avoids false empty-body reads)
        $this->getSession()->wait(
            5000,
            "document.readyState === 'complete' && document.body && (document.body.innerText || '').trim().length > 0"
        );
        // Use JS for text checks — atomic, avoids stale-element on Vue re-renders
        $text = strtolower((string) $this->getSession()->evaluateScript(
            'return document.body ? (document.body.innerText || document.body.textContent || "") : ""'
        ));
        if (str_contains($text, 'internal server error')) {
            throw new \Behat\Mink\Exception\ExpectationException(
                'Page contains "Internal server error"',
                $this->getSession()
            );
        }
        if (str_contains($text, 'error')) {
            throw new \Behat\Mink\Exception\ExpectationException(
                'Page contains "error"',
                $this->getSession()
            );
        }
        $el = $this->getSession()->getPage()->find('css', '.alert-danger');
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
        if ($this->getSession()->isStarted()) {
            $this->getSession()->reset();
        }
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

        // 1) Native browser alert
        try {
            $session->getDriver()->getWebDriverSession()->accept_alert();
            return;
        } catch (\Exception $e) {}

        // 2) PrimeVue ConfirmDialog: wait for dialog to appear AND animation to finish
        $session->wait(5000, "document.querySelector('.p-confirmdialog') !== null || document.querySelector('.swal2-container') !== null");
        // Wait until dialog is present AND its enter animation is complete
        $session->wait(2000,
            "(function(){ var d = document.querySelector('.p-confirmdialog'); " .
            "return d && !d.classList.contains('p-dialog-enter-active') && !d.classList.contains('p-dialog-enter-from'); })()"
        );

        $js = <<<'JS'
        (function(){
            function click(el) {
                if (!el) return false;
                try { el.style.pointerEvents = 'auto'; el.style.zIndex = 999999; } catch(e){}
                try {
                    el.dispatchEvent(new MouseEvent('click', {bubbles: true, cancelable: true, view: window}));
                    return true;
                } catch(e) {}
                try { el.click(); return true; } catch(e) { return false; }
            }
            // PrimeVue ConfirmDialog: target accept button by its specific class
            var dlg = document.querySelector('.p-confirmdialog');
            if (dlg) {
                var acceptBtn = dlg.querySelector('.p-confirmdialog-accept-button');
                if (click(acceptBtn)) return true;
                // Fallback: button with text "Yes" or "Oui"
                var btns = dlg.querySelectorAll('button');
                for (var i = 0; i < btns.length; i++) {
                    if ((btns[i].textContent||'').trim() === 'Yes' || (btns[i].textContent||'').trim() === 'Oui') {
                        if (click(btns[i])) return true;
                    }
                }
            }
            // SweetAlert2 fallback
            var swal = document.querySelector('.swal2-container');
            if (swal) {
                var el = swal.querySelector('.swal2-confirm');
                if (click(el)) return true;
            }
            return false;
        })();
        JS;

        // executeScript may return null if the accept handler navigates away (form.submit()).
        // In that case the confirmation DID succeed, so we treat null as "clicked".
        try {
            $result = $session->executeScript($js);
        } catch (\Exception $e) {
            // Page navigation during script execution — confirmation was accepted
            return;
        }

        if (null === $result) {
            // Page navigated away during script execution — confirmation was accepted
            return;
        }

        if (!(bool) $result) {
            // Dialog was still present but nothing was clickable — true failure
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
     * @When /^wait the page to be loaded when ready$/
     */
    public function waitForThePageToBeLoaded(): void
    {
        // Brief sleep allows any in-flight navigation (form submit, redirect) to start
        // before we begin polling; without it, the condition can fire on the old page.
        $this->getSession()->wait(300);
        $this->getSession()->wait(
            8000,
            "document.readyState === 'complete' && " .
            "(document.querySelector('#app') === null || document.querySelector('#app').children.length > 0) && " .
            "(document.querySelector('#sectionMainContent') === null || document.querySelector('#sectionMainContent').style.display !== 'none')"
        );
    }

    /**
     * @When /^(?:|I )wait very long for the page to be loaded$/
     */
    public function waitVeryLongForThePageToBeLoaded(): void
    {
        // Sleep gives the browser time to start any in-flight navigation (form submit,
        // redirect) before we begin polling; without it the condition fires immediately
        // on the old page (which already satisfies readyState=complete).
        $this->getSession()->wait(1500);
        $this->getSession()->wait(
            14000,
            "document.readyState === 'complete' && " .
            "(document.querySelector('#app') === null || document.querySelector('#app').children.length > 0) && " .
            "(document.querySelector('#sectionMainContent') === null || document.querySelector('#sectionMainContent').style.display !== 'none')"
        );
    }

    /**
     * @When /^(?:|I )wait for the page to be loaded when ready$/
     */
    public function waitForThePageToBeLoadedWhenReady(): void
    {
        $this->getSession()->wait(9000, "document.readyState === 'complete'");
    }

    /**
     * @When /^(?:|I )wait for the "([^"]*)" element$/
     */
    public function waitForSelector(string $css, int $timeoutMs = 8000): void
    {
        $escaped = addslashes($css);
        $this->getSession()->wait($timeoutMs, "document.querySelector('$escaped') !== null");
    }

    /**
     * @When /^(?:|I )wait until the URL contains "([^"]*)"$/
     */
    public function waitUntilUrlContains(string $fragment): void
    {
        $escaped = addslashes($fragment);
        $this->getSession()->wait(10000, "window.location.href.indexOf('$escaped') !== -1");
    }

    /**
     * Trigger a Vue Router push to the current URL.
     * Fixes the issue where lazy route components don't render on initial full-page load
     * because the component factory is never called until a SPA navigation occurs.
     * Uses a roundtrip via /home (eagerly loaded) to avoid NavigationDuplicated.
     *
     * @When /^I trigger Vue SPA navigation$/
     */
    public function triggerVueSpaNavigation(): void
    {
        $this->getSession()->executeScript(
            '(function(){
                window.__vueSpaNavDone__ = false;
                window.__vueSpaNavError__ = null;
                var app = document.getElementById("app");
                var vueApp = app && app.__vue_app__;
                if (!vueApp) { window.__vueSpaNavDone__ = true; return; }
                var router = vueApp.config.globalProperties.$router;
                if (!router) { window.__vueSpaNavDone__ = true; return; }
                var targetPath = router.currentRoute.value.fullPath;
                // Use force:true to bypass NavigationDuplicated and trigger a real re-navigation
                router.push({ path: targetPath, force: true }).then(function() {
                    // Wait for nextTick to ensure DOM is committed after vnode update
                    var nextTick = vueApp.config.globalProperties.$nextTick || Promise.resolve.bind(Promise);
                    return nextTick();
                }).then(function() {
                    window.__vueSpaNavDone__ = true;
                }).catch(function(err) {
                    window.__vueSpaNavError__ = err ? (err.message || String(err)) : "unknown";
                    window.__vueSpaNavDone__ = true;
                });
            })()'
        );
        $result = $this->getSession()->wait(20000, "window.__vueSpaNavDone__ === true");
        if (!$result) {
            throw new \RuntimeException('Vue SPA navigation did not complete within 20 seconds.');
        }
        $error = $this->getSession()->evaluateScript('return window.__vueSpaNavError__');
        if ($error) {
            echo "\n[Vue nav notice]: $error\n";
        }
        // Wait for Vue DOM updates to commit (scheduler flushes async)
        usleep(3000000);
    }

    /**
     * @When /^I wait (\d+) seconds for the "([^"]*)" element$/
     */
    public function waitSecondsForSelector(int $seconds, string $css): void
    {
        $this->waitForSelector($css, $seconds * 1000);
    }

    /**
     * Dump browser console logs (errors, warnings) captured by ChromeDriver.
     *
     * @When /^I dump browser console logs$/
     */
    public function iDumpBrowserConsoleLogs(): void
    {
        $driver = $this->getSession()->getDriver();
        if (!$driver instanceof \Behat\Mink\Driver\Selenium2Driver) {
            echo "\n[console logs] Driver is not Selenium2\n";
            return;
        }
        try {
            $logs = $driver->getWebDriverSession()->log('browser');
            echo "\n=== BROWSER CONSOLE LOGS (" . count($logs) . " entries) ===\n";
            foreach ($logs as $entry) {
                $level = $entry['level'] ?? '?';
                $msg   = $entry['message'] ?? '';
                echo "[$level] $msg\n";
            }
            echo "=== END CONSOLE LOGS ===\n";
        } catch (\Throwable $e) {
            echo "\n[console logs] Could not retrieve: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Wait until the page's visible text contains the given string (polls until timeout).
     * Use this instead of waitForSelector+assertPageContainsText when the content
     * arrives asynchronously (Vue SPAs, lazy-loaded data) to avoid race conditions
     * where the element exists but its text hasn't been populated yet.
     *
     * @When /^I wait until I see "([^"]*)"$/
     * @When /^wait until I see "([^"]*)"$/
     */
    public function iWaitUntilISee(string $text): void
    {
        $escaped = addslashes($text);
        $result = $this->getSession()->wait(
            45000,
            "(document.body ? (document.body.innerText || document.body.textContent || '') : '').indexOf('$escaped') !== -1"
        );
        if (!$result) {
            // Debug: dump final state before throwing
            $debug = (string) $this->getSession()->evaluateScript(
                '(function(){
                    var app = document.getElementById("app");
                    var vueApp = app && app.__vue_app__;
                    if (!vueApp) return "no vueApp";
                    var router = vueApp.config.globalProperties.$router;
                    var route = router ? router.currentRoute.value.name : "no router";
                    var pinia = vueApp.config.globalProperties.$pinia;
                    var isAdmin = false;
                    var isLoading = "?";
                    if (pinia && pinia.state.value.security) {
                        var s = pinia.state.value.security;
                        isLoading = s.isLoading;
                        isAdmin = !!(s.user && s.user.roles && (s.user.roles.indexOf("ROLE_ADMIN") !== -1 || s.user.roles.indexOf("ROLE_GLOBAL_ADMIN") !== -1));
                    }
                    var adminIdx = document.querySelector(".admin-index");
                    var chunks = window.webpackChunkChamilo ? window.webpackChunkChamilo.length : "N/A";
                    var bodyText = document.body ? (document.body.innerText || "").substring(0, 300) : "nobody";
                    return "route="+route+" isAdmin="+isAdmin+" isLoading="+isLoading+" chunks="+chunks+" adminIdx="+(!!adminIdx)+" body="+bodyText;
                })()'
            );
            echo "\n[iWaitUntilISee debug] $debug\n";
            throw new \RuntimeException("Text '$text' did not appear on the page within 12 seconds.");
        }
    }

    /**
     * @When /^I dump the page body text$/
     */
    public function iDumpThePageBodyText(): void
    {
        $text = (string) $this->getSession()->evaluateScript(
            'return document.body ? (document.body.innerText || document.body.textContent || "BODY_EMPTY") : "NO_BODY"'
        );
        echo "\n=== PAGE BODY TEXT (first 2000 chars) ===\n";
        echo mb_substr($text, 0, 2000) . "\n";
        echo "=== URL: " . $this->getSession()->getCurrentUrl() . " ===\n";

        $debug = (string) $this->getSession()->evaluateScript(
            'return (function() {
                var info = {};
                info.url = window.location.href;
                info.chunks = window.webpackChunkChamilo ? window.webpackChunkChamilo.length : "N/A";
                var app = document.getElementById("app");
                var vueApp = app && app.__vue_app__;
                if (!vueApp) { info.vue = "no vueApp"; return JSON.stringify(info); }
                var router = vueApp.config.globalProperties.$router;
                if (router) {
                    info.route = router.currentRoute.value.name;
                    info.path = router.currentRoute.value.fullPath;
                    info.matched = router.currentRoute.value.matched.length;
                    // Check what component factories the matched records have
                    info.matchedComponents = router.currentRoute.value.matched.map(function(r) {
                        var c = r.components && r.components.default;
                        return typeof c === "function" ? "lazy:"+c.name : (typeof c === "object" ? "eager" : typeof c);
                    });
                }
                var pinia = vueApp.config.globalProperties.$pinia;
                if (pinia) {
                    var stores = Object.keys(pinia.state.value);
                    info.stores = stores;
                    if (pinia.state.value.security) {
                        info.isAuthenticated = !!(pinia.state.value.security.user && pinia.state.value.security.user.id);
                        info.isLoading = pinia.state.value.security.isLoading;
                    }
                    if (pinia.state.value.platformConfig) {
                        info.platformIsLoading = pinia.state.value.platformConfig.isLoading;
                    }
                }
                var adminIndex = document.querySelector(".admin-index");
                info.adminIndexExists = !!adminIndex;
                // Check for AdminBlock/PrimeVue elements
                info.pPanelCount = document.querySelectorAll(".p-panel, .p-card, [class*=admin]").length;
                // Check app-main
                var appMain = document.querySelector(".app-main");
                info.appMainChildCount = appMain ? appMain.childNodes.length : 0;
                // Dump the full app-main innerHTML for analysis
                info.appMainFull = appMain ? appMain.innerHTML.substring(0, 500) : "null";
                // Walk Vue component tree to find RouterView and inspect its state
                try {
                    var root = vueApp._instance;
                    var treeLines = [];
                    function walkTree(vnode, depth) {
                        if (!vnode || depth > 15) return;
                        var typeName = "?";
                        if (!vnode.type) typeName = "null";
                        else if (typeof vnode.type === "string") typeName = "<"+vnode.type+">";
                        else if (typeof vnode.type === "symbol") typeName = "Fragment";
                        else if (vnode.type.__name) typeName = vnode.type.__name;
                        else if (vnode.type.name) typeName = vnode.type.name;
                        else typeName = typeof vnode.type;
                        var indent = "  ".repeat(depth);
                        var extra = "";
                        if (vnode.component && vnode.component.setupState) {
                            var ss = vnode.component.setupState;
                            if (ss.matchedRouteRef !== undefined) {
                                extra += " [RV matched="+(ss.matchedRouteRef && ss.matchedRouteRef.value ? (ss.matchedRouteRef.value.path || "ok") : "null")+"]";
                            }
                        }
                        treeLines.push(indent + typeName + extra);
                        if (vnode.component) {
                            walkTree(vnode.component.subTree, depth + 1);
                        } else if (Array.isArray(vnode.children)) {
                            vnode.children.forEach(function(c) { walkTree(c, depth + 1); });
                        }
                    }
                    walkTree(root.subTree, 0);
                    info.tree = treeLines.join("\n");
                } catch(e) { info.tree = "error: "+e.message; }
                return JSON.stringify(info);
            })()'
        );
        echo "=== VUE STATE ===\n" . json_encode(json_decode($debug), JSON_PRETTY_PRINT) . "\n";
    }

    /**
     * @When /^I wait for jqGrid to load$/
     */
    public function waitForJqGridToLoad(): void
    {
        // Step 1: wait for jqGrid to initialize (fires in $(document).ready, same tick as readyState===complete)
        $this->getSession()->wait(5000, "document.querySelector('.ui-jqgrid') !== null");
        // Step 2: wait for jqGrid's AJAX data fetch to complete
        $this->getSession()->wait(8000, "typeof jQuery !== 'undefined' && jQuery.active === 0");
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
        $this->getSession()->wait(3000); // wait for autocomplete results, not page load
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
        $this->waitForSelector($selector, 8000);
        $escaped = addslashes($selector);
        // Scroll element into view so it is interactable even when off-screen,
        // then use JavaScript click - avoids sticky-header occlusion issues with WebDriver physical clicks.
        $exists = $this->getSession()->evaluateScript(
            "!!document.querySelector('$escaped')"
        );
        if (!$exists) {
            throw new \RuntimeException("Element '$selector' not found on page.");
        }
        $this->getSession()->executeScript(
            "(function(){
                var el = document.querySelector('$escaped');
                el.scrollIntoView({behavior:'instant',block:'center'});
                // If the element is inside a button, click the button so Vue event handlers fire.
                var clickTarget = el.closest('button') || el.closest('a') || el;
                clickTarget.click();
            })();"
        );
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

    public function fillField($field, $value): void
    {
        $field = $this->fixStepArgument($field);
        $value = $this->fixStepArgument($value);

        $usedNative = false;
        try {
            $this->getSession()->getPage()->fillField($field, $value);
            $usedNative = true;
        } catch (\Exception $e) {
            if (false === strpos($e->getMessage(), 'interactable') && false === strpos($e->getMessage(), 'ElementNotInteractable')) {
                throw $e;
            }
        }

        // After native fill, verify the value was actually set (Selenium sendKeys can silently
        // drop non-ASCII characters like accented letters). If mismatch, fall through to JS.
        if ($usedNative) {
            $element = $this->getSession()->getPage()->findField($field);
            if ($element) {
                $actualValue = $element->getValue();
                if ($actualValue === $value) {
                    return;
                }
            } else {
                return;
            }
        }

        // PrimeVue float-label elements have the <label> positioned over the <input>,
        // making ChromeDriver report "not interactable". Also used when native fill
        // silently drops characters (e.g. non-ASCII like 'Ñ'). Set the value via JS.
        $element = $element ?? $this->getSession()->getPage()->findField($field);
        if (!$element) {
            throw new \RuntimeException("Field '$field' not found on the page.");
        }

        $id = $element->getAttribute('id');
        $name = $element->getAttribute('name') ?? '';
        $selector = $id
            ? "document.getElementById('" . addslashes($id) . "')"
            : "document.querySelector('[name=\"" . addslashes($name) . "\"]')";
        $escaped = addslashes($value);

        $this->getSession()->executeScript(
            "var el = $selector;
             if (el) {
                 el.scrollIntoView({block:'center'});
                 el.value = '$escaped';
                 el.dispatchEvent(new Event('input',  {bubbles:true}));
                 el.dispatchEvent(new Event('change', {bubbles:true}));
             }"
        );
    }

    public function visit($page): void
    {
        parent::visit($page);
        $this->waitForThePageToBeLoaded();
    }
}
