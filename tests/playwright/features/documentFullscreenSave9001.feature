Feature: GH9001 Save HTML documents directly from TinyMCE fullscreen
  The document Save button must use Chamilo's existing save flow, not TinyMCE native submit.

  Scenario: Create and edit an HTML document without leaving fullscreen
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I follow "Documents"
    And I wait for the page to be loaded
    When I open the GH9001 document creation form
    And I fill in the following:
      | title | PW GH9001 fullscreen document |
    And I fill in tinymce field "item_content" with "Initial GH9001 document content"
    And I enable fullscreen for the document editor
    Then the document Save button is available in fullscreen
    When I save the document from the fullscreen toolbar
    Then I should see "PW GH9001 fullscreen document"
    When I edit the GH9001 document "PW GH9001 fullscreen document"
    Then the GH9001 document editor contains "Initial GH9001 document content"
    When I fill in tinymce field "item_content" with "Updated GH9001 document content"
    And I enable fullscreen for the document editor
    Then the document Save button is available in fullscreen
    When I save the document from the fullscreen toolbar
    Then I should see "PW GH9001 fullscreen document"
    When I edit the GH9001 document "PW GH9001 fullscreen document"
    Then the GH9001 document editor contains "Updated GH9001 document content"
    When I return to the GH9001 document list
    Then I delete the document "PW GH9001 fullscreen document" if present
