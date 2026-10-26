@common
Feature: CMS page list supports explicit bulk deletion
  Scenario: Administrator can review a selection and cancel without deleting pages
    Given I am a platform administrator
    And I am on "/resources/pages"
    Then I should see the ".p-datatable tbody tr" element
    When I click the checkbox in table row 1
    And I click the checkbox in table row 2
    Then I should see the "button[name='cms-pages-bulk-delete-open']" element
    When I click the "button[name='cms-pages-bulk-delete-open']" element
    Then I should see the "button[name='cms-pages-bulk-delete-confirm']" element
    When I click the "button[name='cms-pages-bulk-delete-cancel']" element
    Then I should not see the "button[name='cms-pages-bulk-delete-confirm']" element
    And I should see the ".p-datatable tbody tr" element
