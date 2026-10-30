@common
Feature: CMS pages of the menu_public_links category
  Pages in the "menu_public_links" category are links meant for anonymous visitors only:
  they appear under the login form of the public homepage, and never in the sidebar of
  a logged-in user, where only "menu_links" pages are listed.

  Scenario: Administrator creates a page in the menu_public_links category
    Given I am a platform administrator
    And I am on "/resources/pages/new"
    And I wait for the element "#item_title" to appear
    When I fill in "item_title" with "PW public menu link"
    And I fill in "slug" with "pw-public-menu-link"
    And I check "enabled"
    And I select the dropdown option "menu_public_links" in "category"
    And I should not see the ".p-select-overlay" element
    And I select the dropdown option "English" in "locale"
    And I fill in the active tinymce editor with "Public menu link content"
    And I press "Save"
    Then I wait for the element "button[name='cms-page-delete-open']" to appear
    And I should not see the "#item_title" element

  Scenario: Anonymous visitors see the link under the login form
    Given I am not logged
    And I am on the homepage
    Then I should see the "a[href='/pages/pw-public-menu-link']" element

  Scenario: Logged-in users do not see the link
    Given I am a platform administrator
    And I am on the homepage
    Then I should not see the "a[href='/pages/pw-public-menu-link']" element

  Scenario: Administrator deletes the page
    Given I am a platform administrator
    And I am on "/resources/pages"
    When I click the "button[name='cms-page-delete-open']" icon in the row for "PW public menu link"
    And I click the "button[name='cms-page-delete-confirm']" element
    Then I should not see "PW public menu link"
    When I am not logged
    And I am on the homepage
    Then I should not see the "a[href='/pages/pw-public-menu-link']" element
