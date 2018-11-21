Feature: Document tool
  In order to use the document tool
  The teachers should be able to create and upload files

  Background:
    Given I am a platform administrator

  Scenario: Create a folder
    Given I am on "/main/document/document.php?cidReq=TEMP&createdir=1"
    Then I should see "Create folder"
    And I fill in the following:
      | dirname | My new directory |
    And I press "Create the folder"
    Then I should see "Folder created"

  Scenario: Create a folder that already exists
    Given I am on "/main/document/document.php?cidReq=TEMP&createdir=1"
    Then I should see "Create folder"
    And I fill in the following:
      | dirname | My new directory |
    And I press "Create the folder"
    Then I should see "Unable to create the folder"

  Scenario: Create a simple document
    Given I am on "/main/document/create_document.php?cidReq=TEMP"
    Then I should see "Create a rich media page / activity"
    Then I fill in the following:
      | create_document_title   | My first document                       |
    And I fill in ckeditor field "content" with "This is my first document!!!"
    And I press "Create a rich media page / activity"
    Then I should see "Item added"
    And I should see "My first document"
    Then I follow "My first document"
    And wait for the page to be loaded
    Then I should see "My first document"

  Scenario: Create a HTML document
    Given I am on "/main/document/create_document.php?cidReq=TEMP"
    Then I should see "Create a rich media page / activity"
    Then I fill in the following:
      | create_document_title   | My second document                       |
    And I fill in ckeditor field "content" with "<a href='www.chamilo.org'>Click here</a><span><strong>This is my second document!!!</strong></span>"
    And I press "Create a rich media page / activity"
    Then I should see "Item added"
    And I should see "My second document"
    Then I follow "My second document"
    And wait for the page to be loaded
    Then I should see "My second document"
    And I should not see "<strong>"
    And I should not see "www.chamilo.org"

  Scenario: Upload a document
    Given I am on "/main/document/upload.php?cidReq=TEMP"
    Then I should see "Upload documents"
    Then I follow "Upload (Simple)"
    Then I attach the file "web/css/base.css" to "file"
    When I press "Upload file"
    And wait for the page to be loaded
    Then I should see "File upload succeeded"

#  Scenario: Create cloud link
#    Given I am on "/main/document/add_link.php?cidReq=TEMP"
#    Then I should see "Add a link"
#    And I fill in the following:
#      | name | My dropbox link |
#      | url | http://dropbox.com/sh/loremipsum/loremipsum?dl=0 |
#    And I press "Add link to Cloud file"
#    Then I should see "Cloud link added"
