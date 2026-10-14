# Support Bot v1 administration coverage for #8754.
#
# Verified against the live Vue implementation before this scenario was added:
# - /admin/support-bot is the real management page.
# - /admin/support-bot/create creates a draft and redirects to /admin/support-bot/<id>/edit.
# - the editor exposes stable name attributes for title/node/option inputs.
# - row actions are icon-only BaseButtons, so the dedicated steps scope Delete
#   to the row containing this scenario's unique title instead of clicking a
#   translated label or the first matching action on the page.
# - non-admin access is denied by the Vue router's requiresAdmin guard, which
#   replace-navigates away from /admin/support-bot rather than returning a
#   Symfony 403 from the SPA shell.
#
# Deliberately does NOT publish the disposable tree. Publishing automatically
# archives the portal's currently-published tree, which would make this test
# destructive on a reused development database. Runtime/publish/escalation was
# verified manually against the live UI; this file keeps automated browser
# coverage self-contained by exercising draft create/read/update/delete and
# role-based access only.
@supportBot
Feature: Support Bot administration
  In order to maintain guided support flows safely
  As a platform administrator
  I want to manage Support Bot drafts while non-administrators are denied access

  Scenario: Administrator can create, update, read and delete a Support Bot draft
    Given I am a platform administrator
    And the Support Bot setting is enabled for this scenario
    When I create a disposable Support Bot draft
    Then the disposable Support Bot draft should be editable
    When I rename the disposable Support Bot draft
    And I open the Support Bot tree list
    Then the disposable Support Bot draft should be listed
    When I delete the disposable Support Bot draft
    Then the disposable Support Bot draft should no longer be listed

  Scenario: Non-administrators cannot access Support Bot administration
    Given I am not logged
    And I am a student
    When I am on "/admin/support-bot"
    And I wait for the page to be loaded
    Then the page path should not start with "/admin/support-bot"
