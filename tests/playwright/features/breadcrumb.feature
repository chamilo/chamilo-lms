Feature: Breadcrumb visibility
  In order to know where I am
  As a user
  I need the breadcrumb to appear only where it has a trail to show

  # Assertions use the ".app-breadcrumb" container instead of crumb text, so the
  # scenarios stay valid whatever the platform language is.

  Scenario: The personal agenda shows no breadcrumb
    Given I am a platform administrator
    When I am on "/resources/ccalendarevent"
    And wait for the page to be loaded
    Then I should not see the ".app-breadcrumb" element

  Scenario: The course agenda shows a breadcrumb
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    When I click the "a[href*='ccalendarevent'][href*='cid=']" element
    And I wait for the element ".app-breadcrumb" to appear
    Then I should see the ".app-breadcrumb" element
