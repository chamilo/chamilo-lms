Feature: Link tool
  In order to use the link tool
  The teachers should be able to create link categories and links

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create a link category
    Given I am on "/main/link/link.php?action=addcategory&cidReq=TEMP"
    When I fill in the following:
      | category_title | Category 1 |
      | description    | Category description |
    And I press "submitCategory"
    Then I should see "Category added"

  Scenario: Create a link
    Given I am on "/main/link/link.php?action=addlink&cidReq=TEMP"
    When I fill in the following:
      | url   | http://www.chamilo.org |
      | title | Chamilo |
    And I press "submitLink"
    Then I should see "The link has been added"

#  Scenario: Create a link with category
#    Given I am on "/main/link/link.php?action=addlink&cidReq=TEMP"
#    When I fill in the following:
#      | url   | http://www.chamilo.org |
#      | title | Chamilo in category 1 |
#    And I select "Category 1" from "category_id"
#    And I press "submitLink"
#    Then I should see "The link has been added"

  Scenario: Delete link
    Given I am on "/main/link/link.php?cidReq=TEMP"
    And I follow "Delete"
    And I confirm the popup
    Then I should see "The link has been deleted"

  Scenario: Delete link category
    Given I am on "/main/link/link.php?cidReq=TEMP"
    And I follow "Delete"
    Then I should see "The category has been deleted."



