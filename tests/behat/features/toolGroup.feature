Feature: Group tool
  In order to use the group tool
  The teachers should be able to create groups

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Delete default category
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    Then I should see "Default groups"
    Then I click the "i.mdi-delete" element
    Then I confirm the popup
    And I wait for the page to be loaded
    Then I should see "The category has been deleted"

  Scenario: Create a group directory
    Given I am on "/main/group/group_category.php?cid=1&sid=0&action=add_category"
    And I wait for the page to be loaded
    When I fill in the following:
      | title | Group category 1 |
    And I press "Add"
    And I wait for the page to be loaded
    Then I should see "Group category 1"
    Then I should not see an error

  Scenario: Create 4 groups
    Given I am on "/main/group/group_creation.php?cid=1&sid=0"
    And I wait for the page to be loaded
    Then I fill in the following:
      | number_of_groups | 5 |
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "New groups creation"
    Then I fill in the following:
      | group_0_places | 1 |
      | group_1_places | 1 |
      | group_2_places | 1 |
      | group_3_places | 1 |
      | group_4_places | 2 |
    And I select "Group category 1" from "category_0"
    And I wait for the page to be loaded
    And I select "Group category 1" from "category_1"
    And I wait for the page to be loaded
    And I select "Group category 1" from "category_2"
    And I wait for the page to be loaded
    And I select "Group category 1" from "category_3"
    And I wait for the page to be loaded
    And I select "Group category 1" from "category_4"
    And I wait for the page to be loaded
    And I press "submit"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Create document folder in group
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I follow "Group 0001"
    And I wait for the page to be loaded
    Then I should see "Group 0001"
    And I click the "i.mdi-bookshelf" element
    And I wait for the page to be loaded
    Then I should see "There are no documents to be displayed"
    Then I follow "Create folder"
    And I wait for the page to be loaded
    Then I should see "Create folder"
    Then I fill in the following:
      | dirname | My folder in group |
    And I press "create_dir_form_submit"
    And I wait for the page to be loaded
    Then I should see "Folder created"

  Scenario: Create document inside folder in group
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I follow "Group 0001"
    And I wait for the page to be loaded
    Then I should see "Group 0001"
    And I follow "Documents"
    And I wait for the page to be loaded
    Then I should see "My folder in group"
    Then I follow "My folder in group"
    And I wait for the page to be loaded
    Then I follow "Create a rich media page / activity"
    And I wait for the page to be loaded
    Then I should see "Create a rich media page"
    Then I fill in the following:
      | title | html test |
    And I fill in editor field "content" with "My first HTML!!"
    Then I press "create_document_submit"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Upload a document inside folder in group
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I follow "Group 0001"
    And I wait for the page to be loaded
    Then I should see "Group 0001"
    And I follow "Documents"
    And I wait for the page to be loaded
    Then I should see "My folder in group"
    Then I follow "My folder in group"
    And I wait for the page to be loaded
    Then I follow "Upload documents"
    And I wait for the page to be loaded
    Then I follow "Upload (Simple)"
    And I wait for the page to be loaded
   # File path is located in behat.yml
    Then I attach the file "/public/favicon.ico" to "file"
    And I wait for the page to be loaded
    Then I press "upload_submitDocument"
    And I wait for the page to be loaded
    Then I should see "File upload succeeded"

  Scenario: Delete 2 uploaded files
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I follow "Group 0001"
    And I wait for the page to be loaded
    Then I should see "Group 0001"
    And I follow "Documents"
    And I wait for the page to be loaded
    Then I should see "My folder in group"
    Then I follow "My folder in group"
    And I wait for the page to be loaded
    Then I follow "Delete"
    And I wait for the page to be loaded
    Then I should see "Are you sure to delete"
    Then I follow "delete_item"
    And I wait for the page to be loaded

  Scenario: Delete directory
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I follow "Group 0001"
    And I wait for the page to be loaded
    Then I should see "Group 0001"
    And I follow "Documents"
    And I wait for the page to be loaded
    Then I should see "My folder in group"
    Then I follow "Delete"
    And I wait for the page to be loaded
    Then I should see "Are you sure to delete"
    Then I follow "delete_item"
    And I wait for the page to be loaded

  Scenario: Add fapple to the Group 0001
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    And I follow "Group 0001"
    And I wait for the page to be loaded
    Then I should see "Group 0001"
    Then I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    Then I should see "Group members"
    Then I click the "i.mdi-account" element
    And I wait for the page to be loaded
    Then I select "Fiona Apple Maggart (fapple)" from "group_members"
    And I wait for the page to be loaded
    Then I press "group_members_rightSelected"
    And I wait for the page to be loaded
    Then I press "Save settings"
    And I wait for the page to be loaded
    Then I should see "Group settings modified"
    Then I follow "Group 0001"
    And I wait for the page to be loaded
    Then I should see "Fiona"

  Scenario: Add fapple to the Group 0003 not allowed because group category allows 1 user per group
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    And I follow "Group 0003"
    And I wait for the page to be loaded
    Then I should see "Group 0003"
    Then I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    Then I should see "Group members"
    Then I click the "i.mdi-account" element
    And I wait for the page to be loaded
    Then I select "Fiona Apple Maggart (fapple)" from "group_members"
    And I wait for the page to be loaded
    Then I press "group_members_rightSelected"
    And I wait for the page to be loaded
    Then I press "Save settings"
    And I wait for the page to be loaded
    Then I should see "Group settings modified"
    Then I follow "Group 0003"
    And I wait for the page to be loaded
    Then I should not see "Fiona"

 # Group category overwrites all other groups settings.
  Scenario: Change Group category to allow multiple inscription of the user
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    And I follow "Edit this category"
    And I wait for the page to be loaded
    Then I should see "Edit group category: Group category 1"
    And I select "10" from "groups_per_user"
    And I wait for the page to be loaded
    Then I press "Edit"
    And I wait for the page to be loaded
    Then I should see "Group settings have been modified"

  Scenario: Change Group 0003 settings to make announcements private
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    And I follow "Group 0003"
    And I wait for the page to be loaded
    Then I should see "Group 0003"
    Then I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    Then I check the "announcements_state" radio button with "2" value
    And I wait for the page to be loaded
    Then I press "Save settings"
    And I wait for the page to be loaded
    Then I should see "Group settings modified"

  Scenario: Change Group 0004 settings to make it private
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    And I follow "Group 0004"
    And I wait for the page to be loaded
    Then I should see "Group 0004"
    Then I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    Then I check the "announcements_state" radio button with "2" value
    And I wait for the page to be loaded
    Then I press "Save settings"
    And I wait for the page to be loaded
    Then I should see "Group settings modified"

  Scenario: Change Group 0005 settings to make announcements private between users
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    And I follow "Group 0005"
    And I wait for the page to be loaded
    Then I should see "Group 0005"
    Then I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    Then I check the "announcements_state" radio button with "3" value
    And I wait for the page to be loaded
    Then I press "Save settings"
    And I wait for the page to be loaded
    Then I should see "Group settings modified"

  Scenario: Add fapple and acostea to Group 0005
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    And I follow "Group 0005"
    And I wait for the page to be loaded
    Then I should see "Group 0005"
    Then I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    Then I should see "Group members"
    Then I click the "i.mdi-account" element
    And I wait for the page to be loaded
    Then I additionally select "Fiona Apple Maggart (fapple)" from "group_members"
    And I wait for the page to be loaded
    Then I additionally select "Andrea Costea (acostea)" from "group_members"
    And I wait for the page to be loaded
    Then I press "group_members_rightSelected"
    And I wait for the page to be loaded
    Then I press "Save settings"
    And I wait for the page to be loaded
    Then I should see "Group settings modified"
    Then I follow "Group 0005"
    And I wait for the page to be loaded
    Then I should see "Fiona"
    Then I should see "Andrea"

  Scenario: Add fapple to the Group 0003
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    And I follow "Group 0003"
    And I wait for the page to be loaded
    Then I should see "Group 0003"
    Then I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    Then I should see "Group members"
    Then I click the "i.mdi-account" element
    And I wait for the page to be loaded
    Then I select "Fiona Apple Maggart (fapple)" from "group_members"
    And I wait for the page to be loaded
    Then I press "group_members_rightSelected"
    And I wait for the page to be loaded
    Then I press "Save settings"
    And I wait for the page to be loaded
    Then I should see "Group settings modified"
    Then I follow "Group 0003"
    And I wait for the page to be loaded
    Then I should see "Fiona"

  Scenario: Add acostea to the Group 0002
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    And I follow "Group 0002"
    And I wait for the page to be loaded
    Then I should see "Group 0002"
    Then I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    Then I should see "Group members"
    Then I click the "i.mdi-account" element
    And I wait for the page to be loaded
    Then I select "Andrea Costea (acostea)" from "group_members"
    And I wait for the page to be loaded
    Then I press "group_members_rightSelected"
    And I wait for the page to be loaded
    Then I press "Save settings"
    And I wait for the page to be loaded
    Then I should see "Group settings modified"
    Then I follow "Group 0002"
    And I wait for the page to be loaded
    Then I should see "Andrea"

  Scenario: Create an announcement for everybody inside Group 0001
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    And I follow "Group 0001"
    And I wait for the page to be loaded
    Then I should see "Group 0001"
    Then I click the "i.mdi-bullhorn" element
    And I wait for the page to be loaded
    Then I follow "Add an announcement"
    And I wait for the page to be loaded
    Then I should see "Add an announcement"
    Then I fill in the following:
      | title | Announcement for all users inside Group 0001 |
      And I fill in editor field "content" with "Announcement description in Group 0001"
    Then I follow "announcement_preview"
    And I wait for the page to be loaded
    Then I should see "Announcement will be sent to"
    Then I press "submit"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Create an announcement for fapple inside Group 0001
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    And I follow "Group 0001"
    And I wait for the page to be loaded
    Then I should see "Group 0001"
    Then I click the "i.mdi-bullhorn" element
    And I wait for the page to be loaded
    Then I follow "Add an announcement"
    And I wait for the page to be loaded
    Then I should see "Add an announcement"
    Then I press "choose_recipients"
    And I wait for the page to be loaded
    Then I select "Fiona Apple Maggart" from "users"
    And I wait for the page to be loaded
    Then I press "users_rightSelected"
    And I wait for the page to be loaded
    Then I fill in the following:
      | title |Announcement for user fapple inside Group 0001|
    And I fill in editor field "content" with "Announcement description for user fapple inside Group 0001"
    Then I follow "announcement_preview"
    And I wait for the page to be loaded
    Then I should see "Announcement will be sent to"
    Then I press "submit"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Create an announcement for everybody inside Group 0003 (private)
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    And I follow "Group 0003"
    And I wait for the page to be loaded
    Then I should see "Group 0003"
    Then I click the "i.mdi-bullhorn" element
    And I wait for the page to be loaded
    Then I follow "Add an announcement"
    And I wait for the page to be loaded
    Then I should see "Add an announcement"
    Then I fill in the following:
      | title | Announcement for all users inside Group 0003 |
    And I fill in editor field "content" with "Announcement description in Group 0003"
    Then I follow "announcement_preview"
    And I wait for the page to be loaded
    Then I should see "Announcement will be sent to"
    Then I press "submit"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Create an announcement for fapple inside Group 0003
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    And I follow "Group 0003"
    And I wait for the page to be loaded
    Then I should see "Group 0003"
    Then I click the "i.mdi-bullhorn" element
    And I wait for the page to be loaded
    Then I follow "Add an announcement"
    And I wait for the page to be loaded
    Then I should see "Add an announcement"
    Then I press "choose_recipients"
    And I wait for the page to be loaded
    Then I select "Fiona Apple" from "users"
    And I wait for the page to be loaded
    Then I press "users_rightSelected"
    And I wait for the page to be loaded
    Then I fill in the following:
      | title | Announcement for user fapple inside Group 0003 |
    And I fill in editor field "content" with "Announcement description for user fapple inside Group 0003"
    Then I follow "announcement_preview"
    And I wait for the page to be loaded
    Then I should see "Announcement will be sent to"
    Then I press "submit"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Create an announcement as acostea and send only to fapple
    Given I am not logged
    And I wait for the page to be loaded
    Then I am logged as "acostea"
    And I wait for the page to be loaded
    Then I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    And I follow "Group 0005"
    And I wait for the page to be loaded
    Then I should see "Group 0005"
    Then I click the "i.mdi-bullhorn" element
    And I wait for the page to be loaded
    Then I follow "Add an announcement"
    And I wait for the page to be loaded
    Then I should see "Add an announcement"
    Then I press "choose_recipients"
    And I wait for the page to be loaded
    Then I select "Fiona Apple Maggart" from "users"
    And I wait for the page to be loaded
    Then I press "users_rightSelected"
    And I wait for the page to be loaded
    Then I fill in the following:
      | title | Announcement only for fapple Group 0005 |
    And I fill in editor field "content" with "Announcement description only for fapple Group 0005"
    Then I follow "announcement_preview"
    And I wait for the page to be loaded
    Then I should see "Announcement will be sent to"
    Then I press "submit"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Check fapple/acostea access of announcements
    Given I am not logged
    And I wait for the page to be loaded
    Given I am logged as "fapple"
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    And I follow "Group 0001"
    And I wait for the page to be loaded
    Then I should see "Group 0001"
    Then I click the "i.mdi-bullhorn" element
    And I wait for the page to be loaded
    Then I should see "Announcement for all users inside Group 0001"
    Then I should see "Announcement for user fapple inside Group 0001"
    Then I follow "Announcement for user fapple inside Group 0001"
    And I wait for the page to be loaded
    Then I should see "Announcement description for user fapple inside Group 0001"
    Then I save current URL with name "announcement_for_user_fapple_group_0001_public"
    Then I move backward one page
    And I wait for the page to be loaded
    Then I should see "Announcement for all users inside Group 0001"
    Then I follow "Announcement for all users inside Group 0001"
    And I wait for the page to be loaded
    Then I save current URL with name "announcement_for_all_users_group_0001_public"
    Then I should see "Announcement description in Group 0001"
    And I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    And I follow "Group 0003"
    And I wait for the page to be loaded
    Then I should see "Group 0003"
    Then I click the "i.mdi-bullhorn" element
    And I wait for the page to be loaded
    Then I should see "Announcement for all users inside Group 0003"
    Then I should see "Announcement for user fapple inside Group 0003"
    Then I follow "Announcement for user fapple inside Group 0003"
    And I wait for the page to be loaded
    Then I should see "Announcement description for user fapple inside Group 0003"
    Then I save current URL with name "announcement_for_user_fapple_group_0003_private"
    Then I move backward one page
    And I wait for the page to be loaded
    Then I should see "Announcement for all users inside Group 0003"
    Then I follow "Announcement for all users inside Group 0003"
    And I wait for the page to be loaded
    Then I should see "Announcement description in Group 0003"
    Then I save current URL with name "announcement_for_all_users_group_0003_private"
    And I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    And I follow "Group 0005"
    And I wait for the page to be loaded
    Then I should see "Group 0005"
    Then I click the "i.mdi-bullhorn" element
    And I wait for the page to be loaded
    Then I follow "Announcement only for fapple Group 0005"
    And I wait for the page to be loaded
    Then I save current URL with name "announcement_only_for_fapple_private"


   ## Finish tests with fapple now check access with acostea ##
    Given I am not logged
    And I wait for the page to be loaded
    Given I am logged as "acostea"
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    Then I should see "Group 0001"
    And I should see "Group 0002"
    And I should see "Group 0003"
    And I should see "Group 0004"
    Then I visit URL saved with name "announcement_for_user_fapple_group_0001_public"
    And I wait for the page to be loaded
    Then I should see "not allowed"
    Then I visit URL saved with name "announcement_for_all_users_group_0001_public"
    And I wait for the page to be loaded
    Then I should not see "not allowed"
    Then I visit URL saved with name "announcement_only_for_fapple_private"
    And I wait for the page to be loaded
    Then I should not see "not allowed"


    Given I am not logged
    And I wait for the page to be loaded
    Given I am logged as "acostea"
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    Given I am on "/main/group/group.php?cid=1&sid=0"
    And I wait for the page to be loaded
    Then I should see "Group 0001"
    And I should see "Group 0002"
    And I should see "Group 0003"
    And I should see "Group 0004"
    And I should see "Group 0005"


    Then I visit URL saved with name "announcement_for_user_fapple_group_0001_public"
    And I wait for the page to be loaded
    Then I should see "not allowed"
    Then I visit URL saved with name "announcement_for_all_users_group_0001_public"
    And I wait for the page to be loaded
    Then I should not see "not allowed"
    Then I visit URL saved with name "announcement_for_user_fapple_group_0003_private"
    And I wait for the page to be loaded
    Then I should see "not allowed"
    Then I visit URL saved with name "announcement_for_all_users_group_0003_private"
    And I wait for the page to be loaded
    Then I should see "not allowed"
    Then I visit URL saved with name "announcement_only_for_fapple_private"
    And I wait for the page to be loaded
    Then I should not see "not allowed"
