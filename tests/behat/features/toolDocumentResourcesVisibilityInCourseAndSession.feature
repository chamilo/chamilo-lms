Feature: Visibility of folders and documents in the base course and access in a session context.
  The visibility in the course imply a reaction in the session context. 
  A resource set to visible in the course should be visible in the session context and can be set to invisible in this context without afecting the base course.
  A resource set to invisible in the course should not be present in the session context with default configuration.
  A folder set to invisible should not be seen in a session context, but a document inside this folder should be accessible by students.
  
  ## The tests below are not tested yet because the visibility funcionnality is not implemented yet.
  ## Once implemented It can be uncommented. 
  ## The lines with double "#" at the begining are to be revised because the behat statement is not correct.
#
#  Background:
      
#  @javascript
#  Scenario: Create session visibility test
#    Given I am a platform administrator
#    And I am on "/main/session/session_add.php"
#    When I fill in the following:
#      | name | SessionVisibilityTest |
#    And I fill in select2 input "#coach_username" with id "1" and value "admin"
#    And I press "submit"
#    Then I should see "Add courses to this session (SessionVisibilityTest)"
#    Then I fill in ajax select2 input "#courses" with id "1" and value "TEMP"
#    And I press "submit"
#    And wait very long for the page to be loaded
#    Then I should see "Update successful"
#    And I should see "Subscribe users to this session"
#    Then I fill in ajax select2 input "#users" with id "15" and value "fapple"
#    And I press "submit"
#    And wait very long for the page to be loaded
#    Then I should see "SessionVisibilityTest"
#    Then I should see "TEMP"
#    Then I should see "fapple"

#  Scenario: Document visible in course and in session
#    Given I am on course "TEMP" homepage
#    And I am a platform administrator
#    Then I follow "Document"
#    And wait the page to be loaded when ready
#    Then I press "New document"
#    And wait for the page to be loaded
#    Then I fill in the following:
#      | Title   | Visibility check document |
#    And I fill in tinymce field "item_content" with "This is my test document!"
#    And I press "Submit"
#    And wait for the page to be loaded
#    Then I should see "created"
#    And I should see "Visibility check document"
#    And wait for the page to be loaded
##    Then I should see "mdi-eye" for the eye on the line of the document "Visibility check document"
##    Then I am on "/course/1/home?sid=1&gid=0"
#    Then I follow "Document"
#    Then I should see "Visibility check document"

#  Scenario: Document invisible in course and in session
#    Given I am a platform administrator
#    And I am on course "TEMP" homepage
#    Then I follow "Document"
#    And wait the page to be loaded when ready
#    Then I should see "Visibility check document"
#    And the eye next to the document "Visibility check document" is "mdi-eye"
#    And I click on the eye on the line of the document "Visibility check document"
##    Then I should see "mdi-eye-off" for the eye on the line of the document "Visibility check document"
##    Then I am on "/course/1/home?sid=1&gid=0"
#    Then I follow "Document"
#    And wait the page to be loaded when ready
#    Then I should not see "Visibility check document"

#  Scenario: Document visible in course and modifiable in session
#    Given I am a platform administrator
#    And I am on course "TEMP" homepage
#    Then I follow "Document"
#    And wait the page to be loaded when ready
#    Then I should see "Visibility check document"
#    And I click on the eye on the line of the document "Visibility check document"
#    And wait the page to be loaded when ready
##    And the eye next to the document "Visibility check document" is "mdi-eye"
##    Then I am on "/course/1/home?sid=1&gid=0"
#    Then I follow "Document"
#    And wait the page to be loaded when ready
#    Then I should see "Visibility check document"
##    and I click on the eye on the line of the document "Visibility check document"
#    And wait the page to be loaded when ready
##    Then I should see "mdi-eye-off" for the eye on the line of the document "Visibility check document"
#    Then I am on course "TEMP" homepage
#    And I follow "Document"
#    And wait the page to be loaded when ready
#    Then I should see "Visibility check document"
##    And the eye next to the document "Visibility check document" is "mdi-eye"


#  Scenario: Document visible inside a invisible folder should be accessible by student in the base course and in the session context
#    Given I am a platform administrator
#    And I am on course "TEMP" homepage
#    Then I follow "Document"
#    And wait the page to be loaded when ready
#    Then I should see "New folder"
#    Then I press "New folder"
#    Then I fill in the following:
#      | title | Visibility testing folder |
#    And I press "Save"
#    And wait for the page to be loaded
#    Then I should see "saved"
##    and I click on the eye on the line of the folder "Visibility testing folder"
#    And wait the page to be loaded when ready
##    Then I should see "mdi-eye-off" for the eye on the line of the folder "Visibility testing folder"
#    Then I follow "Visibility testing folder"
#    And wait the page to be loaded when ready
#    Then I press "New document"
#    And wait for the page to be loaded
#    Then I fill in the following:
#      | Title   | Visibility check document in folder |
#    And I fill in tinymce field "item_content" with "This is my test document!"
#    And I press "Submit"
#    And wait for the page to be loaded
#    Then I should see "created"
#    And I should see "Visibility check document in folder"
#    Then I am on "/main/admin/user_list.php"
##    And I search for "fapple"
#    And wait for the page to be loaded
#    Then I should see "Apple Maggart"
#    And I follow "Login as"
#    And wait for the page to be loaded
#    And I should see "Login successful"
#    And I should see "Attempting to login as Fiona Apple Maggar"
##    Then I am on "/course/1/home?sid=1&gid=0"
#    Then I follow "Document"
#    Then I should not see "Visibility testing folder"
##    Then I am on the document "Visibility check document in folder"
#    And I should see "This is my test document!"
