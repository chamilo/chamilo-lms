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

  # The agenda's third label, "Group agenda", has no scenario on purpose. It needs a
  # real group — CidReqListener rejects an invented gid — and groups are created
  # through the legacy PHP tool, whose buttons are matched by English text. The only
  # thing that tells that label apart from "Agenda" is the crumb text itself, and the
  # key has no French translation, so the assertion would pass or fail with the
  # environment language. What is left uncovered is one term of a ternary in
  # assets/vue/router/ccalendarevent.js.

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

  # The next two cover meta.breadcrumbResource. Only the course part of the trail
  # is asserted: the tool crumb is the last one, and the component renders the last
  # crumb as plain text, so it carries no href to match.
  #
  # These two need the long wait. A tool that declares breadcrumbResource renders its
  # breadcrumb only after the resource-node fetch resolves, and that call takes well
  # over the default 15 s here. The other scenarios need no fetch and appear at once.

  Scenario: The document tool builds the whole course trail
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    When I click the "a[href*='/resources/document/'][href*='cid=']" element
    And I wait up to 45 seconds for the element ".app-breadcrumb" to appear
    Then I should see the ".app-breadcrumb a[href='/courses']" element

  Scenario: The assignment tool builds the whole course trail
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    When I click the "a[href*='/resources/assignment/'][href*='cid=']" element
    And I wait up to 45 seconds for the element ".app-breadcrumb" to appear
    Then I should see the ".app-breadcrumb a[href='/courses']" element

  # Covers the "ancestors" trail: with a folder open, the tool crumb stops being the
  # last one and becomes a link. The scenario creates the folder and deletes it again.
  Scenario: A document folder adds its own crumb to the trail
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    When I click the "a[href*='/resources/document/'][href*='cid=']" element
    And I wait up to 45 seconds for the element "button:has(.mdi-folder-plus)" to appear
    And I click the "button:has(.mdi-folder-plus)" element
    And I fill in "title" with "BcTmpFolder"
    And I click the ".p-dialog button:has(.mdi-check)" element
    And I wait up to 30 seconds for the element "a:has-text('BcTmpFolder')" to appear
    And I follow "BcTmpFolder"
    And I wait up to 45 seconds for the element ".app-breadcrumb a[href*='/resources/document/']" to appear
    Then I should see the ".app-breadcrumb a[href*='/resources/document/']" element
    And I click the ".app-breadcrumb a[href*='/resources/document/']" element
    And I wait up to 45 seconds for the element "button:has(.mdi-delete)" to appear
    And I click the "button:has(.mdi-delete)" element
    And I click the ".p-dialog button:has(.mdi-check)" element
    And I wait until I no longer see "BcTmpFolder"

  # A wiki page is not a platform Page. The trail used to gain a "Pages" crumb
  # pointing at /resources/pages, because the route name contains "Page".
  Scenario: A wiki page does not link to the platform pages
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    When I click the "a[href*='/resources/wiki/'][href*='cid=']" element
    And I wait up to 45 seconds for the element ".app-breadcrumb" to appear
    Then I should not see the ".app-breadcrumb a[href='/resources/pages']" element
    And I should see the ".app-breadcrumb a[href='/courses']" element

  Scenario: A room page links back to the room list
    Given I am a platform administrator
    When I am on "/resources/rooms/new"
    And I wait for the element ".app-breadcrumb" to appear
    Then I should see the ".app-breadcrumb a[href='/admin']" element
    And I should see the ".app-breadcrumb a[href='/resources/rooms']" element

  # A settings page is the one trail the router cannot declare: its last crumb is
  # read from the DOM, because the server already translated it.
  Scenario: A settings page links back to the settings list
    Given I am a platform administrator
    When I am on "/admin/settings/search_settings?keyword=allow_registration"
    And I wait for the element ".app-breadcrumb" to appear
    Then I should see the ".app-breadcrumb a[href='/admin/settings']" element
