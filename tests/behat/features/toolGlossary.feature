Feature: Glossary tool
  Ensure glossary integration and visibility in course

  Scenario: Create glossary term in course TEMP
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Glossary"
    And wait very long for the page to be loaded
    Then I click the "span.mdi-plus" element
    And I wait for the page to be loaded
    When I fill in the following:
      | term-name | Device |
      | term-description | a device is a thing |
    And I click the "button.p-button-success" element
    And I wait for the page to be loaded
    Then I should see "Device"

  Scenario: Add glossary link from Documents in course TEMP
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Documents"
    And wait very long for the page to be loaded
    Then I click the "span.mdi-file-plus" element
    And I wait for the page to be loaded
    When I fill in the following:
      | item_title | Glossary |
    And I fill in tinymce field "item_content" with "Several words, including device"
    And I press "Save"
    And wait very long for the page to be loaded
    And wait very long for the page to be loaded
    Then I should see "Glossary"

  Scenario: Enable glossary display in extra tools from admin settings
    Given I am a platform administrator
    And I am on "/admin/settings/glossary"
    And I wait for the page to be loaded
    When I select "Learning path" from "form_show_glossary_in_extra_tools"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Create Learning path named Glossary in course TEMP
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Learning paths"
    And wait very long for the page to be loaded
    Then I click the "i.mdi-dots-vertical" element
    And I wait for the page to be loaded
    And I follow "Create new learning path"
    And I wait for the page to be loaded
    When I fill in the following:
      | lp_name | Glossary |
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Glossary"


    #scenario Dragen and Drop have to be

  Scenario: Export glossary then check generated file in Documents
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Glossary"
    And wait very long for the page to be loaded
    Then I click the "span.mdi-export" element
    And I wait for the page to be loaded
    Then I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Documents"
    And wait very long for the page to be loaded
    Then I should see "glossary_"
