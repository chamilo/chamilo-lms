@common
Feature: GH2220 - Insert and read function graphs in messages
  Graph expressions are inert message data. Sending, reading and replying
  must not execute stored SVG or script; the graph is reconstructed by the
  trusted client renderer when the message opens.

  Scenario: GH2220 - Send and read a graph without opening Reply
    Given I am a platform administrator
    And I resolve the user id for "acostea"
    And I am on "/resources/messages/new?send_to_user=USER_ID" with the resolved user id
    And I wait for the element ".tox-editor-header" to appear
    When I fill in "item_title" with "PW GH2220 function graph"
    And I click the ".tox-toolbar button[title='Insert graph']" element
    And I wait for the element "#message-graph-expression" to appear
    And I fill in "message-graph-expression" with "x^3"
    And I click the ".p-dialog-footer button:has-text('Insert graph')" element
    And I wait for the element ".tox-edit-area iframe" to appear
    And I wait for the element ".flex.justify-end.mt-2 button:not([disabled])" to appear
    And I click the ".flex.justify-end.mt-2 button:not([disabled])" element
    And I am on "/resources/messages"
    And I click the ".flex.items-stretch.self-stretch > button:nth-child(3)" element
    When I click element "td" containing text "PW GH2220 function graph"
    Then I wait for the element ".tiny-content .ch-message-function-graph-svg" to appear
    And I should see "Graph: y = x^3"
    And I should not see an error

  Scenario: GH2220 - Unsafe graph expressions are rejected before sending
    Given I am a platform administrator
    And I am on "/resources/messages/new"
    And I wait for the element ".tox-editor-header" to appear
    When I click the ".tox-toolbar button[title='Insert graph']" element
    And I fill in "message-graph-expression" with "x;alert(1)"
    And I click the ".p-dialog-footer button:has-text('Insert graph')" element
    Then I should see "Unsupported mathematical expression."
    And I should see the "[role='dialog']" element

  Scenario: GH2220 - The recipient sees the same graph without replying
    Given I am a student
    And I am on "/resources/messages"
    When I click element "td" containing text "PW GH2220 function graph"
    Then I wait for the element ".tiny-content .ch-message-function-graph-svg" to appear
    And I should see "Graph: y = x^3"
    And I should not see an error

  Scenario: GH2220 - Remove the test message from both mailboxes
    Given I am a student
    And I am on "/resources/messages"
    When I click the "button:has(.mdi-delete)" icon in the row for "PW GH2220 function graph"
    And I click the "[role='alertdialog'] [data-pc-name='pcacceptbutton']" element
    Then I should not see "PW GH2220 function graph"
    Given I am a platform administrator
    And I am on "/resources/messages"
    And I click the ".flex.items-stretch.self-stretch > button:nth-child(3)" element
    When I click the "button:has(.mdi-delete)" icon in the row for "PW GH2220 function graph"
    And I click the "[role='alertdialog'] [data-pc-name='pcacceptbutton']" element
    Then I should not see "PW GH2220 function graph"
