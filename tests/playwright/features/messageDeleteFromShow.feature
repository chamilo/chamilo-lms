@common
Feature: Delete a message from its own page
  The "Delete" button of an opened message must remove the message from the
  current user's folder, for the recipient (inbox) as for the sender (sent
  folder). It used to only navigate back to the list, leaving the message in
  place, because it looked for the user's copy without its receiver type.

  # A second message, "PW kept message", is sent alongside the one deleted:
  # waiting for it to show proves the folder finished loading, so the absence
  # of the deleted one is not read from a list that is still empty or still
  # showing the previous folder. It is removed at the end from the list, whose
  # row action is a separate code path.
  #
  # The confirm button is targeted by its PrimeVue section, not its label:
  # the student account's interface language is not English.

  Scenario: The sender sends two messages to a student
    Given I am a platform administrator
    And I resolve the user id for "acostea"
    And I am on "/resources/messages/new?send_to_user=USER_ID" with the resolved user id
    And I wait for the element ".tox-editor-header" to appear
    When I fill in "item_title" with "PW delete from message page"
    And I fill in the active tinymce editor with "Message body"
    And I press "Send"
    Then I should not see the ".tox-editor-header" element
    Given I am on "/resources/messages/new?send_to_user=USER_ID" with the resolved user id
    And I wait for the element ".tox-editor-header" to appear
    When I fill in "item_title" with "PW kept message"
    And I fill in the active tinymce editor with "Message body"
    And I press "Send"
    Then I should not see the ".tox-editor-header" element

  Scenario: The recipient deletes the message from its page
    Given I am a student
    And I am on "/resources/messages"
    When I click element "td" containing text "PW delete from message page"
    And I wait for the element "button:has(.mdi-delete)" to appear
    And I click the "button:has(.mdi-delete)" element
    And I click the "[role='alertdialog'] [data-pc-name='pcacceptbutton']" element
    Then I wait for the element "td:has-text('PW kept message')" to appear
    And I should not see "PW delete from message page"

  Scenario: The sender deletes the message from its page in the sent folder
    Given I am a platform administrator
    And I am on "/resources/messages"
    And I press "Sent"
    When I click element "td" containing text "PW delete from message page"
    And I wait for the element "button:has(.mdi-delete)" to appear
    And I click the "button:has(.mdi-delete)" element
    And I click the "[role='alertdialog'] [data-pc-name='pcacceptbutton']" element
    And I wait for the element ".p-datatable" to appear
    And I press "Sent"
    Then I wait for the element "td:has-text('PW kept message')" to appear
    And I should not see "PW delete from message page"

  Scenario: Clean up the kept message on both sides
    Given I am a student
    And I am on "/resources/messages"
    When I click the "button:has(.mdi-delete)" icon in the row for "PW kept message"
    And I click the "[role='alertdialog'] [data-pc-name='pcacceptbutton']" element
    Then I should not see "PW kept message"
    Given I am a platform administrator
    And I am on "/resources/messages"
    And I press "Sent"
    When I click the "button:has(.mdi-delete)" icon in the row for "PW kept message"
    And I click the "[role='alertdialog'] [data-pc-name='pcacceptbutton']" element
    Then I should not see "PW kept message"
