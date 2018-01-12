Feature: Group tool
  In order to use the group tool
  The teachers should be able to create groups

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create a group directory
    Given I am on "/main/group/group_category.php?cidReq=TEMP&id_session=0&action=add_category"
    When I fill in the following:
      | title | Group category 1   |
    And I press "group_category_submit"
    Then I should see "Category created"

  Scenario: Create a group
    Given I am on "/main/group/group_creation.php?cidReq=TEMP&id_session=0"
    When I fill in the following:
      | number_of_groups | 1 |
    And I press "submit"
    Then I should see "New groups creation"
    When I fill in the following:
      | group_0_places | 1 |
    And I press "submit"
    Then I should see "group(s) has (have) been added"


  Scenario: Create document folder in group
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0001"
    Then I should see "Group 0001"
    And I follow "Documents"
    Then I should see "There are no documents to be displayed"
    Then I follow "Create folder"
    Then I should see "Create folder"
    Then I fill in the following:
      | dirname | My folder in group |
    And I press "create_dir_form_submit"
    Then I should see "Folder created"

  Scenario: Create document inside folder in group
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0001"
    Then I should see "Group 0001"
    And I follow "Documents"
    Then I should see "My folder in group"
    Then I follow "My folder in group"
    Then I follow "Create a rich media page / activity"
    Then I should see "Create a rich media page"
    Then I fill in the following:
      | title | html test |
    And I fill in ckeditor field "content" with "My first HTML!!"
    Then I press "create_document_submit"
    Then I should see "Item added"

  Scenario: Upload a document inside folder in group
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0001"
    Then I should see "Group 0001"
    And I follow "Documents"
    Then I should see "My folder in group"
    Then I follow "My folder in group"
    Then I follow "Upload documents"
    Then I follow "Upload (Simple)"
    # File path is located in behat.yml
    Then I attach the file "build/css/base.css" to "file"
    Then wait for the page to be loaded
    Then I press "upload_submitDocument"
    Then wait for the page to be loaded
    Then I should see "File upload succeeded"

  Scenario: Delete 2 uploaded files
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0001"
    Then I should see "Group 0001"
    And I follow "Documents"
    Then I should see "My folder in group"
    Then I follow "My folder in group"
    Then I follow "Delete"
    And I confirm the popup
    Then I should see "Document deleted"
    Then I follow "Delete"
    And I confirm the popup
    Then I should see "Document deleted"

  Scenario: Delete directory
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0001"
    Then I should see "Group 0001"
    And I follow "Documents"
    Then I should see "My folder in group"
    Then I follow "Delete"
    And I confirm the popup
    Then I should see "Document deleted"
