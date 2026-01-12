Feature: Course catalogue and extra fields
  In order to test course catalogue search and extra fields
  As an administrator
  I want to create courses, search them in the catalogue and manage extra fields

  Background:
    Given I am a platform administrator

  Scenario: Create three courses for catalogue testing
    Given I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    When I fill in "title" with "testcourse"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "testcourse"

    Given I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    When I fill in "title" with "grammarcourse"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "grammarcourse"

    Given I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    When I fill in "title" with "grammartest"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "grammartest"

  Scenario: Search courses in catalogue by title
    Given I am on "/catalogue/courses"
    And I wait for the page to be loaded
    When I fill in "search_catalogue" with "test"
    And I wait for the page to be loaded
    Then I should see "testcourse"
    And I should see "grammartest"
    And I should not see "grammarcourse"


  Scenario: Search courses in catalogue by title (search "course")
    Given I am on "/catalogue/courses"
    And I wait for the page to be loaded
    When I fill in "search_catalogue" with "course"
    And I wait for the page to be loaded
    Then I should see "testcourse"
    And I should see "grammarcourse"
    And I should not see "grammartest"

  Scenario: Search courses in catalogue by title (search "course" via filters)
    Given I am on "/catalogue/courses"
    And I wait for the page to be loaded
    Then I click the "span.pi-sliders-h" element
    And I wait for the page to be loaded
    When I fill in "search_by_title" with "course"
    And I click the "span.pi-filter" element
    And I wait for the page to be loaded
    Then I should see "testcourse"
    And I should see "grammarcourse"
    And I should not see "grammartest"

  Scenario: Search courses in catalogue by title (search "test" via filters)
    Given I am on "/catalogue/courses"
    And I wait for the page to be loaded
    Then I click the "span.pi-sliders-h" element
    And I wait for the page to be loaded
    When I fill in "search_by_title" with "test"
    And I click the "span.pi-filter" element
    And I wait for the page to be loaded
    Then I should see "testcourse"
    And I should see "grammartest"
    And I should not see "grammarcourse"


  Scenario: Add an extra field "Duration" for courses
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Manage extra fields for courses"
    And I wait for the page to be loaded
    Then I click the "i.mdi-plus-box" element
    And I wait for the page to be loaded
    When I fill in "course_field_display_text" with "Duration"
    And I fill in "course_field_variable" with "duration"
    And I click the "input#visible_to_self_yes" element
    And I click the "input#visible_to_others_yes" element
    And I click the "input#changeable_no" element
    And I click the "input#filter_yes" element
    And I press "course_field_submit"
    And wait very long for the page to be loaded
    Then I should see "duration"


  Scenario: Update course extra field value
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Course list"
    And I wait for the page to be loaded
    Then I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    When I fill in "update_course_extra_duration" with "22:22:22"
    And I press "update_course_submit"
    And I wait for the page to be loaded
    Then I should see "Update successful"

  Scenario: Update catalogue settings to include extra field in search form
    Given I am on "/admin/settings/catalog"
    And I wait for the page to be loaded
    When I fill in "form_course_catalog_settings" with "{\"extra_fields_in_search_form\":[\"duration\"]}"
    And I zoom out to maximum
    And I press "Save settings"
    And I wait for the page to be loaded
    Then I should see "extra_fields_in_search_form"

  Scenario: Search courses in catalogue by extra field (Duration = "22:22:22")
    Given I am on "/catalogue/courses"
    And I wait for the page to be loaded
    Then I click the "span.pi-sliders-h" element
    And I wait for the page to be loaded
    When I fill in "extra_duration" with "22:22:22"
    And I click the "span.pi-filter" element
    And I wait for the page to be loaded
    Then I should see "grammartest"
    And I should not see "testcourse"
    And I should not see "grammarcourse"


