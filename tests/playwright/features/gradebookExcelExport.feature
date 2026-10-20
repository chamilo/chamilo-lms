# Issue #1642: the Excel export of the Gradebook list view used to put every
# score in one text cell ("6.00 / 10.00 (60.00%)"), which a spreadsheet cannot
# compute with. Each score must now come out as separate percentage, score and
# maximum score columns.
#
# Self-contained: works on its own course (GBXLS) with its own learner
# subscription and deletes the course at the end, so it never depends on, or
# changes, the shared TEMP course.
@common @tools
Feature: Gradebook Excel export
  Teachers export the Gradebook list view to Excel to work with the scores

  Scenario: Create a course with a graded learner
    Given I am a platform administrator
    And I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "GBXLS"
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "English" from "course_language"
    When I press "submit"
    And wait very long for the page to be loaded
    Then I should see "GBXLS"
    And I resolve the numeric id of course "GBXLS"
    Given I am on "/main/user/subscribe_user.php?keyword=norizales&type=5&cid=COURSE_ID" with the resolved id of course "GBXLS"
    And wait for the page to be loaded
    Then I should see "Noa"
    Then I follow "Register"
    And wait very long for the page to be loaded
    Then I should not see an error
    Given I am on course "GBXLS" homepage
    And I wait for the page to be loaded
    And I follow "Assessments"
    And I wait for the page content to settle
    Then I press "Add classroom activity"
    When I fill in the following:
      | gradebook-evaluation-title     | xlsexam |
      | gradebook-evaluation-weight    | 100     |
      | gradebook-evaluation-max-score | 10      |
      | gradebook-evaluation-min-score | 3       |
    And I check "Grade learners"
    And I press "Add classroom activity"
    And I wait for the page content to settle
    When I fill in the score for "norizales" with "6"
    And I press "Save"
    And I wait for the page content to settle
    Then I should see "xlsexam"

  Scenario: The Excel export splits each score into numeric columns
    Given I am a platform administrator
    And I am on course "GBXLS" homepage
    And I wait for the page to be loaded
    And I follow "Assessments"
    And I wait for the page content to settle
    And I click the "[title='List view']" element
    And wait for the page to be loaded when ready
    Then I should see "Noa"
    When I download the file from the link "Export Excel"
    Then the downloaded file should contain "xlsexam (Percentage)"
    And the downloaded file should contain "xlsexam (Score)"
    And the downloaded file should contain "xlsexam (Max score)"
    And the downloaded file should contain "Total (Percentage)"

  Scenario: Delete the course
    Given I am a platform administrator
    And I am on course "GBXLS" homepage
    And I wait for the page content to settle
    And I press "More actions"
    And I follow "Course maintenance"
    And I wait for the page content to settle
    And I follow "Completely delete this course"
    And I wait for the page content to settle
    And I fill in the following:
      | delete-course-code | GBXLS |
    And I check "delete-docs"
    And I click the "button.p-button-danger:has-text('Delete course')" element
    And I wait for the page content to settle
    Then I should not see "GBXLS"
