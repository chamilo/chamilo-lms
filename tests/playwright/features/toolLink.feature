# Rewritten against the Vue links tool. public/main/link/link.php only
# redirects to /resources/links/{courseNodeId}/ now, and drops its `action`
# query parameter on the way, so the old addcategory/addlink URLs land on the
# list and the legacy field names (category_title, url, category_id) and
# buttons (submitCategory, submitLink) no longer exist.
#
# Selectors confirmed against the running app with a DOM dump:
#
# - The tool is reached from the course homepage; "Add a link" and
#   "Add a category" are buttons that route to the create forms.
# - The category form fields are #category_category_title and #description
#   (LinkCategoryForm.vue). The link form fields are `url` and `title`
#   (LinkForm.vue), and its category is a PrimeVue Select (#link-category)
#   inside the collapsed "Advanced settings".
# - The list has no table: every category is a PrimeVue `.p-card` and every
#   link an `<li>` inside the card of its category (or the "General" one),
#   each titled by its own heading, with icon-only buttons titled "Delete",
#   "Edit"... The card step picks the innermost item with that exact heading,
#   so a link's buttons are never mistaken for its category's.
# - Deletion is a BaseDialogDelete whose confirm button reads "Yes". Deleting
#   a category does not delete its links (CLink.category is ON DELETE SET
#   NULL), so the categorized link is deleted on its own first, to leave the
#   shared TEMP course as this file found it.
#
# Test data uses non-overlapping strings so no title is a substring of
# another one.
Feature: Link tool
  In order to use the link tool
  The teachers should be able to create link categories and links

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow the course tool "Links"
    And I wait up to 30 seconds for the element "button:has-text('Add a category')" to appear

  Scenario: Create a link category
    When I press "Add a category"
    And I wait up to 30 seconds for the element "#category_category_title" to appear
    And I fill in the following:
      | category_category_title | Link Category Test   |
      | description             | Category description |
    And I press "Save the category"
    Then I wait up to 30 seconds for the element "h5:text-is('Link Category Test')" to appear
    And I should not see an error

  Scenario: Create a link
    When I press "Add a link"
    And I wait up to 30 seconds for the element "input[name='url']" to appear
    And I fill in the following:
      | url   | http://www.chamilo.org |
      | title | Chamilo Link Test      |
    And I press "Save link"
    Then I wait up to 30 seconds for the element "h6:has-text('Chamilo Link Test')" to appear
    And I should not see an error

  Scenario: Create a link with category
    When I press "Add a link"
    And I wait up to 30 seconds for the element "input[name='url']" to appear
    And I fill in the following:
      | url   | http://www.chamilo.org        |
      | title | Chamilo Categorized Link Test |
    And I press "Advanced settings"
    And I select the dropdown option "Link Category Test" in "link-category"
    And I press "Save link"
    Then I wait up to 30 seconds for the element ".p-card:has(h5:text-is('Link Category Test')) h6:has-text('Chamilo Categorized Link Test')" to appear
    And I should not see an error

  Scenario: Delete link
    When I click the "button[title='Delete']" icon in the card for "Chamilo Link Test"
    And I press "Yes"
    Then I wait until I no longer see "Chamilo Link Test"
    And I should not see an error

  Scenario: Delete categorized link
    When I click the "button[title='Delete']" icon in the card for "Chamilo Categorized Link Test"
    And I press "Yes"
    Then I wait until I no longer see "Chamilo Categorized Link Test"
    And I should not see an error

  Scenario: Delete link category
    When I click the "button[title='Delete']" icon in the card for "Link Category Test"
    And I press "Yes"
    Then I wait until I no longer see "Link Category Test"
    And I should not see an error
