Feature: Forum tool
  In order to use the Forum tool
  The teachers should be able to create forum categories, forums, forum threads

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create a forum category
    Given I am on "/main/forum/index.php?action=add_category&cid=1"
    When I fill in the following:
      | forum_category_title   | Forum Category Test |
    And I fill in editor field "forum_category_comment" with "This is the first forum category for test"
    And I press "SubmitForumCategory"
    Then I should see "The forum category has been added"

  Scenario: Create a forum
    Given I am on "/main/forum/index.php?action=add_forum&cid=1"
    When I fill in the following:
      | forum_title   | Forum Test |
    And I fill in editor field "forum_comment" with "This is the first forum for test"
    And I press "SubmitForum"
    Then I should see "The forum has been added"

  Scenario: Create a forum thread
    Given I am on "/main/forum/index.php?cid=1"
    And I follow "Forum Test"
    And I follow "Create thread"
    And wait for the page to be loaded
    When I fill in the following:
      | post_title | Thread One |
    And I fill in editor field "post_text" with "This is a the first thread in a forum for test"
    And I press "SubmitPost"
    And wait for the page to be loaded
    Then I should see "The new thread has been added"

  Scenario: Reply to forum message
    Given I am on "/main/forum/index.php?cid=1"
    And I follow "Forum Test"
    When I follow "Thread One"
    When I follow "Reply to this thread"
    And I fill in the following:
      | post_title | Reply |
    And I fill in editor field "post_text" with "This is a reply to the first message for test"
    And I press "SubmitPost"
    And wait for the page to be loaded
    Then I should see "The reply has been added"

  Scenario: Delete a forum thread
    Given I am on "/main/forum/index.php?cid=1"
    And I follow "Forum Test"
    Then I follow "Thread One"
    Then I follow "Delete"
    And wait for the page to be loaded
    Then I should see "Thread deleted"

# This test is commented because to quote a message is necessary load HTML code inside of textarea.
# And this breaks the page for Behat
#  Scenario: Quote a forum message
#    Given I am on "/main/forum/viewthread.php?forum=1&thread=1"
#    When I follow "quote-1"
#    And I press "SubmitPost"
#    Then I should see "The reply has been added"
