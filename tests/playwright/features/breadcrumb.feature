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

  # The next two cover meta.breadcrumbParents: a page that hangs from a list page
  # links back to every ancestor the route declares.

  Scenario: A class page links back to the class list
    Given I am a platform administrator
    When I am on "/admin/usergroup-import"
    And I wait for the element ".app-breadcrumb" to appear
    Then I should see the ".app-breadcrumb a[href='/admin/usergroups']" element

  Scenario: An access URL page links back to both its ancestors
    Given I am a platform administrator
    When I am on "/admin/urls/assign-users"
    And I wait for the element ".app-breadcrumb" to appear
    Then I should see the ".app-breadcrumb a[href='/admin/urls']" element
    And I should see the ".app-breadcrumb a[href='/admin/urls/manage']" element

  # A settings page is the one trail the router cannot declare: its last crumb is
  # read from the DOM, because the server already translated it.
  Scenario: A settings page links back to the settings list
    Given I am a platform administrator
    When I am on "/admin/settings/search_settings?keyword=allow_registration"
    And I wait for the element ".app-breadcrumb" to appear
    Then I should see the ".app-breadcrumb a[href='/admin/settings']" element
