# Ported from tests/behat/features/toolChat.feature — rewritten, not
# verbatim. The Chat tool is still legacy (src/CoreBundle/Resources/views/
# Chat/chat.html.twig + vanilla JS, route /resources/chat?cid=...), not
# migrated to Vue — confirmed live, no matching directory under
# assets/vue/views/. This is the course TOOL chat (the "Chat" link on the
# course homepage tools list), not the site-wide DockedChat widget.
#
# Field/button ids "chat-writer" and "chat-send-message" are unchanged from
# the original scenario and confirmed live. The per-user private-chat button
# ids are NOT: the current JS builds them from the user's own complete_name
# with spaces replaced by underscores but CASE PRESERVED
# (`rawName.replace(/\s+/g, '_')` in chat.html.twig), so the real live ids are
# "Andrea_Costea_chat" and "John_Doe_chat" — confirmed live via a real DOM
# dump — not the original's all-lowercase "andrea_costea_chat"/
# "john_doe_chat". "John Doe" is the admin fixture account's own display
# name, which is why the student scenario opens its private conversation
# with admin via "button#John_Doe_chat". Drops "I zoom out to maximum" — see
# adminHealthBlock.feature's header comment for why.
#
# Verified live end-to-end exactly as scripted below: admin's public message
# ("I am USER1") lands in the general/"All" tab, and the private message to
# Andrea ("HelloAndrea") is invisible from that same general tab (confirmed
# "Hello" absent) but appears once Andrea's own private conversation with
# admin ("John_Doe_chat") is opened — "HelloAndrea" contains "Hello" as a
# substring, matching the original scenario's own substring assertion.
@common @tools
Feature: Chat tool
  In order to communicate with other users
  Teachers and students should be able to send public and private chat messages

  Background:
    Given I am a platform administrator

  Scenario: Admin sends public and private messages, Andrea checks them
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    Then I follow "Chat"
    And wait for the page to be loaded
    # Send a public message as admin
    Then I fill in the following:
      | chat-writer | I am USER1 |
    Then I click the "button#chat-send-message" element
    And wait for the page to be loaded

    # Open private chat with Andrea and send a private message
    Then I click the "button#Andrea_Costea_chat" element
    And wait for the page to be loaded
    Then I fill in the following:
      | chat-writer | HelloAndrea |
    Then I click the "button#chat-send-message" element
    And wait for the page to be loaded

  Scenario: Now switch to Andrea (student) and verify messages
    Given I am a student
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    Then I follow "Chat"
    And wait for the page to be loaded
    Then I should see "USER1"
    Then I should not see "Hello"

    # Click on admin's own chat entry (fixture display name "John Doe") and assert the private message
    Then I click the "button#John_Doe_chat" element
    And wait for the page to be loaded
    Then I should see "Hello"
