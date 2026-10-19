# A new document, test or learning path gets the visibility the administrator chose for its tool
# (Documents, Tests, Learning paths). When the administrator allows it, a course replaces that
# choice in its own settings, so a teacher can prepare content before learners see it.
#
# The platform values are global and other files run in parallel: turning documents or tests
# invisible platform-wide would hide what those files create and expect to see. So this file
# keeps the platform values untouched and checks the course-level choice in its own course,
# DEFVIS, which it deletes at the end. Letting courses choose changes nothing for a course that
# has not chosen, so it does not disturb the other files either. How the platform value, the
# course value and the "can be changed by course" switch combine is pinned by
# ResourceDefaultVisibilityHelperTest.
@common @tools
Feature: Default visibility of new documents, tests and learning paths
  In order to prepare content before learners see it
  As a platform administrator and a teacher
  I want new documents, tests and learning paths to get the visibility chosen for the platform or the course

  Scenario: The administrator finds the default visibility of each tool
    Given I am a platform administrator
    And I wait for the page to be loaded
    When I am on "/admin/settings/search_settings?keyword=default_visibility"
    And I wait for the page to be loaded
    Then I should see "Default visibility of new documents"
    And I should see "Default visibility of new tests"
    And I should see "Default visibility of new learning paths"
    And the field "form_document_default_visibility_on_creation" should have value "visible"
    And the field "form_exercise_default_visibility_on_creation" should have value "visible"
    And the field "form_lp_default_visibility_on_creation" should have value "invisible"

  Scenario: A teacher cannot change the platform settings
    Given I am a teacher
    And I wait for the page to be loaded
    When I am on "/admin/settings/document"
    And I wait for the page to be loaded
    Then I should not see "Default visibility of new documents"

  @long-scenario
  Scenario: Create the default visibility course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "DEFVIS"
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "English" from "course_language"
    When I press "submit"
    And wait very long for the page to be loaded
    Then I wait up to 60 seconds for the element "text=DEFVIS" to appear

  Scenario: Subscribe the teacher to the default visibility course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "DEFVIS" homepage
    And I wait for the page to be loaded
    And I follow the course tool "Users"
    And I wait for the page to be loaded
    And I press "Teachers"
    And the URL should contain "type=1"
    And I click the "[title='Add']" element
    And the URL should contain "type=1"
    And I wait for the page to be loaded
    And I fill in the following:
      | search | mmosquera |
    And I press "Search"
    And I wait for the page to be loaded
    Then I should see "Mosquera"
    And I click the "[title='Register']" element
    And I wait for the page to be loaded
    Then I should see "subscribed to the course"

  Scenario: Subscribe the learner to the default visibility course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "DEFVIS" homepage
    And I wait for the page to be loaded
    And I press "Show all"
    And I wait for the page content to settle
    And I follow the course tool "Users"
    And I wait for the page to be loaded
    And I click the "[title='Add']" element
    And I wait for the page to be loaded
    And I fill in the following:
      | search | acostea |
    And I press "Search"
    And I wait for the page to be loaded
    Then I should see "Costea"
    And I click the "[title='Register']" element
    And I wait for the page to be loaded
    Then I should see "subscribed to the course"

  Scenario: The course cannot choose while the administrator does not allow it
    Given I am a teacher
    And I wait for the page to be loaded
    When I am on the settings form of course "DEFVIS"
    And I wait up to 30 seconds for the element "text=Save settings" to appear
    Then I should not see "Default visibility for new documents"
    And I should not see "Default visibility for new tests"
    And I should not see "Default visibility for new learning paths"

  # The admin settings pages, the course settings form and the course creation are slow on a
  # shared dev box: a scenario that chains several of them exceeds the default 90-second budget.
  @long-scenario
  Scenario: The administrator lets each course choose the default visibility
    Given I am a platform administrator
    And I wait for the page to be loaded
    When I am on "/admin/settings/search_settings?keyword=document_default_visibility_can_be_modified_in_course"
    And I wait for the page to be loaded
    And I select "Yes" from "form_document_default_visibility_can_be_modified_in_course"
    And I press "Save settings"
    And I wait for the page to be loaded
    And I am on "/admin/settings/search_settings?keyword=exercise_default_visibility_can_be_modified_in_course"
    And I wait for the page to be loaded
    And I select "Yes" from "form_exercise_default_visibility_can_be_modified_in_course"
    And I press "Save settings"
    And I wait for the page to be loaded
    And I am on "/admin/settings/search_settings?keyword=lp_default_visibility_can_be_modified_in_course"
    And I wait for the page to be loaded
    And I select "Yes" from "form_lp_default_visibility_can_be_modified_in_course"
    And I press "Save settings"
    And I wait for the page to be loaded
    And I am on "/admin/settings/search_settings?keyword=default_visibility_can_be_modified_in_course"
    And I wait for the page to be loaded
    Then the field "form_document_default_visibility_can_be_modified_in_course" should have value "true"
    And the field "form_exercise_default_visibility_can_be_modified_in_course" should have value "true"
    And the field "form_lp_default_visibility_can_be_modified_in_course" should have value "true"

  # The opposite of each platform default, so the result cannot come from the platform value.
  @long-scenario
  Scenario: The teacher hides new documents and tests and shows new learning paths
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on the settings form of course "DEFVIS"
    # Each option sits in the collapsed <details> panel of its tool.
    And I wait up to 30 seconds for the element "details:has(input[name='document_default_visibility_on_creation']) > summary" to appear
    And I click the "details:has(input[name='document_default_visibility_on_creation']) > summary" element
    And I click the "details:has(input[name='exercise_default_visibility_on_creation']) > summary" element
    And I click the "details:has(input[name='lp_default_visibility_on_creation']) > summary" element
    When I check the "document_default_visibility_on_creation" radio button with "invisible" value
    And I check the "exercise_default_visibility_on_creation" radio button with "invisible" value
    And I check the "lp_default_visibility_on_creation" radio button with "visible" value
    And I press "Save settings"
    And I wait for the page content to settle
    And I am on the settings form of course "DEFVIS"
    And I wait up to 30 seconds for the element "details:has(input[name='document_default_visibility_on_creation']) > summary" to appear
    Then the "document_default_visibility_on_creation" radio button with "invisible" value should be checked
    And the "exercise_default_visibility_on_creation" radio button with "invisible" value should be checked
    And the "lp_default_visibility_on_creation" radio button with "visible" value should be checked

  Scenario: The teacher creates a folder
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "DEFVIS" homepage
    And I wait for the page to be loaded
    And I follow "Documents"
    And I wait for the page to be loaded
    When I press "New folder"
    And I fill in the following:
      | title | Defvis folder |
    And I press "Save"
    Then I should see "Saved"
    And I wait up to 30 seconds for the element "text=Defvis folder" to appear

  Scenario: The teacher creates a test
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "DEFVIS" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Create exercise"
    And I wait for the page content to settle
    When I fill in "title" with "Defvis test"
    And I press "Proceed to questions"
    And I wait for the page content to settle
    Then I should not see an error

  @long-scenario
  Scenario: The teacher creates a learning path
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "DEFVIS" homepage
    And I wait for the page to be loaded
    And I follow "Learning paths"
    And I wait for the page content to settle
    And I press "Create new learning path"
    And I wait for the page content to settle
    When I fill in "title" with "Defvis path"
    And I press "Continue"
    And I wait for the page content to settle
    Then I should not see an error

  @long-scenario
  Scenario: The learner sees the new learning path but not the new folder and test
    Given I am a student
    And I wait for the page to be loaded
    And I am on course "DEFVIS" homepage
    And I wait for the page to be loaded
    When I follow "Learning paths"
    And I wait for the page content to settle
    Then I wait up to 30 seconds for the element "text=Defvis path" to appear
    When I am on course "DEFVIS" homepage
    And I wait for the page to be loaded
    And I follow "Documents"
    And I wait for the page content to settle
    Then I should not see "Defvis folder"
    When I am on course "DEFVIS" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    Then I should not see "Defvis test"

  @long-scenario
  Scenario: Teardown — the courses can no longer choose, and the course is deleted
    Given I am a platform administrator
    And I wait for the page to be loaded
    When I am on "/admin/settings/search_settings?keyword=document_default_visibility_can_be_modified_in_course"
    And I wait for the page to be loaded
    And I select "No" from "form_document_default_visibility_can_be_modified_in_course"
    And I press "Save settings"
    And I wait for the page to be loaded
    And I am on "/admin/settings/search_settings?keyword=exercise_default_visibility_can_be_modified_in_course"
    And I wait for the page to be loaded
    And I select "No" from "form_exercise_default_visibility_can_be_modified_in_course"
    And I press "Save settings"
    And I wait for the page to be loaded
    And I am on "/admin/settings/search_settings?keyword=lp_default_visibility_can_be_modified_in_course"
    And I wait for the page to be loaded
    And I select "No" from "form_lp_default_visibility_can_be_modified_in_course"
    And I press "Save settings"
    And I wait for the page to be loaded
    And I am on "/admin/course-list?keyword=DEFVIS"
    And I wait for the page to be loaded
    And I click the "[title='Delete']" icon in the row for "DEFVIS"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "DEFVIS"
