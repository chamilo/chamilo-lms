Feature: Group tool
  In order to use the group tool
  The teachers should be able to create groups

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Delete default category
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    Then I should see "Default groups"
    Then I follow "Delete"
    Then I confirm the popup
    Then I should see "The category has been deleted"

  Scenario: Create a group directory
    Given I am on "/main/group/group_category.php?cidReq=TEMP&id_session=0&action=add_category"
    When I fill in the following:
      | title | Group category 1 |
    And I press "group_category_submit"
    Then I should see "Category created"

  Scenario: Create 4 groups
    Given I am on "/main/group/group_creation.php?cidReq=TEMP&id_session=0"
    When I fill in the following:
      | number_of_groups | 5 |
    And I press "submit"
    Then I should see "New groups creation"
    When I fill in the following:
      | group_0_places | 1 |
      | group_1_places | 1 |
      | group_2_places | 1 |
      | group_3_places | 1 |
      | group_4_places | 2 |
    And I fill in select bootstrap static by text "#category_0" select "Group category 1"
    And I fill in select bootstrap static by text "#category_1" select "Group category 1"
    And I fill in select bootstrap static by text "#category_2" select "Group category 1"
    And I fill in select bootstrap static by text "#category_3" select "Group category 1"
    And I fill in select bootstrap static by text "#category_4" select "Group category 1"
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
    Then I attach the file "web/css/base.css" to "file"
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
    Then wait for the page to be loaded
    Then I should see "Are you sure to delete"
    Then I follow "delete_item"

  Scenario: Delete directory
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0001"
    Then I should see "Group 0001"
    And I follow "Documents"
    Then I should see "My folder in group"
    Then I follow "Delete"
    Then wait for the page to be loaded
    Then I should see "Are you sure to delete"
    Then I follow "delete_item"

  Scenario: Add fapple to the Group 0001
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0001"
    Then I should see "Group 0001"
    Then I follow "Edit this group"
    Then I should see "Group members"
    Then wait for the page to be loaded
    Then I follow "group_members_tab"
    Then I select "Fiona Apple Maggart (fapple)" from "group_members"
    Then I press "group_members_rightSelected"
    Then I press "Save settings"
    And wait for the page to be loaded
    Then I should see "Group settings modified"
    Then I follow "Group 0001"
    Then I should see "Fiona"

  Scenario: Add fapple to the Group 0003 not allowed because group category allows 1 user per group
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0003"
    Then I should see "Group 0003"
    Then I follow "Edit this group"
    Then I should see "Group members"
    Then wait for the page to be loaded
    Then I follow "group_members_tab"
    Then I select "Fiona Apple Maggart (fapple)" from "group_members"
    Then I press "group_members_rightSelected"
    Then I press "Save settings"
    And wait for the page to be loaded
    Then I should see "Group settings modified"
    Then I follow "Group 0003"
    Then I should not see "Fiona"

 # Group category overwrites all other groups settings.
  Scenario: Change Group category to allow multiple inscription of the user
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Edit this category"
    Then I should see "Edit group category: Group category 1"
    Then I fill in select bootstrap static by text "#groups_per_user" select "10"
    Then I press "Edit"
    Then I should see "Group settings have been modified"

  Scenario: Change Group 0003 settings to make announcements private
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0003"
    Then I should see "Group 0003"
    Then I follow "Edit this group"
    Then I check the "announcements_state" radio button with "2" value
    Then I press "Save settings"
    Then I should see "Group settings modified"

  Scenario: Change Group 0004 settings to make it private
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0004"
    Then I should see "Group 0004"
    Then I follow "Edit this group"
    Then I check the "announcements_state" radio button with "2" value
    Then I press "Save settings"
    Then I should see "Group settings modified"

  Scenario: Change Group 0005 settings to make announcements private between users
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0005"
    Then I should see "Group 0005"
    Then I follow "Edit this group"
    Then I check the "announcements_state" radio button with "3" value
    Then I press "Save settings"
    Then I should see "Group settings modified"

  Scenario: Add fapple and acostea to Group 0005
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0005"
    Then I should see "Group 0005"
    Then I follow "Edit this group"
    Then I should see "Group members"
    Then wait for the page to be loaded
    Then I follow "group_members_tab"
    Then I additionally select "Fiona Apple Maggart (fapple)" from "group_members"
    Then I additionally select "Andrea Costea (acostea)" from "group_members"
    Then I press "group_members_rightSelected"
    Then I press "Save settings"
    And wait for the page to be loaded
    Then I should see "Group settings modified"
    Then I follow "Group 0005"
    Then I should see "Fiona"
    Then I should see "Andrea"

  Scenario: Add fapple to the Group 0003
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0003"
    Then I should see "Group 0003"
    Then I follow "Edit this group"
    Then I should see "Group members"
    Then wait for the page to be loaded
    Then I follow "group_members_tab"
    Then I select "Fiona Apple Maggart (fapple)" from "group_members"
    Then I press "group_members_rightSelected"
    Then I press "Save settings"
    And wait for the page to be loaded
    Then I should see "Group settings modified"
    Then I follow "Group 0003"
    Then I should see "Fiona"

  Scenario: Add acostea to the Group 0002
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0002"
    Then I should see "Group 0002"
    Then I follow "Edit this group"
    Then I should see "Group members"
    Then wait for the page to be loaded
    Then I follow "group_members_tab"
    Then I select "Andrea Costea (acostea)" from "group_members"
    Then I press "group_members_rightSelected"
    Then I press "Save settings"
    And wait for the page to be loaded
    Then I should see "Group settings modified"
    Then I follow "Group 0002"
    Then I should see "Andrea"

  Scenario: Create an announcement for everybody inside Group 0001
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0001"
    Then I should see "Group 0001"
    And I follow "Announcements"
    Then I should see "Announcements"
    Then I follow "Add an announcement"
    Then I should see "Add an announcement"
    Then wait for the page to be loaded
    Then I fill in the following:
      | title | Announcement for all users inside Group 0001 |
    And I fill in ckeditor field "content" with "Announcement description in Group 0001"
    Then I follow "announcement_preview"
    And wait for the page to be loaded
    Then I should see "Announcement will be sent to"
    Then I press "submit"
    Then I should see "Announcement has been added"

  Scenario: Create an announcement for fapple inside Group 0001
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0001"
    Then I should see "Group 0001"
    And I follow "Announcements"
    Then I should see "Announcements"
    Then I follow "Add an announcement"
    Then I should see "Add an announcement"
    Then wait for the page to be loaded
    Then I press "choose_recipients"
    Then I select "Fiona Apple" from "users"
    Then I press "users_rightSelected"
    Then I fill in the following:
      | title | Announcement for user fapple inside Group 0001 |
    And I fill in ckeditor field "content" with "Announcement description for user fapple inside Group 0001"
    Then I follow "announcement_preview"
    And wait for the page to be loaded
    Then I should see "Announcement will be sent to"
    Then I press "submit"
    Then I should see "Announcement has been added"

  Scenario: Create an announcement for everybody inside Group 0003 (private)
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0003"
    Then I should see "Group 0003"
    And I follow "Announcements"
    Then I should see "Announcements"
    Then I follow "Add an announcement"
    Then I should see "Add an announcement"
    Then wait for the page to be loaded
    Then I fill in the following:
      | title | Announcement for all users inside Group 0003 |
    And I fill in ckeditor field "content" with "Announcement description in Group 0003"
    Then I follow "announcement_preview"
    And wait for the page to be loaded
    Then I should see "Announcement will be sent to"
    Then I press "submit"
    Then I should see "Announcement has been added"

  Scenario: Create an announcement for fapple inside Group 0003
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0003"
    Then I should see "Group 0003"
    And I follow "Announcements"
    Then I should see "Announcements"
    Then I follow "Add an announcement"
    Then I should see "Add an announcement"
    Then wait for the page to be loaded
    Then I press "choose_recipients"
    Then I select "Fiona Apple" from "users"
    Then I press "users_rightSelected"
    Then I fill in the following:
      | title | Announcement for user fapple inside Group 0003 |
    And I fill in ckeditor field "content" with "Announcement description for user fapple inside Group 0003"
    Then I follow "announcement_preview"
    And wait for the page to be loaded
    Then I should see "Announcement will be sent to"
    Then I press "submit"
    Then I should see "Announcement has been added"

  Scenario: Create an announcement as acostea and send only to fapple
    Given I am logged as "acostea"
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0005"
    Then I should see "Group 0005"
    And I follow "Announcements"
    Then I should see "Announcements"
    Then I follow "Add an announcement"
    Then I should see "Add an announcement"
    Then wait for the page to be loaded
    Then I press "choose_recipients"
    Then I select "Fiona Apple Maggart" from "users"
    Then I press "users_rightSelected"
    Then I fill in the following:
      | title | Announcement only for fapple Group 0005 |
    And I fill in ckeditor field "content" with "Announcement description only for fapple Group 0005"
    Then I follow "announcement_preview"
    And wait for the page to be loaded
    Then I should see "Announcement will be sent to"
    Then I press "submit"
    Then I should see "Announcement has been added"

  Scenario: Check fapple/acostea access of announcements
    Given I am logged as "fapple"
    And I am on course "TEMP" homepage
    And I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0001"
    Then I should see "Group 0001"
    Then I follow "Announcements"
    And wait for the page to be loaded
    Then I should see "Announcement for all users inside Group 0001"
    Then I should see "Announcement for user fapple inside Group 0001"
    Then I follow "Announcement for user fapple inside Group 0001 Group"
    Then I should see "Announcement description for user fapple inside Group 0001"
    Then I save current URL with name "announcement_for_user_fapple_group_0001_public"
    Then I move backward one page
    Then wait for the page to be loaded
    Then I should see "Announcement for all users inside Group 0001"
    Then I follow "Announcement for all users inside Group 0001"
    Then I save current URL with name "announcement_for_all_users_group_0001_public"
    Then I should see "Announcement description in Group 0001"
    And I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0003"
    Then I should see "Group 0003"
    Then I follow "Announcements"
    And wait for the page to be loaded
    Then I should see "Announcement for all users inside Group 0003"
    Then I should see "Announcement for user fapple inside Group 0003"
    Then I follow "Announcement for user fapple inside Group 0003 Group"
    Then I should see "Announcement description for user fapple inside Group 0003"
    Then I save current URL with name "announcement_for_user_fapple_group_0003_private"
    Then I move backward one page
    Then wait for the page to be loaded
    Then I should see "Announcement for all users inside Group 0003"
    Then I follow "Announcement for all users inside Group 0003"
    Then I should see "Announcement description in Group 0003"
    Then I save current URL with name "announcement_for_all_users_group_0003_private"
    And I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    And I follow "Group 0005"
    Then I should see "Group 0005"
    Then I follow "Announcements"
    And wait for the page to be loaded
    Then I should see "Announcement only for fapple Group 0005"
    Then I follow "Announcement only for fapple Group 0005"
    Then I save current URL with name "announcement_only_for_fapple_private"

    ## Finish tests with fapple now check access with acostea ##
    Given I am logged as "acostea"
    And I am on course "TEMP" homepage
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    Then I should see "Group 0001"
    And I should see "Group 0002"
    And I should see "Group 0003"
    And I should see "Group 0004"
    Then I visit URL saved with name "announcement_for_user_fapple_group_0001_public"
    Then I should see "Sorry, you are not allowed to access this page"
    Then I visit URL saved with name "announcement_for_all_users_group_0001_public"
    Then I should see "Sorry, you are not allowed to access this page"
    Then I visit URL saved with name "announcement_only_for_fapple_private"
    Then I should see "Sorry, you are not allowed to access this page"

    Given I am logged as "acostea"
    And I am on course "TEMP" homepage
    Given I am on "/main/group/group.php?cidReq=TEMP&id_session=0"
    Then I should see "Group 0001"
    And I should see "Group 0002"
    And I should see "Group 0003"
    And I should see "Group 0004"
    And I should see "Group 0005"

    Then I visit URL saved with name "announcement_for_user_fapple_group_0001_public"
    Then I should see "Sorry, you are not allowed to access this page"
    Then I visit URL saved with name "announcement_for_all_users_group_0001_public"
    Then I should see "Sorry, you are not allowed to access this page"
    Then I visit URL saved with name "announcement_for_user_fapple_group_0003_private"
    Then I should see "Sorry, you are not allowed to access this page"
    Then I visit URL saved with name "announcement_for_all_users_group_0003_private"
    Then I should see "Sorry, you are not allowed to access this page"
    Then I visit URL saved with name "announcement_only_for_fapple_private"
    Then I should see "Sorry, you are not allowed to access this page"
