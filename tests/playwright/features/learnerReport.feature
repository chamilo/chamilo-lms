Feature: Learner report
  In order to follow up a learner
  As a platform administrator
  I need the learner report to list the learner's courses per subscription and to let me write,
  read and delete follow-up messages about the learner

  # acostea is subscribed to TEMP by course_user_registration.feature.
  # The Messages block only exists when private_messages_about_user is Yes, and its default is No.
  # Turning it on does not affect the other files running in parallel: only this report reads it.

  Scenario: The direct courses are listed with their total
    Given I am a platform administrator
    And I resolve the user id for "acostea"
    When I am on "/reporting/learners/USER_ID" with the resolved user id
    And I wait up to 30 seconds for the element "[data-testid='learner-subscription-0']" to appear
    Then I should see "Courses"
    And I should see "TEMP"
    And I should see "Total"

  Scenario: Admin writes, reads and deletes a message about the learner
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=private_messages_about_user"
    And I wait for the page to be loaded
    And I select "Yes" from "form_private_messages_about_user"
    And I press "Save settings"
    And I wait for the page to be loaded
    And I resolve the user id for "acostea"
    When I am on "/reporting/learners/USER_ID" with the resolved user id
    And I wait up to 30 seconds for the element "[data-testid='learner-messages']" to appear
    And I press "New message"
    And I fill in "subject" with "Follow-up about the exercises"
    And I fill in tinymce field "learner_message_content" with "Needs help with the exercises"
    And I press "Send message"
    Then I wait up to 30 seconds for the element "text=Follow-up about the exercises" to appear
    When I click the "[data-testid='learner-messages'] button[aria-label='Delete message']" element
    And I press "Yes"
    Then I wait until I no longer see "Follow-up about the exercises"

  Scenario: Admin disables the messages about users again
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=private_messages_about_user"
    And I wait for the page to be loaded
    And I select "No" from "form_private_messages_about_user"
    And I press "Save settings"
    And I wait for the page to be loaded
