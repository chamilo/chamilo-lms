# Covers /reporting/admin/student-bosses, the Vue port of
# main/my_space/tc_report.php ("Student's superior follow up").
#
# As in the legacy page, each student boss gets its own column listing its
# learners, with an "Add learner" search + "Add" button below. Only platform
# administrators can assign learners: the legacy learner search
# (user_manager.ajax.php?a=user_by_role) and save endpoint
# (statistics.ajax.php?a=add_student_to_boss) both required
# api_is_platform_admin(), so the add controls must not appear for anyone else.
#
# Self-contained: the learner assigned here is created by the first scenario
# and permanently deleted by the last one, which also removes its boss
# relation (user_rel_user cascades on user deletion). The notification sent
# to the boss is removed from its inbox, but the inbox delete is per receiver,
# so its message row remains. Relies on the seeded student boss abaggins
# (yarn test:playwright:seed).
Feature: Student's superior follow up report
  In order to follow the learners assigned to each student boss
  As a platform administrator
  I need one column per student boss with its learners, and to assign new learners from that column

  Scenario: Create the learner assigned in this feature
    Given I am a platform administrator
    And I am on "/admin/user-add"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | firstname | Bossreport             |
      | lastname  | Learner                |
      | email     | sbreport@example.com   |
      | username  | sbreportlearner        |
    And I check the "No" radio button
    And I press "Add"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: See one column per student boss with the add controls
    Given I am a platform administrator
    And I am on "/reporting/admin/student-bosses"
    And I wait up to 30 seconds for the element "article[data-boss-id]" to appear
    Then I should see "Angelica Baggins"
    And I should see "Add learner"
    And I should not see "Bossreport Learner"

  Scenario: Assign a learner to a student boss from its column
    Given I am a platform administrator
    And I am on "/reporting/admin/student-bosses"
    And I wait up to 30 seconds for the element "article[data-boss-id]:has-text('abaggins')" to appear
    When I type "sbreport" into the "article[data-boss-id]:has-text('abaggins') input" element
    And I wait up to 15 seconds for the element "li[role='option']:has-text('sbreportlearner')" to appear
    And I click the "li[role='option']:has-text('sbreportlearner')" element
    And I click the "article[data-boss-id]:has-text('abaggins') button:has-text('Add')" element
    Then I wait up to 15 seconds for the element "article[data-boss-id]:has-text('abaggins') a:has-text('Bossreport Learner')" to appear

  Scenario: The assigned learner stays in the boss column after a reload
    Given I am a platform administrator
    And I am on "/reporting/admin/student-bosses"
    Then I wait up to 30 seconds for the element "article[data-boss-id]:has-text('abaggins') a:has-text('Bossreport Learner')" to appear

  Scenario: HR managers do not get the add controls
    Given I am an HR manager
    And I am on "/reporting/admin/student-bosses"
    And I wait very long for the page to be loaded
    Then I should not see "Add learner"

  Scenario: Learners do not get the add controls
    Given I am a student
    And I am on "/reporting/admin/student-bosses"
    And I wait very long for the page to be loaded
    Then I should not see "Add learner"

  # Assigning a learner notifies the boss, as in the legacy page
  # (UserManager::subscribeUserToBossList). The inbox delete only marks the
  # message deleted for its receiver: the message row itself stays.
  Scenario: The boss was notified and removes the notification from its inbox
    Given I am a student boss
    And I am on "/resources/messages"
    And I wait up to 30 seconds for the element "tr:has-text('You have been assigned the learner Bossreport Learner')" to appear
    And I click the "[title='Delete']" icon in the row for "You have been assigned the learner Bossreport Learner"
    And I press "Yes"
    Then I wait until I no longer see "You have been assigned the learner Bossreport Learner"

  Scenario: Delete the learner assigned in this feature
    Given I am a platform administrator
    And I am on "/admin/user-list?keyword=sbreportlearner"
    And wait very long for the page to be loaded
    And I click the "[title='Delete']" icon in the row for "sbreportlearner"
    And I press "Yes"
    And wait very long for the page to be loaded
    And I am on "/admin/user-list?view=deleted&keyword=sbreportlearner"
    And wait very long for the page to be loaded
    And I click the "[title='Delete permanently']" icon in the row for "sbreportlearner"
    And I press "Yes"
    And wait very long for the page to be loaded
    Then I should not see an error
