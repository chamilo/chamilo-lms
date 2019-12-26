Feature: Document tool
  In order to use the document tool
  The teachers should be able to create and upload files

  Background:
    Given I am a platform administrator

  Scenario: Create a folder
    Given I am on "/resources/document/files?cid=1&sid=0"
    Then I follow "New folder"
    And I fill in the following:
      | c_document_title | My new directory |
    And I press "Save"
    Then I should see "Saved"

  Scenario: Create a folder that already exists
    Given I am on "/resources/document/files?cid=1&sid=0"
    Then I follow "New folder"
    And I fill in the following:
      | c_document_title | My new directory |
    And I press "Save"
    Then I should see "Saved"

  Scenario: Create a simple document
    Given I am on "/resources/document/files?cid=1&sid=0"
    Then I follow "Create new document"
    Then I fill in the following:
      | c_document_title   | My first document |
    And I fill in ckeditor field "c_document_content" with "This is my first document!!!"
    And wait for the page to be loaded
    And I press "c_document_save"
    Then I should see "Saved"
    And I should see "My first document.html"
    Then I follow "Info My first document.html"
    And wait the page to be loaded when ready
    Then I follow "View"
    Then I should see "This is my first document"

  Scenario: Create a HTML document
    Given I am on "/resources/document/files?cid=1&sid=0"
    Then I follow "Create new document"
    Then I fill in the following:
      | c_document_title   | My second document |
    And I fill in ckeditor field "c_document_content" with "<a href='www.chamilo.org'>Click here</a><span><strong>This is my second document!!!</strong></span>"
    And I press "c_document_save"
    Then I should see "Saved"
    And I should see "My second document.html"
    Then I follow "Info My second document.html"
    And wait the page to be loaded when ready
    Then I follow "View"
    And I should not see "<strong>"
    And I should not see "www.chamilo.org"
    And I should see "Click here"

  Scenario: Upload a document
    Given I am on "/resources/document/files?cid=1&sid=0"
    Then I follow "Upload"
    Then I attach the file "/public/favicon.ico" to "fileupload"
    And wait for the page to be loaded
    Then I should see "File upload succeeded"
    Then I am on "/resources/document/files?cid=1&sid=0"
    Then I should see "favicon.ico"

  Scenario: Delete simple document
    Given I am on "/resources/document/files?cid=1&sid=0"
    Then I follow "Info My first document.html"
    Then I should see "Created at"
    Then I follow "Delete"
    Then I should see "Deleted"
    And I should not see "My first document.html"
