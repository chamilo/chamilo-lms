# #1407: a teacher can follow an assignment to be e-mailed when a learner comments
# on one of its submissions. The creator follows a new assignment automatically and
# can unfollow it; learners never see the toggle. The e-mails themselves (who gets
# them, the course-wide master switch) are covered by PHPUnit
# (AssignmentFeedbackNotifierTest, StudentPublicationFollowTest), not here.
# The toggle is a BaseButton (data-testid="assignment-follow-toggle") labelled
# "Following" with a bell icon, or "Follow" with a crossed-out bell. "Follow" alone
# is a substring of the assignment's own title, so the state is asserted on the
# icon (and on the absence of "Following"), never on "I should see 'Follow'".
Feature: Follow an assignment
  In order to get e-mails only about the assignments I care about
  As a teacher
  I want to follow or unfollow an assignment

  Scenario: The creator of an assignment follows it automatically
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait for the page to be loaded when ready
    Then I press "Create Assignment"
    And wait for the page to be loaded
    When I fill in the following:
      | Assignment name | Follow Work Test |
    And I fill in the active tinymce editor with "Follow work description"
    And I press "Save"
    And wait for the page to be loaded when ready
    Then I should see "Assignment created"
    When I follow "Follow Work Test"
    And I wait for the element "[data-testid='assignment-follow-toggle']" to appear
    Then I should see the "[data-testid='assignment-follow-toggle'] .mdi-bell" element
    And I should see "Following"

  Scenario: The creator unfollows and follows the assignment again
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait for the page to be loaded when ready
    And I follow "Follow Work Test"
    And I wait for the element "[data-testid='assignment-follow-toggle']" to appear
    Then I should see the "[data-testid='assignment-follow-toggle'] .mdi-bell" element
    When I press "Following"
    And wait for the page to be loaded when ready
    Then I should see the "[data-testid='assignment-follow-toggle'] .mdi-bell-off-outline" element
    And I should not see "Following"
    # The choice is stored, not just a local toggle.
    When I reload the current page
    And I wait for the element "[data-testid='assignment-follow-toggle']" to appear
    Then I should see the "[data-testid='assignment-follow-toggle'] .mdi-bell-off-outline" element
    And I should not see "Following"
    When I press "Follow"
    And wait for the page to be loaded when ready
    Then I should see the "[data-testid='assignment-follow-toggle'] .mdi-bell" element
    And I should see "Following"

  Scenario: A learner does not get the follow toggle
    Given I am not logged
    Given I am a student
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait for the page to be loaded when ready
    And I follow "Follow Work Test"
    And wait for the page to be loaded when ready
    Then I should see "Follow work description"
    And I should not see the "[data-testid='assignment-follow-toggle']" element
    And I should not see "Following"
    And I should not see an error

  Scenario: Delete the assignment used to test following
    Given I am not logged
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait for the page to be loaded when ready
    Then I should see "Follow Work Test"
    And I click the "input.p-checkbox-input" icon in the row for "Follow Work Test"
    And I press "Delete selected"
    And I press "Yes"
    And wait for the page to be loaded when ready
    Then I should not see "Follow Work Test"
    And I should not see an error
