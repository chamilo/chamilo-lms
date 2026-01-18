Feature: Forum tool
  In order to use the Forum tool
  The teachers should be able to create forum categories, forums, forum threads

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create a forum category
    Given I am on "/main/forum/index.php?action=add_category&cid=1"
    And I wait for the page to be loaded
    When I fill in the following:
      | forum_category_title   | Forum Category Test |
    And I fill in editor field "forum_category_comment" with "This is the first forum category for test"
    And I press "SubmitForumCategory"
    And wait for the page to be loaded
    And I should see "Forum Category Test"
    Then I should not see an error

  Scenario: Create a forum
    Given I am on "/main/forum/index.php?action=add_forum&cid=1"
    And I wait for the page to be loaded
    When I fill in the following:
      | forum_title   | Forum Test |
    And I fill in editor field "forum_comment" with "This is the first forum for test"
    And I press "SubmitForum"
    And wait very long for the page to be loaded
    Then I should see "Forum Test"
    And I should not see an error

  Scenario: Create a forum thread
    Given I am on "/main/forum/index.php?cid=1"
    And I wait for the page to be loaded
    And I follow "Forum Test"
    And I wait for the page to be loaded
    And I click the "i.mdi-format-quote-open" element
    And wait for the page to be loaded
    When I fill in the following:
      | post_title | Thread One |
    And I fill in editor field "post_text" with "This is a the first thread in a forum for test"
    And I press "SubmitPost"
    And wait for the page to be loaded
    Then I should see "Thread One"
    And I should not see an error

  Scenario: Reply to forum message
    Given I am on "/main/forum/index.php?cid=1"
    And I wait for the page to be loaded
    And I follow "Forum Test"
    And I wait for the page to be loaded
    When I follow "Thread One"
    And I wait for the page to be loaded
    When I click the "i.mdi-comment-arrow-right-outline" element
    And I wait for the page to be loaded
    And I fill in the following:
      | post_title | Reply |
    And I fill in editor field "post_text" with "This is a reply to the first message for test"
    And I press "SubmitPost"
    And wait for the page to be loaded
    Then I should see "Reply"
    Then I should not see an error

  Scenario: Delete a forum thread
    Given I am on "/main/forum/index.php?cid=1"
    And I wait for the page to be loaded
    And I follow "Forum Test"
    And I wait for the page to be loaded
    Then I follow "Thread One"
    And I wait for the page to be loaded
    Then I click the "i.mdi-delete" element
    And I confirm the popup
    And wait for the page to be loaded
    Then I should not see an error

  Scenario: Quote a forum message
    Given I am on "/main/forum/viewthread.php?forum=1&thread=1&cid=1"
    And I wait for the page to be loaded
    When I click the "i.mdi-comment-quote" element
    And I wait for the page to be loaded
    And I press "SubmitPost"
    And wait for the page to be loaded
    Then I should see "Quoting"

