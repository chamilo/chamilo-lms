Feature: Document tool
  In order to use the document tool
  The teachers should be able to create and upload files

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create a folder
    Then I follow "document"
    And wait the page to be loaded when ready
    Then I should see "New folder"
    Then I press "New folder"
    Then I fill in the following:
      | item_title | My new directory |
    And I press "Submit"
    And wait for the page to be loaded
    Then I should see "created"

  Scenario: Create a folder that already exists
    Then I follow "document"
    And wait the page to be loaded when ready
    Then I should see "New folder"
    Then I press "New folder"
    And I fill in the following:
      | item_title | My new directory |
    And I press "Submit"
    And wait for the page to be loaded
    Then I should see "created"

  Scenario: Create a text document
    Then I follow "document"
    Then I press "New document"
    And wait for the page to be loaded
    Then I fill in the following:
      | item_title   | My first document |
    And I fill in tinymce field "item_content" with "This is my first document!"
    And I press "Submit"
    And wait for the page to be loaded
    Then I should see "created"
    And I should see "My first document"
    And wait for the page to be loaded

#    Then I follow "View"
#    Then I should see "This is my first document"

  Scenario: Create a HTML document
    Then I follow "document"
    Then I press "New document"
    And wait for the page to be loaded
    Then I fill in the following:
      | item_title   | My second document |
    And I fill in tinymce field "item_content" with "<a href='www.chamilo.org'>Click here</a><span><b>This is my second document!!</b></span>"
    And I press "Submit"
    And wait for the page to be loaded
    Then I should see "created"
    And I should see "My second document"

#    Then I follow "Info My second document.html"
#    And wait the page to be loaded when ready
#    Then I follow "View"
#    And I should not see "<strong>"
#    And I should not see "www.chamilo.org"

  Scenario: Upload a document
    Then I follow "document"
    Then I press "File upload"
    And wait for the page to be loaded
    Then I attach the file "/public/favicon.ico" to "file_upload"
    And I press "Submit"
    And wait for the page to be loaded
    Then I should see "created"
    Then I move backward one page
    Then I should see "favicon.ico"

  Scenario: Search for "My second document" and edit it
    Then I follow "document"
    Then I press "Search"
    And wait for the page to be loaded
    Then I fill in the following:
      | search_filter | My second document |
    Then I press "Filter"
    And wait for the page to be loaded
    Then I should not see "My first document"
    Then I press "Info"
    Then I should see "My second document"
    Then I press "Edit"
    And wait for the page to be loaded
    Then I fill in the following:
      | item_title | My second document edited |
    Then I press "Submit"
    And wait very long for the page to be loaded
    Then I should see "updated"
    Then move backward one page
    And I should see "My second document edited"



#  Scenario: Delete simple document
#    Then I follow "document"
#    Then I press "File upload"
#    And wait for the page to be loaded
#    Then I follow "My first document"
#    Then I should see "Created at"
#    Then I follow "Delete"
#    Then I should see "Deleted"
#    And I should not see "My first document.html"
