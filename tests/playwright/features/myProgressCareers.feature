Feature: Careers in my progress
  In order to follow the careers I am enrolled in
  As a learner
  I need to see my careers and a link to their diagram in my progress page

  # The careers block replaced the one of the removed main/auth/my_progress.php. The only way the
  # interface gives a career to a learner is the session import, through an "extra_careerid" column
  # that holds the external id of the career (extra field "external_career_id").
  #
  # The settings changed here (allow_career_users, use_career_external_id_as_identifier_in_diagrams)
  # only change the careers block and the career diagram, so they do not affect the files running in
  # parallel. The teardown restores them to No, their default.

  Scenario: Admin enables the careers of users
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=allow_career_users"
    And I wait for the page to be loaded
    And I select "Yes" from "form_allow_career_users"
    And I press "Save settings"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Admin creates the external career id field
    Given I am a platform administrator
    And I am on "/main/admin/extra_fields.php?type=career&action=add"
    And I wait for the page to be loaded
    When I fill in "career_field_display_text" with "PW external career id"
    And I fill in "career_field_variable" with "external_career_id"
    And I press "career_field_submit"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "external_career_id"

  Scenario: Admin creates the career with its external id
    Given I am a platform administrator
    And I am on "/main/admin/careers.php?action=add"
    And I wait for the page to be loaded
    When I fill in "career_title" with "PW my progress career"
    And I fill in editor field "description" with "Career of the my progress test"
    And I fill in "extra_external_career_id" with "PWCAREER EXT"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "PW my progress career"

  Scenario: Admin creates the learner
    Given I am a platform administrator
    When I am on "/admin/user-add"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | firstname | Career                          |
      | lastname  | Learner                         |
      | email     | pw.career.learner@example.test  |
      | username  | pw_career_learner               |
    And I check the "Set password manually" radio button
    And I fill in "password" with "CareerLearner01!"
    And I check the "No" radio button
    And I press "Add"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Admin gives the career to the learner with a session import
    Given I am a platform administrator
    And I am on "/main/session/session_import.php"
    And I wait for the page to be loaded
    When I attach the file "tests/playwright/fixtures/my-progress-careers-session.csv" to "import_file"
    And I press "Import session(s)"
    And I wait very long for the page to be loaded
    Then I should not see an error
    And I should see "PW my progress career"

  Scenario: The learner sees the career with a link to its diagram
    Given I am not logged
    And I am logged as "pw_career_learner" with password "CareerLearner01!"
    And I wait for the page to be loaded
    When I am on "/reporting/my-progress"
    And I wait up to 30 seconds for the element "#global-reporting-careers" to appear
    Then I should see "Careers"
    And I should see "PW my progress career"
    And I wait for the element "#global-reporting-careers a[href*='/main/user/career_diagram.php?career_id=']" to appear

  Scenario: Admin identifies the diagrams by the external career id
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=use_career_external_id_as_identifier_in_diagrams"
    And I wait for the page to be loaded
    And I select "Yes" from "form_use_career_external_id_as_identifier_in_diagrams"
    And I press "Save settings"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: The diagram link of the learner uses the external career id
    Given I am not logged
    And I am logged as "pw_career_learner" with password "CareerLearner01!"
    And I wait for the page to be loaded
    When I am on "/reporting/my-progress"
    And I wait up to 30 seconds for the element "#global-reporting-careers" to appear
    Then I wait for the element "#global-reporting-careers a[href='/main/user/career_diagram.php?career_id=PWCAREER+EXT']" to appear

  @long-scenario
  Scenario: Teardown — delete the session and the learner
    Given I am a platform administrator
    When I am on "/admin/session-list?keyword=PW my progress careers"
    And I wait for the page to be loaded
    And I click the "[title='Delete']" icon in the row for "PW my progress careers"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "PW my progress careers"

    When I am on "/admin/user-list?keyword=pw_career_learner"
    And I wait for the page to be loaded
    And I click the "[title='Delete']" icon in the row for "pw.career.learner@example.test"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "pw.career.learner@example.test"

    When I am on "/admin/user-list?view=deleted&keyword=pw_career_learner"
    And I wait for the page to be loaded
    And I click the "[title='Delete permanently']" icon in the row for "pw.career.learner@example.test"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "pw.career.learner@example.test"

  Scenario: Teardown — delete the career and the external career id field
    Given I am a platform administrator
    And I am on "/main/admin/careers.php"
    And I wait for the page to be loaded
    And I click the "i.mdi-delete" icon in the row for "PW my progress career"
    And I confirm the popup
    And I wait for the page to be loaded
    Then I should not see "PW my progress career"

    When I am on "/main/admin/extra_fields.php?type=career"
    And I wait for the page to be loaded
    And I click the "a[href*='action=delete']" icon in the row for "external_career_id"
    And I wait for the page to be loaded
    Then I should not see "external_career_id"

  Scenario: Teardown — restore the career settings
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=use_career_external_id_as_identifier_in_diagrams"
    And I wait for the page to be loaded
    And I select "No" from "form_use_career_external_id_as_identifier_in_diagrams"
    And I press "Save settings"
    And I wait for the page to be loaded
    Then I should not see an error
    When I am on "/admin/settings/search_settings?keyword=allow_career_users"
    And I wait for the page to be loaded
    And I select "No" from "form_allow_career_users"
    And I press "Save settings"
    And I wait for the page to be loaded
    Then I should not see an error
