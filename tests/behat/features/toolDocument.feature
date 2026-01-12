Feature: Document tool
  In order to use the document tool
  The teachers should be able to create and upload files

  Scenario: Create a folder
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    Then I follow "Document"
    And wait for the page to be loaded
    Then I click the "span.mdi-folder-plus" element
    And I wait for the page to be loaded
    Then I fill in the following:
      | title | My new directory |
    And I press "Save"
    And wait for the page to be loaded
    Then I should see "saved"

  Scenario: Create a folder that already exists
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    Then I follow "Document"
    And wait for the page to be loaded
    Then I click the "span.mdi-folder-plus" element
    And I wait for the page to be loaded
    And I fill in the following:
      | title | My new directory |
    And I press "Save"
    Then I should see "saved"

  Scenario: Create a text document
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    Then I follow "Document"
    And wait for the page to be loaded
    Then I click the "span.mdi-file-plus" element
    And wait for the page to be loaded
    Then I fill in the following:
      | Title   | My first document |
    And I fill in tinymce field "item_content" with "This is my first document!"
    And I press "Save"
    And wait for the page to be loaded
    Then I should see "created"
    And I should see "My first document"
    And wait for the page to be loaded

  Scenario: Create a HTML document
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    Then I follow "Document"
    And wait for the page to be loaded
    Then I click the "span.mdi-file-plus" element
    And wait for the page to be loaded
    Then I fill in the following:
      | Title   | My second document |
    And I fill in tinymce field "item_content" with "<a href='www.chamilo.org'>Click here</a><span><b>This is my second document!!</b></span>"
    And I click the "span.mdi-content-save" element
    And wait for the page to be loaded
    Then I should see "created"
    And I should see "My second document"

  Scenario: Upload a document
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    Then I follow "Document"
    And wait for the page to be loaded
    Then I click the "span.mdi-file-upload" element
    And wait for the page to be loaded
    Then I should see "Drop files here"
    Then I attach the file "/public/favicon.ico" to "files[]"
    Then I press "Upload 1 file"
    And wait for the page to be loaded
    Then I should see "created"
    Then I should see "favicon.ico"

  Scenario: Search for "My second document" and edit it
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    Then I follow "Documents"
    And wait for the page to be loaded
    Then I should see "My second document"
    Then I click the "span.mdi-pencil" element
    And wait for the page to be loaded
    Then I fill in the following:
      | item_title | My second document edited |
    Then I press "Save"
    And wait very long for the page to be loaded
    Then I should see "My second document edited"

  Scenario: Search for "My first document" and delete it
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    Then I follow "Document"
    And wait for the page to be loaded
    Then I should see "My first document"
    Then I press "Info"
    And wait for the page to be loaded
    Then I should see "My first document"
    Then I press "Delete"
    And wait for the page to be loaded
    And I press "Yes"
    And wait for the page to be loaded
    Then I should see "Deleted"

  Scenario: Delete simple document
    Then I follow "document"
    Then I press "File upload"
    And wait for the page to be loaded
    Then I follow "My first document"
    Then I should see "Created at"
    Then I follow "Delete"
    Then I should see "Deleted"
    And I should not see "My first document.html"
