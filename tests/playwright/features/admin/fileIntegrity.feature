# Ported from tests/behat/features/admin/fileIntegrity.feature — every field
# id/button label (file_integrity.html.twig) and the access control itself
# (SecurityController::fileIntegrity(), #[IsGranted('ROLE_ADMIN')]) are
# unchanged, BUT the last scenario's assertion had to change.
#
# ExceptionListener (src/CoreBundle/EventListener/ExceptionListener.php)
# explicitly skips its own redirect-conversion logic in dev/test env
# ("Leave a genuine 403 for the profiler"), so under Behat's own env this
# scenario's literal 403 assertion held. This suite runs against a real
# prod-mode install (this project's whole CI/local test box installs and
# runs in prod, per playwright.yml), where that SAME listener catches
# AccessDeniedHttpException and returns a 302 redirect to the app shell
# instead — confirmed live, page.goto() (which follows redirects) reports a
# final 200 at "/", never a 403. A raw status-code assertion can never
# observe "denied" under prod's real, intentional behavior (a friendly
# redirect, not a bare 403 page) — the meaningful assertion is what a
# non-admin actually sees: redirected away, with none of the file-integrity
# page's own content rendered.
Feature: File integrity monitoring
  In order to detect unexpected changes to the installed files
  As a global administrator
  I want to review the file integrity report and control scanning
  But as a security boundary, pausing or rebaselining must require my password again
Feature: File integrity monitoring
  In order to detect unexpected changes to the installed files
  As a global administrator
  I want to review the file integrity report and control scanning
  But as a security boundary, pausing or rebaselining must require my password again

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Global administrator sees the report and the admin actions
    Given I am on "/admin/security/file-integrity"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "File integrity"
    And I should see "Actions"

  Scenario: Global administrator can run a scan on demand
    Given I am on "/admin/security/file-integrity"
    And I wait for the page to be loaded
    And I press "Run a scan now"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "Last scan"

  Scenario: Pausing alerting is refused with a wrong password
    Given I am on "/admin/security/file-integrity"
    And I wait for the page to be loaded
    And I fill in "file-integrity-pause-password" with "not-the-right-password"
    And I press "Pause for 1 hour"
    And I wait for the page to be loaded
    Then I should not see "Alerting is currently paused for maintenance."

  Scenario: Non-administrators cannot access the file integrity page
    Given I am a student
    And I am on "/admin/security/file-integrity"
    And I wait for the page to be loaded
    Then the response status code should be 200
    And I should not see "Run a scan now"
