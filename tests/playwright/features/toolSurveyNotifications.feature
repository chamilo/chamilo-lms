# The confirmations of the Surveys tool use the platform's notification, the green toast every
# other tool shows after saving. They used to be boxes inside the page, styled with Tailwind
# colour classes (bg-green-50...) that the platform theme does not define, so they showed as
# black text in a black frame.
#
# Uses its own course SURVTOAST, deleted at the end with the survey it holds. The course is
# created in English so that the pages render in English whatever the administrator's language.
# The survey pages load their data after the page itself, and saving can take more than a minute
# on a development server, hence @slow-scenario.
@common @tools @slow-scenario
Feature: Confirmations of the Surveys tool
  In order to know that my survey changes were saved
  As a teacher
  I want the Surveys tool to confirm them like the rest of the platform

  Scenario: Create the survey notifications course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "SURVTOAST"
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "English" from "course_language"
    When I press "submit"
    And wait very long for the page to be loaded
    And I am on "/admin/course-list?keyword=SURVTOAST"
    And I wait for the page to be loaded
    Then I should see "SURVTOAST"

  Scenario: Create a survey
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "SURVTOAST" homepage
    And I wait for the page to be loaded
    And I follow the course tool "Surveys"
    And I wait for the page content to settle
    And I click the "[title='Create survey']" element
    And I wait up to 60 seconds for the element "#survey_code" to appear
    And I fill in "survey_code" with "SURVTOAST1"
    And I fill in tinymce field "survey_title" with "Toast survey"
    # The breadcrumb and the page heading also read "Create survey".
    When I click the "button:has-text('Create survey')" element
    Then I wait up to 60 seconds for the element "h1:has-text('Survey questions')" to appear

  Scenario: Adding a question is confirmed by the platform notification
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "SURVTOAST" homepage
    And I wait for the page to be loaded
    And I follow the course tool "Surveys"
    And I wait for the page content to settle
    And I click the "[title='Edit questions']" icon in the row for "Toast survey"
    And I wait up to 60 seconds for the element "text=Choose the type of question you want to add." to appear
    And I click the "button[title='Open']" element
    And I fill in tinymce field "survey_question_text" with "What did you think of the toast?"
    When I press "Create question"
    Then I wait up to 60 seconds for the element ".p-toast-message-success:has-text('The question has been added.')" to appear

  Scenario: Publishing the survey is confirmed by the platform notification
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "SURVTOAST" homepage
    And I wait for the page to be loaded
    And I follow the course tool "Surveys"
    And I wait for the page content to settle
    And I click the "[title='Publish']" icon in the row for "Toast survey"
    And I wait up to 60 seconds for the element "text=Publish survey" to appear
    When I press "Publish survey"
    Then I wait up to 60 seconds for the element ".p-toast-message-success" to appear

  Scenario: Delete the survey notifications course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/course-list?keyword=SURVTOAST"
    And I wait for the page to be loaded
    Then I click the "[title='Delete']" icon in the row for "SURVTOAST"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "SURVTOAST"
