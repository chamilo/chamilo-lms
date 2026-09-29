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
#
# Real CI-only failure: clicking a private-chat button calls
# switchConversation(), which triggers its own async fetch() for that
# conversation's history (chat.html.twig's polling client) — not a real page
# navigation, so "wait for the page to be loaded" (domcontentloaded only) is
# a near no-op here and gives that fetch no extra time to land before the
# very next action. Scenario 1 sends its private message immediately after
# switching to Andrea's conversation with nothing confirming the switch
# actually completed first, and scenario 2 asserts on the fetched history
# right after switching to admin's conversation the same way — on a loaded
# CI runner either race can plausibly lose. Both now use "I wait for the
# page content to settle" (bounded networkidle) right after switching
# conversations instead.
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
    And I wait for the page content to settle
    Then I fill in the following:
      | chat-writer | HelloAndrea |
    Then I click the "button#chat-send-message" element
    And wait for the page to be loaded

  # @skip 2026-08-05: failed once in real CI (concurrent-worker-load class of
  # flake being tracked across several files this session — see courseCatalogue.
  # feature/toolGroup.feature's own @skip notes). Not yet reproduced/root-caused
  # in isolation. Revisit together with the other @skip'd scenarios.
  # RE-ENABLED 2026-08-22. The @skip note kept below is preserved as history,
  # but its premise no longer holds: every one of those deferrals attributed the
  # failure to "concurrent-worker-load" / "real-CI-only" flakiness whose
  # suspected source was specialCase1PlatformSettings.feature mutating ~100
  # global platform settings (notably cookie_warning, a fixed bottom-of-viewport
  # overlay that intercepts pointer events) and its @long-scenario tests
  # starving the shared worker pool. SpecialCase1 has since been moved OUT of the
  # parallel batch into its own sequential CI step (@specialcase1 tag, see
  # package.json + playwright.yml), which removes that interference at the
  # source. Direct evidence it was real: toolAssessments.feature's five
  # NON-skipped scenarios were failing in CI before that change and pass after
  # it. Re-enabled to be judged on real results instead of staying dark.
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
    And I wait for the page content to settle
    Then I should see "Hello"
