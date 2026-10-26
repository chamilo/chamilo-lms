@common
Feature: CMS page deletion across languages
  Platform administrators can explicitly select CMS pages from different languages.
  Opening and cancelling the delete dialog never changes any page.

  Scenario: Administrator can review CMS page deletion without deleting anything
    Given I am a platform administrator
    And I am on "/resources/pages"
    Then I should see the "tbody tr:first-child button[name='cms-page-delete-open']" element
    When I click the "tbody tr:first-child button[name='cms-page-delete-open']" element
    And I should see the "button[name='cms-page-delete-confirm']" element
    When I click the "button[name='cms-page-delete-cancel']" element
    Then I should not see the "button[name='cms-page-delete-confirm']" element

  Scenario: Anonymous visitors cannot see CMS page deletion controls
    Given I am not logged
    And I am on "/resources/pages"
    Then I should not see the "button[name='cms-page-delete-open']" element
