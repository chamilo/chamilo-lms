Feature: Link tool
  In order to use the link tool
  The teachers should be able to create link categories and links

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create a link category
    Given I am on "/main/link/link.php?action=addcategory&cid=1"
    And wait the page to be loaded when ready
    When I fill in the following:
      | category_title | Category 1 |
    And I fill in editor field "description" with "Category description"
    And I press "submitCategory"
    And wait for the page to be loaded
    Then I should see "Category added"

  Scenario: Create a link
    And I am on "/main/link/link.php?action=addlink&cid=1"
    And wait the page to be loaded when ready
    When I fill in the following:
      | url   | http://www.chamilo.org |
      | title | Chamilo |
    And I press "submitLink"
    And wait for the page to be loaded
    Then I should see "The link has been added"

#  Scenario: Create a link with category
#    Given I am on "/main/link/link.php?action=addlink&cid=1"
#    When I fill in the following:
#      | url   | http://www.chamilo.org |
#      | title | Chamilo in category 1 |
#    And I select "Category 1" from "category_id"
#    And I press "submitLink"
#    Then I should see "The link has been added"

  Scenario: Delete link
    Given I am on "/main/link/link.php?cid=1"
    And I follow "Delete"
    And I confirm the popup
    And wait very long for the page to be loaded
    Then I should see "The link has been deleted"

  Scenario: Delete link category
    Given I am on "/main/link/link.php?cid=1"
    And I follow "Delete"
    And wait very long for the page to be loaded
    Then I should see "The category has been deleted."



