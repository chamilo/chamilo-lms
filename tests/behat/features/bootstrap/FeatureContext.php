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
    /** @var array<string, int> */
    private array $courseIdsByCode = [];

    /**
     * Initializes context.
     * Every scenario gets its own context object.
     */
    public function __construct()
    {
    }

    /**
     * Overrides MinkContext::fillField(). Two independent robustness fixes:
     *
     * 1. Retry on ElementNotInteractable: legacy pages can briefly reflow
     *    right after load (fonts/images settling), shifting an
     *    already-visible field before WebDriver's click on it lands, even
     *    though it passed a prior "wait for element" check.
     * 2. The driver's own setValue() (clear + sendKeys) was found to
     *    sometimes leave the DOM value unset without throwing any error, so
     *    a Vue-bound field's v-model silently stays empty and the form later
     *    submits blank data (root cause of a login silently rejected as
     *    invalid credentials, and of an admin search silently not
     *    navigating). Setting the value via the native input/textarea
     *    property setter and dispatching a real 'input'/'change' event is
     *    reliably picked up by Vue regardless of what makes the driver's own
     *    fill flaky here, so it replaces setValue() for every field.
     */
    public function fillField($field, $value)
    {
        $field = $this->fixStepArgument($field);
        $value = $this->fixStepArgument($value);

        $attempts = 3;
        for ($i = 1; $i <= $attempts; ++$i) {
            try {
                $node = $this->getSession()->getPage()->findField($field);
                if (null === $node) {
                    throw new \Behat\Mink\Exception\ElementNotFoundException(
                        $this->getSession(),
                        'form field',
                        'id|name|label|value|placeholder',
                        $field
                    );
                }

                $ok = $this->getSession()->evaluateScript(sprintf(
                    "(function(){var el=document.evaluate(%s,document,null,XPathResult.FIRST_ORDERED_NODE_TYPE,null).singleNodeValue;".
                    "if(!el) return false;".
                    "var proto=el.tagName==='TEXTAREA'?window.HTMLTextAreaElement.prototype:window.HTMLInputElement.prototype;".
                    "var setter=Object.getOwnPropertyDescriptor(proto,'value').set;".
                    "setter.call(el,%s);".
                    "el.dispatchEvent(new Event('input',{bubbles:true}));".
                    "el.dispatchEvent(new Event('change',{bubbles:true}));".
                    "return el.value===%s;})()",
                    json_encode($node->getXpath()),
                    json_encode($value),
                    json_encode($value)
                ));

                if ($ok) {
                    return;
                }

                throw new \RuntimeException(sprintf('Could not set value on field "%s" via native setter.', $field));
            } catch (\WebDriver\Exception $e) {
                if ($i === $attempts) {
                    throw $e;
                }
                $this->getSession()->wait(1000);
            }
        }
    }

    /**
     * Overrides MinkContext::pressButton(). Two independent robustness fixes,
     * mirroring the fillField() override above:
     *
     * 1. Retry on transient WebDriver errors: a page can briefly reflow
     *    right after load, shifting an already-visible button before
     *    WebDriver's click on it lands, even though it passed a prior "wait
     *    for element" check.
     * 2. A native click on a submit button was found to sometimes register
     *    (no exception) without ever triggering the form's actual
     *    submission/navigation — same root cause family as the login form
     *    issue. When the button lives inside a <form>, submit that form
     *    directly via requestSubmit(), which is unaffected by click
     *    coordinates or the driver's click-vs-navigation quirks.
     */
    public function pressButton($button)
    {
        $button = $this->fixStepArgument($button);

        $attempts = 3;
        for ($i = 1; $i <= $attempts; ++$i) {
            try {
                $node = $this->getSession()->getPage()->findButton($button);
                if (null === $node) {
                    throw new \Behat\Mink\Exception\ElementNotFoundException(
                        $this->getSession(),
                        'button',
                        'id|name|title|alt|value',
                        $button
                    );
                }

                $ok = $this->getSession()->evaluateScript(sprintf(
                    "(function(){var el=document.evaluate(%s,document,null,XPathResult.FIRST_ORDERED_NODE_TYPE,null).singleNodeValue;".
                    "if(!el) return false;".
                    "var form=el.closest('form');".
                    "if(form&&el.type==='submit'){form.requestSubmit(el);}else{el.click();}".
                    "return true;})()",
                    json_encode($node->getXpath())
                ));

                if ($ok) {
                    return;
                }

                throw new \RuntimeException(sprintf('Could not press button "%s".', $button));
            } catch (\WebDriver\Exception $e) {
                if ($i === $attempts) {
                    throw $e;
                }
                $this->getSession()->wait(1000);
            }
        }
    }

    /**
     * Overrides MinkContext::clickLink(). Same root cause family as
     * fillField()/pressButton() above: a native click on an <a> link was
     * found to sometimes register (no exception) without ever navigating —
     * likely a Vue-router click handler swallowing it. Since a link's whole
     * purpose is to navigate to its href, visiting that href directly is a
     * simpler and more reliable substitute than simulating a click.
     */
    public function clickLink($link)
    {
        $link = $this->fixStepArgument($link);

        $attempts = 3;
        for ($i = 1; $i <= $attempts; ++$i) {
            try {
                $node = $this->getSession()->getPage()->findLink($link);
                if (null === $node) {
                    throw new \Behat\Mink\Exception\ElementNotFoundException(
                        $this->getSession(),
                        'link',
                        'id|title|alt|text',
                        $link
                    );
                }

                // Use the .href *property* (browser-resolved absolute URL),
                // not getAttribute('href') (the raw, possibly relative HTML
                // attribute) — visit() below resolves a relative attribute
                // against base_url (the site root), not the current page's
                // directory, which can land on a wrong path (e.g. a link
                // meant to resolve to "/main/exercise/overview.php" instead
                // 404ing at "/overview.php"). Same root cause as the
                // absolute-href handling in iClickTheElement() below.
                $href = $this->getSession()->evaluateScript(sprintf(
                    "(function(){var el=document.evaluate(%s,document,null,XPathResult.FIRST_ORDERED_NODE_TYPE,null).singleNodeValue;".
                    "if(!el) return null;return el.href||null;})()",
                    json_encode($node->getXpath())
                ));
                if ($href && 0 !== stripos($href, 'javascript:')) {
                    $this->visit($href);
                } else {
                    $node->click();
                }

                return;
            } catch (\WebDriver\Exception $e) {
                if ($i === $attempts) {
                    throw $e;
                }
                $this->getSession()->wait(1000);
            }
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
        $this->dismissCookieBannerIfNeeded();

        // Some themes (e.g. the custom sidebar template) render a second,
        // hidden copy of the login form with the same field ids inside a
        // collapsed sidebar menu. Scope to the visible form in the main
        // content area so the fill/submit below don't hit the hidden one.
        $session = $this->getSession();
        $session->wait(10000, "document.querySelector('.app-main .login-section') !== null");

        // Mink's fillField() was found to silently no-op on this page on a
        // second /login visit within the same browser session (neither the
        // visible nor the hidden copy ended up with the typed value), which
        // made the backend correctly reject an empty username/password as
        // invalid credentials. Set the value via the native input setter and
        // dispatch a real 'input' event instead, so Vue's v-model reliably
        // picks it up regardless of what made fillField() unreliable here.
        $escapedUsername = addslashes($username);
        $filled = $session->evaluateScript(
            "(function(){var c=document.querySelector('.app-main .login-section');if(!c)return false;".
            "var login=c.querySelector('#login');var password=c.querySelector('#password');".
            "if(!login||!password)return false;".
            "var setter=Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype,'value').set;".
            "setter.call(login,'{$escapedUsername}');login.dispatchEvent(new Event('input',{bubbles:true}));".
            "setter.call(password,'{$escapedUsername}');password.dispatchEvent(new Event('input',{bubbles:true}));".
            "return login.value==='{$escapedUsername}'&&password.value==='{$escapedUsername}';})()"
        );
        if (!$filled) {
            throw new \RuntimeException('Could not fill the login/password fields in ".app-main .login-section".');
        }

        // Submit the <form> directly via requestSubmit() instead of clicking
        // the visible button, to avoid any ambiguity from the theme's
        // duplicate hidden sidebar login copy affecting which element the
        // click coordinates actually land on.
        $submitted = $session->evaluateScript(
            "(function(){var f=document.querySelector('.app-main .login-section form');".
            "if(!f) return false; f.requestSubmit(); return true;})()"
        );
        if (!$submitted) {
            throw new \RuntimeException('Login form <form> inside ".app-main .login-section" not found for submit.');
        }

        // Poll for the redirect away from /login instead of relying on a
        // fixed sleep: a slow login request can otherwise leave the next
        // step running against an unauthenticated session, which then fails
        // several steps downstream with an unrelated, confusing error.
        $redirected = $session->wait(16000, "window.location.pathname.indexOf('/login') === -1");
        if (!$redirected) {
            throw new \RuntimeException(sprintf(
                'Login as "%s" failed: still on %s after waiting for the redirect.',
                $username,
                $session->getCurrentUrl()
            ));
        }

        $this->waitForThePageToBeLoaded();
    }

    /**
     * The cookie consent banner is fixed to the bottom of the viewport and
     * intercepts clicks on any element that renders near the bottom of a
     * page. It's gated server-side by the "ChamiloUsesCookies" cookie
     * (cookie_banner.html.twig), so setting it once and reloading removes
     * the banner from every subsequent page for the rest of the session.
     */
    private function dismissCookieBannerIfNeeded(): void
    {
        $session = $this->getSession();
        if ('ok' === $session->getCookie('ChamiloUsesCookies')) {
            return;
        }

        $session->setCookie('ChamiloUsesCookies', 'ok');
        $this->visit('/login');
        $this->waitForThePageToBeLoaded();
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
    public function iHaveAPublicPasswordProtectedCourse($code, $password): void
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
        $this->waitForThePageToBeLoadedWhenReady();

        // Resolve the numeric course id through Chamilo 2's compatibility route,
        // which redirects the course code to /course/{cid}/home.
        $this->visit('/courses/'.$code.'/index.php');
        $this->waitForThePageToBeLoadedWhenReady();

        $path = (string) parse_url($this->getSession()->getCurrentUrl(), PHP_URL_PATH);
        if (1 !== preg_match('#^/course/(\d+)/home$#', $path, $matches)) {
            throw new RuntimeException('Could not resolve the modern course home URL for course '.$code.'.');
        }

        $courseId = (int) $matches[1];
        $this->courseIdsByCode[$code] = $courseId;

        $this->visit('/main/course_info/infocours.php?cid='.$courseId);
        $this->waitForThePageToBeLoadedWhenReady();
        $this->iClickTheElement('#card_course_access a');
        $this->getSession()->wait(
            5000,
            'var panel = document.querySelector("#collapse_course_access"); panel && panel.classList.contains("active")'
        );
        $this->assertPageContainsText('Course registration password');
        $this->fillField('course_registration_password', $password);
        $this->pressButton('submit_save');
        $this->waitForThePageToBeLoadedWhenReady();
        $this->assertFieldContains('course_registration_password', $password);
    }

    /**
     * @Given /^I am on the modern homepage of course "([^"]*)"$/
     */
    public function iAmOnTheModernHomepageOfCourse(string $courseCode): void
    {
        $courseId = $this->getCreatedCourseId($courseCode);

        $this->visit('/course/'.$courseId.'/home?sid=0&gid=0');
        $this->waitForThePageToBeLoadedWhenReady();
    }

    /**
     * @Then /^I should be on the modern homepage of course "([^"]*)"$/
     */
    public function iShouldBeOnTheModernHomepageOfCourse(string $courseCode): void
    {
        $courseId = $this->getCreatedCourseId($courseCode);
        $actualPath = (string) parse_url($this->getSession()->getCurrentUrl(), PHP_URL_PATH);
        $expectedPath = '/course/'.$courseId.'/home';

        if ($expectedPath !== $actualPath) {
            throw new RuntimeException(
                sprintf('Expected current path "%s", got "%s".', $expectedPath, $actualPath)
            );
        }
    }

    private function getCreatedCourseId(string $courseCode): int
    {
        $courseId = $this->courseIdsByCode[$courseCode] ?? 0;

        if ($courseId <= 0) {
            throw new RuntimeException('No created course id is available for course '.$courseCode.'.');
        }

        return $courseId;
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
            "var ed = tinymce.get(\"$fieldId\"); ed.setContent(\"$value\"); ed.fire('input'); ed.fire('change');"
        );
    }

    /**
     * Same as iFillInTinyMceOnFieldWith(), but for pages where the field's
     * id is dynamic (e.g. suffixed with the current exercise attempt's id,
     * such as "comments_18664") rather than a fixed name. Matches by id
     * *prefix* rather than requiring a single instance on the page — e.g.
     * the exercise-correction page also has a separate "notification_content"
     * tinymce instance for the reviewer's message, so "comments_" narrows to
     * just the one this step is meant to fill.
     *
     * @Then /^I fill in the tinymce field starting with "([^"]*)" with "([^"]*)"$/
     */
    public function iFillInTinyMceFieldStartingWith($idPrefix, $value)
    {
        $this->getSession()->wait(2000);
        $this->getSession()->executeScript(
            "var ids = tinymce.editors.map(function(ed){return ed.id;}).filter(function(id){return id.indexOf(\"$idPrefix\") === 0;});".
            "if(ids.length !== 1){throw new Error('Expected exactly one tinymce instance starting with \"$idPrefix\", found '+ids.length);}".
            "var ed = tinymce.get(ids[0]); ed.setContent(\"$value\"); ed.fire('input'); ed.fire('change');"
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
        $this->getSession()->executeScript("$('$field').select2({data : [{id: '$id', text: '$value'}]});");
    }

    /**
     * @When /^(?:|I )fill in ajax select2 input "(?P<field>(?:[^"]|\\")*)" with id "(?P<id>(?:[^"]|\\")*)" and value "(?P<value>(?:[^"]|\\")*)"$/
     */
    public function iFillInAjaxSelectInputWithAndSelect($field, $id, $value)
    {
        $this->getSession()->executeScript("
            var newOption = new Option('$value', '$id', true, true);
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
        $this->getSession()->wait(14000);
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
        // Waits until a CSS element is present AND visible in the DOM (up to 20s).
        // "Visible" = getBoundingClientRect().height > 0: avoids false positives where Vue inserts
        // the element into the DOM before the panel open animation ends (element present
        // but hidden), which would make the wait pass but crash the subsequent select/fill.
        // Searches simultaneously by name AND id: [name='X'] also tries #X and vice versa,
        // because some Vue fields only expose one of the two depending on the component.
        $alt = null;
        if (preg_match("/^\[name='([^']+)'\]$/", $selector, $m)) {
            $alt = '#'.$m[1];
        } elseif (preg_match('/^#([\w\-]+)$/', $selector, $m)) {
            $alt = "[name='".$m[1]."']";
        }

        $escaped = addslashes($selector);
        $altPart = null !== $alt ? " || document.querySelector('".addslashes($alt)."')" : '';
        $condition = "(function(){var e=document.querySelector('{$escaped}'){$altPart};return e!==null&&e.getBoundingClientRect().height>0;})()";

        $this->getSession()->wait(20000, $condition);

        $found = $this->getSession()->getPage()->find('css', $selector);
        if (null === $found && null !== $alt) {
            $found = $this->getSession()->getPage()->find('css', $alt);
        }
        if (null === $found) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Element '{$selector}' did not appear and become visible within 20 seconds.",
                $this->getSession()
            );
        }
    }

    /**
     * Some <select> fields (e.g. the diagnostic form's "extra_theme_fr_*"
     * topic dropdowns) exist in the DOM from the start with only the empty
     * placeholder option, and are populated via an AJAX call triggered by a
     * previous field's selection. Waiting for the <select> element itself
     * (iWaitForElementToAppear) is not enough — it is already present and
     * visible before the AJAX response adds the real options, so a
     * subsequent "I select" fails with "option not found". This waits for
     * an actual non-empty option to exist.
     *
     * @When /^I wait for the options to load in "([^"]*)"$/
     */
    public function iWaitForTheOptionsToLoadIn($field): void
    {
        $field = $this->fixStepArgument($field);
        $escaped = addslashes($field);
        $condition = "(function(){var e=document.querySelector('#{$escaped}')||document.querySelector(\"[name='{$escaped}']\");".
            "if(!e) return false;".
            "for (var i=0;i<e.options.length;i++){ if(e.options[i].value!=='') return true; }".
            "return false;})()";

        $this->getSession()->wait(20000, $condition);
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
     * Types text into a field one character at a time, dispatching a real
     * 'input' event after each keystroke. Unlike fillField() (which sets the
     * whole value in one jump), this is needed for widgets like the PrimeVue
     * autocomplete on the messaging "to" field, whose suggestion search is
     * only triggered by the incremental per-keystroke input events a real
     * user produces while typing.
     *
     * @When /^I type character by character "([^"]*)" into field "([^"]*)"$/
     */
    public function iTypeCharacterByCharacterIntoField($text, $field): void
    {
        $field = $this->fixStepArgument($field);
        $text = $this->fixStepArgument($text);

        $node = $this->getSession()->getPage()->findField($field);
        if (null === $node) {
            throw new \Behat\Mink\Exception\ElementNotFoundException(
                $this->getSession(),
                'form field',
                'id|name|label|value|placeholder',
                $field
            );
        }

        $xpath = json_encode($node->getXpath());
        $accumulated = '';
        foreach (mb_str_split($text) as $char) {
            $accumulated .= $char;
            $this->getSession()->evaluateScript(sprintf(
                "(function(){var el=document.evaluate(%s,document,null,XPathResult.FIRST_ORDERED_NODE_TYPE,null).singleNodeValue;".
                "if(!el) return false;".
                "var setter=Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype,'value').set;".
                "setter.call(el,%s);".
                "el.dispatchEvent(new Event('input',{bubbles:true}));".
                "return true;})()",
                $xpath,
                json_encode($accumulated)
            ));
            $this->getSession()->wait(150);
        }
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
     * Checks a radio button by finding an element containing the label text, then checking
     * the first input inside its parent. Uses jQuery — only works on pages that load jQuery.
     * Prefer iCheckTheRadioButton() (which uses Mink's findField) for standard radio inputs.
     *
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
     * Selects a radio button directly via JavaScript instead of a native
     * WebDriver click. Needed for PrimeVue's radiobutton component: the real
     * `<input type="radio">` is visually replaced by a styled sibling icon,
     * so clicking the icon can land on a screen position the underlying
     * input doesn't occupy ("element click intercepted"), while clicking the
     * input directly can fail as "element not interactable" (it's present in
     * the DOM but visually hidden by the component's own styling). Setting
     * `.checked` and dispatching the events the component listens for
     * sidesteps both failure modes.
     *
     * @When /^I select the radio button matching "([^"]*)" via javascript$/
     */
    public function iSelectRadioButtonViaJavascript($selector)
    {
        $this->getSession()->executeScript(
            "(function(){var el=document.querySelector(" . json_encode($selector) . ");".
            "if(!el) return;".
            "el.checked=true;".
            "el.dispatchEvent(new Event('change',{bubbles:true}));".
            "el.dispatchEvent(new Event('input',{bubbles:true}));".
            "})();"
        );
    }

    /**
     * Clicks an element via a native JS `.click()` instead of a WebDriver
     * click. Needed for elements that appear via a `.show()` toggle right
     * before the click (e.g. a submit button in a container that was
     * `display:none` until an AJAX success callback revealed it): WebDriver's
     * click requires the element to already be within the viewport and can
     * throw "element not interactable" immediately after such a reveal, even
     * though the element exists and `scrollIntoView` + a JS click land fine.
     *
     * @When /^I click the "([^"]*)" element via javascript$/
     */
    public function iClickTheElementViaJavascript($selector)
    {
        $this->getSession()->executeScript(
            "(function(){var el=document.querySelector(" . json_encode($selector) . ");".
            "if(!el) return;".
            "el.scrollIntoView({block:'center'});".
            "el.click();".
            "})();"
        );
    }

    /**
     * Adds an option to an existing native multi-select without deselecting
     * any option already selected. Needed for PrimeVue's MultiSelect
     * component ("p-select" with a real backing `<select multiple>`): a
     * native WebDriver click sequence to add one more option while keeping
     * existing ones selected is unreliable through the component's styled
     * overlay, so this sets `.selected` directly on the matching `<option>`
     * and dispatches the events the component listens for.
     *
     * @When /^I add option "([^"]*)" to select "([^"]*)" via javascript$/
     */
    public function iAddOptionToSelectViaJavascript($value, $fieldId)
    {
        $selector = 'option[value='.json_encode((string) $value).']';
        $this->getSession()->executeScript(
            "(function(){var sel=document.getElementById(" . json_encode($fieldId) . ");".
            "if(!sel) return;".
            "var opt=sel.querySelector(" . json_encode($selector) . ");".
            "if(!opt) return;".
            "opt.selected=true;".
            "sel.dispatchEvent(new Event('change',{bubbles:true}));".
            "sel.dispatchEvent(new Event('input',{bubbles:true}));".
            "})();"
        );
    }

    /**
     * @Then I click the :selector element
     */
    public function iClickTheElement($selector)
    {
        $attempts = 3;
        for ($i = 1; $i <= $attempts; ++$i) {
            try {
                $element = $this->getSession()->getPage()->find('css', $selector);
                if (null === $element) {
                    throw new \Behat\Mink\Exception\ElementNotFoundException(
                        $this->getSession(),
                        'css element',
                        'css',
                        $selector
                    );
                }

                // Same root cause family as clickLink() above: a native
                // click on a link (or an icon nested inside one, e.g. a
                // <span class="mdi-pencil"> action icon wrapped in <a>) was
                // found to sometimes register without navigating. Look for
                // the closest <a href> ancestor (or self) and visit it
                // directly when one exists.
                // Use the .href *property* (browser-resolved absolute URL),
                // not getAttribute('href') (the raw, possibly relative HTML
                // attribute) — visit() below resolves against base_url, so a
                // relative attribute like "session_edit.php?..." would land
                // on the wrong path (e.g. missing a "/main/session/" prefix)
                // instead of the current page's directory, producing a 404.
                $href = $this->getSession()->evaluateScript(sprintf(
                    "(function(){var el=document.evaluate(%s,document,null,XPathResult.FIRST_ORDERED_NODE_TYPE,null).singleNodeValue;".
                    "if(!el) return null;var a=el.closest('a');".
                    "if(!a||!a.getAttribute('href')||a.getAttribute('href').indexOf('javascript:')===0) return null;".
                    "return a.href;})()",
                    json_encode($element->getXpath())
                ));
                if ($href) {
                    $this->visit($href);
                } else {
                    $element->click();
                }

                return;
            } catch (\WebDriver\Exception $e) {
                if ($i === $attempts) {
                    throw $e;
                }
                $this->getSession()->wait(1000);
            }
        }
    }

    /**
     * Clicks the first element matching $selector inside the table row (<tr>)
     * that contains $text. Needed when the clickable element itself has no
     * text (e.g. an icon-only action link) and the identifying text sits in
     * a sibling cell of the same row — e.g. a tracking table listing several
     * exercises, each row with its own "correct this attempt" icon.
     *
     * @When /^I click element "([^"]*)" in the row containing text "([^"]*)"$/
     */
    public function iClickElementInRowContainingText(string $selector, string $text): void
    {
        $safeSelector = addslashes($selector);
        $safeText = addslashes($text);
        $result = $this->getSession()->evaluateScript(<<<JS
(function() {
    var rows = document.querySelectorAll('tr');
    for (var i = 0; i < rows.length; i++) {
        if (rows[i].textContent.indexOf('$safeText') !== -1) {
            var el = rows[i].querySelector('$safeSelector');
            if (el) {
                el.click();
                return true;
            }
        }
    }
    return false;
})();
JS);
        if (!$result) {
            throw new \Exception(sprintf('No "%s" element found in a row containing text "%s"', $selector, $text));
        }
    }

    /**
     * @When /^I click element "([^"]*)" containing text "([^"]*)"$/
     */
    public function iClickElementContainingText(string $selector, string $text): void
    {
        $safeSelector = addslashes($selector);
        $safeText = addslashes($text);
        $result = $this->getSession()->evaluateScript(<<<JS
(function() {
    var els = document.querySelectorAll('$safeSelector');
    for (var i = 0; i < els.length; i++) {
        if (els[i].textContent.indexOf('$safeText') !== -1) {
            els[i].click();
            return true;
        }
    }
    return false;
})();
JS);
        if (!$result) {
            throw new \Exception(sprintf('No "%s" element containing text "%s" found', $selector, $text));
        }
    }

    /**
     * Clicks the first element matching $selector inside the first element
     * matching $containerSelector whose text contains $text. Needed when a
     * page repeats the same container markup once per row of a list (e.g.
     * one "add learner" box per tutor account) and the only thing telling
     * containers apart is a text label elsewhere inside them — picking the
     * first $selector match on the whole page is unreliable once more than
     * one such container exists (e.g. leftover fixture accounts sharing the
     * same page).
     *
     * Uses a real Mink/WebDriver click rather than a synthetic JS
     * `.click()` — some widgets (e.g. select2's rendered selection box)
     * only open in response to a genuine user-driven click event.
     *
     * @When /^I click element "([^"]*)" in the container "([^"]*)" containing text "([^"]*)"$/
     */
    public function iClickElementInContainerContainingText(string $selector, string $containerSelector, string $text): void
    {
        $containers = $this->getSession()->getPage()->findAll('css', $containerSelector);
        foreach ($containers as $container) {
            // Case-insensitive, matching Mink's own pageTextContains() behavior.
            if (false !== mb_stripos($container->getText(), $text)) {
                $el = $container->find('css', $selector);
                if (null !== $el) {
                    $el->click();

                    return;
                }
            }
        }

        throw new \Exception(sprintf(
            'No "%s" element found in a "%s" container containing text "%s"',
            $selector,
            $containerSelector,
            $text
        ));
    }

    /**
     * @Then I should see the :selector element
     */
    public function iShouldSeeTheElement($selector)
    {
        $this->assertSession()->elementExists('css', $selector);
    }

    /**
     * Asserts $text appears inside the first element matching
     * $containerSelector whose own text also contains $containerText.
     * Companion to iClickElementInContainerContainingText — needed for the
     * same repeated-container pages, so an assertion can't be satisfied by a
     * coincidental match inside a different row's container.
     *
     * @Then /^I should see "([^"]*)" in the container "([^"]*)" containing text "([^"]*)"$/
     */
    public function iShouldSeeInContainerContainingText(string $text, string $containerSelector, string $containerText): void
    {
        $containers = $this->getSession()->getPage()->findAll('css', $containerSelector);
        foreach ($containers as $container) {
            // Case-insensitive, matching Mink's own pageTextContains() behavior.
            if (false !== mb_stripos($container->getText(), $containerText) && false !== mb_stripos($container->getText(), $text)) {
                return;
            }
        }

        throw new \Exception(sprintf(
            'No "%s" container containing text "%s" also contains "%s"',
            $containerSelector,
            $containerText,
            $text
        ));
    }

    /**
     * Checks the Nth (1-indexed) checkbox on the page, in document order.
     * Needed when a checkbox's id/name is a dynamic database-generated value
     * (e.g. a doodle poll's per-timeslot option checkboxes, named
     * "options[2551]", "options[2552]", ... — a different numeric id every
     * run) and the intent is simply "the 1st box" / "the 2nd box", not a
     * specific known id/name/label.
     *
     * @Then I check checkbox number :n
     */
    public function iCheckCheckboxNumber($n)
    {
        $index = ((int) $n) - 1;
        $result = $this->getSession()->evaluateScript(<<<JS
(function() {
    var checkboxes = document.querySelectorAll('input[type="checkbox"]');
    var el = checkboxes[$index];
    if (!el) return false;
    if (!el.checked) el.click();
    return true;
})();
JS);
        if (!$result) {
            throw new \Exception(sprintf('No checkbox number %s found on the page', $n));
        }
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
     * Resets any CSS zoom or transform scale applied to the page (e.g. after a zoom-out step),
     * restoring the browser to its default 100% view.
     *
     * @When /^I reset zoom$/
     */
    public function resetZoom(): void
    {
        $this->getSession()->executeScript("
            document.body.style.zoom = '';
            document.documentElement.style.transform = '';
        ");
        $this->getSession()->wait(300);
    }

    /**
     * Scales the page down to 25% using CSS zoom (Chrome) or CSS transform/scale (Firefox fallback).
     * Useful when an element is outside the visible viewport and Behat cannot click it at normal zoom.
     * Call "I reset zoom" afterwards to restore the page before the next interaction.
     *
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
     * Sets a select2 field directly to a known option value, bypassing the
     * search/click UI. Needed for at least one AJAX-backed multi-select (the
     * "courses" field on add_courses_to_session.php): its underlying
     * `<select>` starts with zero `<option>` elements (options only ever
     * exist inside select2's own in-memory results, never mirrored onto the
     * native element), so `.val(['id'])` alone is a no-op — there is no
     * matching `<option>` node to select — and the real HTML form
     * submission ends up sending an empty array. Appending a genuine
     * `<option>` first gives the native select something to actually select,
     * which select2 then picks up via its own 'change' handling.
     *
     * @When /^I set select2 field "([^"]*)" to value "([^"]*)"$/
     */
    public function iSetSelect2FieldToValue($fieldId, $value)
    {
        $session = $this->getSession();
        $session->wait(20000, "document.getElementById('" . $fieldId . "') !== null");
        $session->executeScript(
            "(function(){var sel=document.getElementById('" . $fieldId . "');".
            "if(!sel.querySelector(\"option[value='" . $value . "']\")){".
            "sel.appendChild(new Option('" . $value . "','" . $value . "',true,true));".
            "}else{sel.querySelector(\"option[value='" . $value . "']\").selected=true;}".
            "$('#" . $fieldId . "').trigger('change');})();"
        );
        $session->wait(500, "document.getElementById('" . $fieldId . "').value !== ''");
    }

    /**
     * @When /^I type and select "([^"]*)" in select2 field "([^"]*)"$/
     */
    public function iTypeAndSelectInSelect2($value, $fieldId)
    {
        $session = $this->getSession();
        // The target select can be mounted asynchronously (e.g. an AJAX-loaded
        // wizard step) after a preceding "wait very long for the page to be
        // loaded" fixed sleep has already elapsed. Poll for its actual
        // presence first so select2('open') isn't called as a silent no-op
        // on an empty jQuery selection.
        $session->wait(20000, "document.getElementById('" . $fieldId . "') !== null");
        $session->executeScript("$('#" . $fieldId . "').select2('open');");
        $session->wait(500);
        $session->executeScript("
            var input = document.querySelector('.select2-search__field');
            if (input) {
                input.value = '" . $value . "';
                input.dispatchEvent(new Event('input', {bubbles: true}));
                input.dispatchEvent(new KeyboardEvent('keyup', {bubbles: true}));
            }
        ");
        // select2 renders the same "select2-results__option" class for real
        // results AND system messages (e.g. "Loading…", "No results found").
        // Waiting on a fixed timeout and picking the first match could grab a
        // message node instead of a real option, leaving the dropdown open
        // and blocking the next step's click (ElementClickIntercepted) or
        // going stale once the real results replace it (StaleElementReference).
        $session->wait(10000, "document.querySelector('.select2-results__option:not(.select2-results__message):not(.loading-results)') !== null");
        $page = $session->getPage();
        $result = $page->find(
            'css',
            '.select2-results__option--highlighted:not(.select2-results__message):not(.loading-results), .select2-results__option:not(.select2-results__message):not(.loading-results)'
        );
        if ($result) {
            $result->click();
        }
        // Multi-value select2 fields deliberately keep the dropdown open
        // after a selection (so more items can be picked), so waiting for
        // the results list to disappear on its own never resolves there.
        // Close it explicitly so it doesn't overlap and intercept the click
        // on whatever element the next step targets.
        $session->executeScript("$('#" . $fieldId . "').select2('close');");
        $session->wait(500, "document.querySelector('.select2-results__option') === null");
    }

    /**
     * Types into the search box of a select2 dropdown that is already open
     * (e.g. opened by a preceding "I click element ... in the container ..."
     * step) and clicks the first real result. Unlike iTypeAndSelectInSelect2,
     * this never needs a fixed field id — useful when a page repeats the
     * same select2 markup once per row (e.g. one "add learner" box per tutor
     * account) and the underlying <select>'s id is dynamically suffixed with
     * a database id (e.g. "add_user_to_87019_user_id"), so it can never be
     * hardcoded in a step.
     *
     * @When /^I type "([^"]*)" and select the first result in the open select2 dropdown$/
     */
    public function iTypeAndSelectInOpenSelect2(string $value): void
    {
        $session = $this->getSession();
        $session->wait(5000, "document.querySelector('.select2-search__field') !== null");
        $session->executeScript("
            var input = document.querySelector('.select2-search__field');
            if (input) {
                input.value = '" . addslashes($value) . "';
                input.dispatchEvent(new Event('input', {bubbles: true}));
                input.dispatchEvent(new KeyboardEvent('keyup', {bubbles: true}));
            }
        ");
        $session->wait(10000, "document.querySelector('.select2-results__option:not(.select2-results__message):not(.loading-results)') !== null");
        $page = $session->getPage();
        $result = $page->find(
            'css',
            '.select2-results__option--highlighted:not(.select2-results__message):not(.loading-results), .select2-results__option:not(.select2-results__message):not(.loading-results)'
        );
        if (null === $result) {
            throw new \Exception(sprintf('No select2 result found for "%s"', $value));
        }
        $result->click();
    }

    /**
     * @When /^I set hidden field "([^"]*)" to "([^"]*)"$/
     */
    public function iSetHiddenField($fieldId, $value)
    {
        // Mirrors fillField()'s fix above: setting .value alone isn't seen by
        // any JS validation/sync tied to the field's 'input'/'change' events
        // (e.g. a start/end date range check), which can silently reassert
        // its own default over our value later. Dispatching both events
        // makes the change actually register.
        $this->getSession()->executeScript(
            "(function(){var el=document.getElementById('" . $fieldId . "');".
            "if(!el) return;".
            "el.value='" . $value . "';".
            "el.dispatchEvent(new Event('input',{bubbles:true}));".
            "el.dispatchEvent(new Event('change',{bubbles:true}));".
            "})()"
        );
    }

    /**
     * Asserts that exactly $count elements matching the given CSS selector are present on the page.
     * Throws an exception if the actual count differs from the expected count.
     *
     * @Then /^I should see (\d+) elements? matching "([^"]*)"$/
     */
    public function iShouldSeeElementsMatching(int $count, string $css): void
    {
        $actual = count($this->getSession()->getPage()->findAll('css', $css));
        if ($actual !== $count) {
            throw new \Exception("Expected {$count} elements matching '{$css}', found {$actual}");
        }
    }

    /**
     * Sets a date/time value on a Flatpickr date picker field via its JavaScript API.
     * Falls back to direct input value injection if Flatpickr is not initialized on the element.
     *
     * Date format: "YYYY-MM-DD" for date-only fields, "YYYY-MM-DD HH:MM:SS" for datetime fields.
     *
     * Example:
     *   And I set flatpickr field "start_date" to "2026-06-02"
     *   And I set flatpickr field "event_date_start" to "2026-06-02 08:00:00"
     *
     * @When /^I set flatpickr field "([^"]*)" to "([^"]*)"$/
     */
    public function iSetFlatpickrField($fieldId, $value)
    {
        $this->getSession()->executeScript(
            "var el = document.getElementById('" . $fieldId . "');
             if (el && el._flatpickr) {
                 el._flatpickr.setDate('" . $value . "', true);
             } else {
                 el.value = '" . $value . "';
             }"
        );
    }

    /**
     * Selects a date range on a PrimeVue calendar picker by navigating the graphical calendar.
     * Clicks the start date first, then navigates to the end month and clicks the end date,
     * then confirms with the "Select" button.
     *
     * Date format: "YYYY-MM-DD"
     *
     * Example:
     *   And I set datepicker "calendar-range" range from "2026-06-01" to "2026-06-30"
     *
     * @When /^I set datepicker "([^"]*)" range from "([^"]*)" to "([^"]*)"$/
     */
    public function iSetDatepickerRange($inputId, $startDate, $endDate)
    {
        $session = $this->getSession();

        $startDay   = (int) date('j', strtotime($startDate));
        $startMonth = (int) date('n', strtotime($startDate));
        $startYear  = (int) date('Y', strtotime($startDate));
        $endDay     = (int) date('j', strtotime($endDate));
        $endMonth   = (int) date('n', strtotime($endDate));
        $endYear    = (int) date('Y', strtotime($endDate));

        // Open the range datepicker (ONE input handles both dates)
        $input = $session->getPage()->find('css', '#' . $inputId);
        if ($input) { $input->click(); }

        // Wait for calendar panel to appear
        for ($attempt = 0; $attempt < 25; $attempt++) {
            $session->wait(200);
            if ($session->getPage()->find('css', '[data-pc-section="month"]')) break;
        }

        // Step 1 — navigate to start month: read displayed month ONCE then navigate the exact diff
        $cur   = $this->calendarReadMonth();
        $steps = ($startYear - $cur['year']) * 12 + ($startMonth - $cur['month']);
        $this->calendarNavigateSteps($steps);
        $this->calendarClickDay($startDay);

        // Step 2 — navigate to end month: diff relative to start month (no DOM re-read needed)
        $steps = ($endYear - $startYear) * 12 + ($endMonth - $startMonth);
        $this->calendarNavigateSteps($steps);
        $this->calendarClickDay($endDay);

        // Click "Select" to commit internalValue → modelValue and close panel
        $footerBtns = $session->getPage()->findAll('css', '.base-calendar-footer .p-button');
        foreach ($footerBtns as $btn) {
            if (stripos(trim($btn->getText()), 'Select') !== false) {
                $btn->click();
                $session->wait(400);
                break;
            }
        }
    }

    /**
     * @When /^I set datepicker "([^"]*)" to "([^"]*)"$/
     */
    public function iSetDatepicker($inputId, $date)
    {
        $session = $this->getSession();

        $day   = (int) date('j', strtotime($date));
        $month = (int) date('n', strtotime($date));
        $year  = (int) date('Y', strtotime($date));

        $input = $session->getPage()->find('css', '#' . $inputId);
        if ($input) { $input->click(); }

        for ($attempt = 0; $attempt < 25; $attempt++) {
            $session->wait(200);
            if ($session->getPage()->find('css', '[data-pc-section="month"]')) break;
        }

        $cur   = $this->calendarReadMonth();
        $steps = ($year - $cur['year']) * 12 + ($month - $cur['month']);
        $this->calendarNavigateSteps($steps);
        $this->calendarClickDay($day);

        $session->wait(300);
        $footerBtns = $session->getPage()->findAll('css', '.base-calendar-footer .p-button');
        foreach ($footerBtns as $btn) {
            if (stripos(trim($btn->getText()), 'Select') !== false) {
                $btn->click();
                $session->wait(400);
                break;
            }
        }
    }

    /**
     * Internal helper — reads the month and year currently displayed in the open PrimeVue calendar panel.
     * Used by: iSetDatepicker(), iSetDatepickerRange()
     */
    private function calendarReadMonth(): array
    {
        $session = $this->getSession();
        $monthNames = [
            'January'   => 1,
            'February'  => 2,
            'March'     => 3,
            'April'     => 4,
            'May'       => 5,
            'June'      => 6,
            'July'      => 7,
            'August'    => 8,
            'September' => 9,
            'October'   => 10,
            'November'  => 11,
            'December'  => 12,
        ];
        for ($w = 0; $w < 10; $w++) {
            $session->wait(200);
            $monthEl = $session->getPage()->find('css', '[data-pc-section="month"]');
            $yearEl  = $session->getPage()->find('css', '[data-pc-section="year"]');
            if ($monthEl && $yearEl) {
                $mn = $monthNames[trim($monthEl->getText())] ?? 0;
                $yr = (int) trim($yearEl->getText());
                if ($mn > 0 && $yr > 2000) return ['month' => $mn, 'year' => $yr];
            }
        }
        return ['month' => (int) date('n'), 'year' => (int) date('Y')];
    }

    /**
     * Internal helper — clicks the prev/next arrow of the PrimeVue calendar the required number of times.
     * Negative steps go backward (prev), positive steps go forward (next).
     * Used by: iSetDatepicker(), iSetDatepickerRange()
     */
    private function calendarNavigateSteps(int $steps): void
    {
        if ($steps === 0) return;
        $session = $this->getSession();
        $btnCss = $steps < 0 ? '.p-datepicker-prev-button' : '.p-datepicker-next-button';
        for ($i = 0; $i < abs($steps); $i++) {
            $btn = $session->getPage()->find('css', $btnCss);
            if ($btn) { $btn->click(); $session->wait(500); }
        }
    }

    /**
     * Internal helper — clicks the cell matching the given day number in the currently displayed month.
     * Ignores days belonging to adjacent months (data-p-other-month="true").
     * Used by: iSetDatepicker(), iSetDatepickerRange()
     */
    private function calendarClickDay(int $day): void
    {
        $session = $this->getSession();
        $session->wait(300);
        $spans = $session->getPage()->findAll(
            'css',
            'td[data-pc-section="daycell"]:not([data-p-other-month="true"]) span[data-pc-section="day"]'
        );
        foreach ($spans as $span) {
            if ((int) trim($span->getText()) === $day) {
                $span->click();
                $session->wait(600);
                break;
            }
        }
    }

    /**
     * Types and selects a value in a Select2 multiple field (search input always visible, no dropdown to open).
     * Unlike iTypeAndSelectInSelect2(), targets the field by its specific container ID instead of a global
     * querySelector — required when multiple Select2 fields coexist on the same page.
     *
     * Example:
     *   And I type and select "theme1" in inline select2 "extra_theme_fr"
     *
     * @When /^I type and select "([^"]*)" in inline select2 "([^"]*)"$/
     */
    public function iTypeAndSelectInInlineSelect2($value, $fieldId)
    {
        $session = $this->getSession();
        $session->executeScript("
            var container = document.getElementById('select2-" . $fieldId . "-container');
            if (container) {
                var textarea = container.closest('.select2-selection').querySelector('.select2-search__field');
                if (textarea) {
                    textarea.focus();
                    textarea.value = '" . $value . "';
                    textarea.dispatchEvent(new Event('input', {bubbles: true}));
                    textarea.dispatchEvent(new KeyboardEvent('keyup', {bubbles: true}));
                }
            }
        ");
        $session->wait(2000);
        $page = $session->getPage();
        $result = $page->find('css', '.select2-results__option--highlighted, .select2-results__option');
        if ($result) {
            $result->click();
        }
        $session->wait(500);
    }

    /**
     * Selects the first non-empty option of a native <select> field via JavaScript.
     * Useful when option values are dynamic (loaded from DB) and not known in advance.
     *
     * Example:
     *   And I select the first option from "extra_ecouter"
     *
     * @When /^I select the first option from "([^"]*)"$/
     */
    public function iSelectFirstOptionFrom(string $fieldName): void
    {
        $js = "var s=document.querySelector('select[name=\"" . $fieldName . "\"],select#" . $fieldName . "');"
            . "if(s){var f=Array.from(s.options).find(function(o){return o.value!==''});"
            . "if(f){s.value=f.value;s.dispatchEvent(new Event('change',{bubbles:true}));}}";
        $this->getSession()->executeScript($js);
    }

    /**
     * @AfterStep
     *
     * When a step fails, dump the full HTML of the page and a form-summary
     * into tests/behat/behat_debug/ so AI (or a developer) can analyse
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

    /**
     * Adds an LP item by simulating the Sortable.js AJAX call used for drag-and-drop.
     *
     * Handles two DOM layouts:
     *  - Quiz items  : text is inside  <a class="link_with_id">Title</a>
     *  - Document items: text is a bare text-node in <div class="item_data">, outside the link
     *
     * @When /^I add LP item "([^"]*)" from the resource panel$/
     */
    public function iAddLpItemFromResourcePanel(string $title): void
    {
        $safeTitle = addslashes($title);

        $infoJs = <<<JS
(function() {
    var items = document.querySelectorAll('ul.lp_resource li');
    for (var i = 0; i < items.length; i++) {
        var li   = items[i];
        var link = li.querySelector('.link_with_id');

        // Strategy 1 – quiz: title attr on <li> (e.g. title="QRU and Image Selection exercise")
        if (li.getAttribute('title') === '{$safeTitle}') {
            return JSON.stringify({ id: li.getAttribute('id'), type: link ? link.getAttribute('data_type') : null });
        }

        // Strategy 2 – quiz: text directly inside .link_with_id
        if (link) {
            var linkText = link.textContent.replace(/ /g, ' ').trim();
            if (linkText === '{$safeTitle}') {
                return JSON.stringify({ id: li.getAttribute('id'), type: link.getAttribute('data_type') });
            }
        }

        // Strategy 3 – document: bare text-node(s) inside div.item_data (text lives outside the <a>)
        var itemData = li.querySelector('.item_data');
        if (itemData) {
            var raw = '';
            for (var j = 0; j < itemData.childNodes.length; j++) {
                if (itemData.childNodes[j].nodeType === 3) raw += itemData.childNodes[j].textContent;
            }
            if (raw.trim() === '{$safeTitle}') {
                return JSON.stringify({ id: li.getAttribute('id'), type: link ? link.getAttribute('data_type') : 'document' });
            }
        }
    }
    return null;
})();
JS;

        $result = $this->getSession()->evaluateScript($infoJs);
        if (!$result) {
            throw new \Exception("LP item '{$title}' not found in resource panel");
        }

        $data   = json_decode($result, true);
        $itemId = $data['id'];
        $type   = $data['type'];

        $currentUrl = $this->getSession()->getCurrentUrl();
        parse_str(parse_url($currentUrl, PHP_URL_QUERY) ?? '', $params);
        $lpId = $params['lp_id'] ?? '';
        $cid  = $params['cid']  ?? '';
        $sid  = $params['sid']  ?? 0;

        $escapedTitle = str_replace("'", "\\'", $title);

        $this->getSession()->executeScript("
            \$.ajax({
                url: '/main/inc/ajax/lp.ajax.php',
                data: {
                    lp_id: '{$lpId}', cid: '{$cid}', sid: '{$sid}',
                    a: 'add_lp_item', id: '{$itemId}', type: '{$type}',
                    title: '{$escapedTitle}',
                    parent_id: '', previous_id: 0
                },
                async: false
            });
        ");

        $this->getSession()->wait(1000);
        $this->getSession()->reload();
        $this->waitVeryLongForThePageToBeLoaded();
    }

    /**
     * Switches ChromeDriver context into the iframe with the given name attribute.
     * All subsequent Behat interactions will target the iframe's DOM until switchback.
     * Used for Chamilo exercises displayed inside an <iframe name="content_name">.
     *
     * Example:
     *   And I switch to the iframe "content_name"
     *
     * @When /^I switch to the iframe "([^"]*)"$/
     */
    public function iSwitchToIframe(string $name): void
    {
        $this->getSession()->getDriver()->switchToIFrame($name);
        $this->getSession()->wait(500);
    }

    /**
     * Switches ChromeDriver context back to the main page after having entered an iframe.
     * Must be called after iSwitchToIframe() once interactions inside the iframe are done.
     *
     * Example:
     *   And I switch back to the main window
     *
     * @When /^I switch back to the main window$/
     */
    public function iSwitchBackToMainWindow(): void
    {
        $this->getSession()->getDriver()->switchToIFrame(null);
        $this->getSession()->wait(500);
    }

    /**
     * Fills the first <textarea> found on the page with the given value via JavaScript.
     * Used for open-question exercises inside iframes where there is only one textarea.
     *
     * Example:
     *   And I fill in the first textarea with "example"
     *
     * @When /^I fill in the first textarea with "([^"]*)"$/
     */
    public function iFillInFirstTextareaWith(string $value): void
    {
        $safe = addslashes($value);
        $this->getSession()->executeScript("
            var ta = document.querySelector('textarea');
            if (ta) {
                ta.value = '{$safe}';
                ta.dispatchEvent(new Event('input', { bubbles: true }));
                ta.dispatchEvent(new Event('change', { bubbles: true }));
            }
        ");
        $this->getSession()->wait(300);
    }
}
