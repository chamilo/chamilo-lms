Feature: Survey tool
  In order to use the survey tool
  The teachers should be able to create surveys

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create a question category
    Given I am on "/main/exercise/tests_category.php?action=addcategory&cidReq=TEMP"
    And wait for the page to be loaded
    When I fill in the following:
      | category_name | Category 1 |
    And I fill in ckeditor field "category_description" with "Category 1 description"
    And I press "SubmitNote"
    Then I should see "Category added"

  Scenario: Create a second question category
    Given I am on "/main/exercise/tests_category.php?action=addcategory&cidReq=TEMP"
    And wait for the page to be loaded
    When I fill in the following:
      | category_name | Category 2 |
    And I fill in ckeditor field "category_description" with "Category 2 description"
    And I press "SubmitNote"
    Then I should see "Category added"

  Scenario: Create a survey
    Given I am on "/main/survey/create_new_survey.php?cidReq=TEMP&action=add"
    And I press advanced settings
    When I fill in the following:
      | survey_code | Survey 1 |
    And I fill in ckeditor field "survey_title" with "Survey 1"
    And I press "submit_survey"
    Then I should see "The survey has been created succesfully"

  Scenario: Edit an Survey
    Given I am on "/main/survey/survey_list.php?cidReq=TEMP"
    And I follow "Survey 1"
    And I follow "Edit survey"
    And I press "submit_survey"
    Then I should see "The survey has been updated succesfully"

  Scenario: Add question "Yes / No" to survey created "Survey 1"
    Given I am on "/main/survey/survey_list.php?cidReq=TEMP"
    And I follow "Survey 1"
    And I follow "Yes / No"
    And I fill in ckeditor field "question" with "Yes / No"
    And I press "buttons[save]"
    Then I should see "The question has been added."

  Scenario: Duplicate survey
    Given I am on "/main/survey/survey_list.php?cidReq=TEMP"
    And I follow "Duplicate survey"
    And I press "Copy survey"
    Then I should see "Survey copied"
    And I should see "Survey 1 Copy"

  Scenario: Delete an survey
    Given I am on "/main/survey/survey_list.php?cidReq=TEMP"
    And I follow "Delete"
    And I confirm the popup
    Then I should see "The survey has been deleted."