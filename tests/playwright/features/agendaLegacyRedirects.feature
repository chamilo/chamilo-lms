# The legacy agenda pages below were replaced by redirects to the Vue agenda
# (/resources/ccalendarevent). Bookmarks and the legacy pages that still link
# to them must land on the matching Vue view, keeping the year and the course
# context, so no user loses the page they had.
Feature: Legacy agenda redirects
  In order to keep old agenda links working
  As any user with an agenda
  I need the legacy agenda pages to open the matching Vue agenda view

  Scenario Outline: The legacy sessions plan opens the Vue sessions plan as <role>
    Given <login>
    When I am on "/main/calendar/planification.php?year=2025"
    And I wait for the URL to contain "/resources/ccalendarevent/sessions-plan"
    Then the URL should contain "year=2025"
    And I should see "Sessions plan calendar"

    Examples:
      | role          | login                          |
      | administrator | I am a platform administrator  |
      | teacher       | I am a teacher                 |
      | student       | I am a student                 |

  Scenario Outline: The legacy personal event list opens the Vue event list as <role>
    Given <login>
    When I am on "/main/calendar/agenda_list.php?type=personal"
    And I wait for the URL to contain "/resources/ccalendarevent/list"
    Then the URL should not contain "cid="
    And I should see "Events list"

    Examples:
      | role          | login                          |
      | administrator | I am a platform administrator  |
      | teacher       | I am a teacher                 |
      | student       | I am a student                 |

  Scenario: The legacy platform event list opens the global Vue event list
    Given I am a platform administrator
    When I am on "/main/calendar/agenda_list.php?type=admin"
    And I wait for the URL to contain "/resources/ccalendarevent/list"
    Then the URL should contain "type=global"
    And I should see "Events list"

  Scenario: The sessions reporting toolbar opens the Vue sessions plan
    Given I am a platform administrator
    When I am on "/reporting/sessions"
    And I wait for the element "a[title='Sessions plan calendar']" to appear
    And I click the "a[title='Sessions plan calendar']" element
    And I wait for the URL to contain "/resources/ccalendarevent/sessions-plan"
    Then I should see "Sessions plan calendar"

  Scenario: The legacy course event list keeps the course context
    Given I am a platform administrator
    And I resolve the numeric id of course "TEMP"
    When I am on "/main/calendar/agenda_list.php?type=course&cid=COURSE_ID&sid=0&gid=0" with the resolved id of course "TEMP"
    And I wait for the URL to contain "/resources/ccalendarevent/list"
    Then the URL should contain "cid="
    And the URL should not contain "type=global"
    And I should see "Events list"
