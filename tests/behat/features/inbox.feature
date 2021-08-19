#Not tested yet, needs to be verified before uncomenting
#Feature: Inbox
#  A user should be able to send and receive messages.
#
#  Background:
#
#  Scenario: Send a new message
#    Given I am a platform administrator
#    And I am on "main/messages/inbox.php"
#    When I press "Compose message"
#    Then I should see "Send to"
#    When I fill in the following:
#      | Send to | acostea |
#    And I press "Andrea Costea (acostea)"
#    Then I fill in the following:
#      | Subject | Behat test message subject |
#      | Message | Behat test message content |
#    And I press "Send message"
#    And wait for the page to be loaded
#    Then I should see "The message has been sent to"
#
#  Scenario: Verify message is in outbox
#    Given I am a platform administrator
#    And I am on "main/messages/inbox.php"
#    When I press "Outbox"
#    Then I should see "Messages / Outbox"
#    And I should see "Behat test message subject"
#
# Scenario: Delete message
#    Given I am a platform administrator
#    And I am on "main/messages/outbox.php"
#   Then I should see "Messages / Outbox"
#    And I should see "Behat test message subject"
#    When I press "Behat test message subject"
#    Then I should see "Behat test message content"
#    And I should see "Delete message"
#    When I press "Delete message"
#    And wait for the page to be loaded
#    Then I should see "The selected messages have been deleted"
#
##Scenario to be added/completed/verified/
##  scenario : delete multiple messages
##    Given I am a platform administrator
##    And I am on "main/messages/outbox.php"
##    Then I should see "Messages / Outbox"
##    When I check multiple checkbox
##    And I press the arrow next to Detail
##    And I press Delete Selected messages
##    Then I should see "The selected messages have been deleted"
#
#
#
#  Scenario: Read a message and come back to inbox
#    Given I am a student
#    And I am on "main/messages/inbox.php"
#    Then I should see "Behat test message subject"
#    When I press "Behat test message subject"
#    And wait for the page to be loaded
#    Then I should see "Behat test message content"
#    And I should see "Inbox"
#    When I press "Inbox"
#    And wait for the page to be loaded
#    Then I should see "Messages / Inbox"
#    And I should see "Behat test message subject"
#
#  Scenario: Reply to a message
#    Given I am a student
#    And I am on "main/messages/inbox.php"
#    Then I should see "Behat test message subject"
#    When I press "Behat test message subject"
#    And wait for the page to be loaded
#    Then I should see "Behat test message content"
#    When I press "Reply to this message"
#    And wait for the page to be loaded
#    Then I should see "Send to"
#    And I should see "RE: Behat test message subject"
#    When I press "Send message"
#    And wait for the page to be loaded
#    Then I should see "The message has been sent to"
#
##Scenario to be added/completed/verified/
##  Scenario: Forward a message
##    Given I am a student
##    And I am on "main/messages/inbox.php"
##    Then I should see "Behat test message subject"
##    When I press "Forward message" on the line of "Behat test message subject"
##    And wait for the page to be loaded
##    Then I should see "Send to"
##    And I should see "[Fwd: Behat test message subject]"
#
##Scenario to be added/completed/verified/
##  scenario : delete a message using the trash icon directly on the Inbox page
