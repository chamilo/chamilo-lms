@common
Feature: GH2220 - Render stored mathematical formulas directly in messages
  A recipient must see a stored LaTeX formula while reading a message,
  without opening the reply editor. This covers the supported modern
  MathJax format, not unsupported legacy AsciiSVG <embed> content.

  # editor.enabled_mathjax is switched on here and switched off again by the last scenario, so the feature does not
  # depend on what an earlier file left in the platform settings. (A settings guard cannot restore it: an empty
  # value, the installer default, is shown by the form as "Yes", the first option, so a guard would store "true".)
  Scenario: GH2220 - The administrator enables the MathJax editor plugin
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=enabled_mathjax"
    And I wait for the page to be loaded
    And I select "Yes" from "form_enabled_mathjax"
    And I press "Save settings"
    And I wait for the page to be loaded
    Then I should not see an error

  # The test uses the seeded acostea student.
  Scenario: GH2220 - The sender can read a rendered formula without replying
    Given I am a platform administrator
    And I resolve the user id for "acostea"
    And I am on "/resources/messages/new?send_to_user=USER_ID" with the resolved user id
    And I wait for the element ".tox-editor-header" to appear
    When I fill in "item_title" with "PW GH2220 rendered formula"
    And I fill in the active tinymce editor with "<p>Equation in a message: <span class='math-latex' contenteditable='false' data-latex='y=x^3'>&nbsp;</span></p>"
    And I wait for the element ".flex.justify-end.mt-2 button:not([disabled])" to appear
    And I click the ".flex.justify-end.mt-2 button:not([disabled])" element
    And I am on "/resources/messages"
    And I click the ".flex.items-stretch.self-stretch > button:nth-child(3)" element
    When I click element "td" containing text "PW GH2220 rendered formula"
    Then I wait for the element ".tiny-content .math-latex.mathjax-processed mjx-container svg" to appear
    And I should not see an error

  Scenario: GH2220 - Clean up the test message in both mailboxes
    Given I am a student
    And I am on "/resources/messages"
    When I click the "button:has(.mdi-delete)" icon in the row for "PW GH2220 rendered formula"
    And I click the "[role='alertdialog'] [data-pc-name='pcacceptbutton']" element
    Then I should not see "PW GH2220 rendered formula"
    Given I am a platform administrator
    And I am on "/resources/messages"
    And I click the ".flex.items-stretch.self-stretch > button:nth-child(3)" element
    When I click the "button:has(.mdi-delete)" icon in the row for "PW GH2220 rendered formula"
    And I click the "[role='alertdialog'] [data-pc-name='pcacceptbutton']" element
    Then I should not see "PW GH2220 rendered formula"

  Scenario: GH2220 - The administrator switches the MathJax editor plugin off again
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=enabled_mathjax"
    And I wait for the page to be loaded
    And I select "No" from "form_enabled_mathjax"
    And I press "Save settings"
    And I wait for the page to be loaded
    Then I should not see an error
