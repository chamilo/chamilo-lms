@common
Feature: The message editor does not offer translate_html blocks
  The "Lang ISO" toolbar button of the translate_html plugin lets authors mark
  multilingual blocks in platform content (pages, documents, course tools). A
  private message is written for its recipients only, so its editor must not
  offer that button, neither when writing a new message nor when replying,
  while every other editor keeps it.

  translate_html is enabled by default (EditorSettingsSchema), so the
  Background only makes sure it is on and the clean-up leaves it that way.

  Background:
    Given I am a platform administrator
    And I am on "/admin/settings/editor"
    And I wait for the page to be loaded
    And I select the value "true" from "form_translate_html"
    And I press "Save settings"
    And I wait for the page to be loaded

  Scenario: Other editors keep the Lang ISO button
    Given I am on "/resources/pages/new"
    Then I wait for the element ".tox-tbtn__select-label:text-is('Lang ISO')" to appear

  Scenario: The new message editor has no Lang ISO button
    Given I resolve the user id for "acostea"
    And I am on "/resources/messages/new?send_to_user=USER_ID" with the resolved user id
    And I wait for the element ".tox-editor-header" to appear
    Then I should not see the ".tox-tbtn__select-label:text-is('Lang ISO')" element
    When I fill in "item_title" with "PW message without Lang ISO"
    And I fill in the active tinymce editor with "Message body"
    And I press "Send"
    Then I should not see the ".tox-editor-header" element

  Scenario: The reply editor has no Lang ISO button
    Given I am a student
    And I am on "/resources/messages"
    When I click element "td" containing text "PW message without Lang ISO"
    And I wait for the element "button:has(.mdi-reply)" to appear
    And I click the "button:has(.mdi-reply)" element
    And I wait for the element ".tox-editor-header" to appear
    Then I should not see the ".tox-tbtn__select-label:text-is('Lang ISO')" element

  # The confirm button is targeted by its PrimeVue section, not its label:
  # the student account's interface language is not English. Messages are
  # deleted from the list, whose row action removes the user's own copy.
  Scenario: Clean up the message on both sides
    Given I am a student
    And I am on "/resources/messages"
    When I click the "button:has(.mdi-delete)" icon in the row for "PW message without Lang ISO"
    And I click the "[role='alertdialog'] [data-pc-name='pcacceptbutton']" element
    Then I should not see "PW message without Lang ISO"
    Given I am a platform administrator
    And I am on "/resources/messages"
    And I press "Sent"
    When I click the "button:has(.mdi-delete)" icon in the row for "PW message without Lang ISO"
    And I click the "[role='alertdialog'] [data-pc-name='pcacceptbutton']" element
    Then I should not see "PW message without Lang ISO"
