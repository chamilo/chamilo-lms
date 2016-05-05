Feature: Forum tool
  In order to use the Forum tool
  The teachers should be able to create forum categories, forums, forum threads

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create a forum category
    Given I am on "/main/forum/index.php?action=add&content=forumcategory"
    When I fill in the following:
      | forum_category_title   | Forum Category Test                       |
      | forum_category_comment | This is the first forum category for test |
    And I press "SubmitForumCategory"
    Then I should see "The forum category has been added"

  Scenario: Create a forum
    Given I am on "/main/forum/index.php?action=add&content=forum"
    When I fill in the following:
      | forum_title   | Forum Test                       |
      | forum_comment | This is the first forum for test |
    And I press "SubmitForum"
    Then I should see "The forum has been added"

  Scenario: Create a forum thread
    Given I am on "/main/forum/newthread.php?forum=1"
    When I fill in the following:
      | post_title | Thread One                                     |
      | post_text  | This is a the first thread in a forum for test |
    And I press "SubmitPost"
    Then I should see "The new thread has been added"

  Scenario: Reply to forum message
    Given I am on "/main/forum/reply.php?forum=1&thread=1&post=1&action=replymessage"
    When I fill in the following:
      | post_text | This is a reply to the first message for test |
    And I press "SubmitPost"
    Then I should see "The reply has been added"

  Scenario: Delete a forum message
    Given I am on "/main/forum/viewthread.php?forum=1&thread=1&action=delete&content=post&id=2"
    Then I should see "Post has been deleted"

  Scenario: Quote a forum message
    Given I am on "/main/forum/reply.php?forum=1&thread=1&post=1&action=quote"
    When I press "SubmitPost"
    Then I should see "The reply has been added"