Feature: Link tool
  In order to use the link tool
  The teachers should be able to create link categories and links

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create a link category
    Given I am on "/main/link/link.php?action=addcategory&cid=1"
    And I wait for the page to be loaded
    When I fill in the following:
      | category_title | Category 1 |
    And I fill in editor field "description" with "Category description"
    And I press "submitCategory"
    And wait for the page to be loaded
    Then I should see "Category 1"
    Then I should not see an error

  Scenario: Create a link
    And I am on "/main/link/link.php?action=addlink&cid=1"
    And I wait for the page to be loaded
    When I fill in the following:
      | url   | http://www.chamilo.org |
      | title | Chamilo |
    And I press "submitLink"
    And wait for the page to be loaded
    Then I should see "Chamilo"
    And I should not see an error

  Scenario: Create a link with category
    Given I am on "/main/link/link.php?action=addlink&cid=1"
    And I wait for the page to be loaded
    When I fill in the following:
      | url   | http://www.chamilo.org |
      | title | Chamilo in category 1 |
    And I select "Category 1" from "category_id"
    And I press "submitLink"
    And wait for the page to be loaded
    Then I should see "Chamilo in category 1"

  Scenario: Delete link
    Given I am on "/main/link/link.php?cid=1"
    And I wait for the page to be loaded
    And I click the "i.mdi-delete" element
    And I confirm the popup
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Delete link category
    Given I am on "/main/link/link.php?cid=1"
    And I wait for the page to be loaded
    And I click the "i.mdi-delete" element
    And I confirm the popup
    And wait very long for the page to be loaded
    Then I should not see an error
