Feature: Chat tool
  In order to communicate with other users
  Teachers and students should be able to send public and private chat messages


  Scenario: Admin sends public and private messages, Andrea checks them
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I zoom out to maximum
    Then I follow "Chat"
    And wait for the page to be loaded
   # Send a public message as admin
    Then I fill in the following:
      | chat-writer | I am USER1 |
    Then I click the "button#chat-send-message" element
    And wait for the page to be loaded


   # Open private chat with Andrea and send a private message
    Then I click the "button#andrea_costea_chat" element
    And wait for the page to be loaded
    Then I fill in the following:
      | chat-writer | HelloAndrea |
    Then I click the "button#chat-send-message" element
    And wait for the page to be loaded


  Scenario: Now switch to Andrea (student) and verify messages
    Given I am a student
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I zoom out to maximum
    Then I follow "Chat"
    And wait for the page to be loaded
    Then I should see "USER1"
    Then I should not see "Hello"


   # Click on another user (john_doe) and assert a message
    Then I click the "button#john_doe_chat" element
    And wait for the page to be loaded
    Then I should see "Hello"
