# Ported from tests/behat/features/toolDropbox.feature — rewritten, not
# verbatim. The Dropbox tool has been migrated to Vue
# (DropboxListReceived.vue/DropboxCreate.vue, /resources/dropbox/...) since
# the original scenario was written: "i.mdi-upload" no longer matches
# anything on the list page (confirmed live) — "Share a new file" is now a
# router-link itself (BaseButton with a `:to` prop), so following its own
# visible text navigates straight to the upload page, where a real "Upload"
# button/heading exists. Drops "I zoom out to maximum" — see
# adminHealthBlock.feature's header comment for why. Verified live
# end-to-end (screenshot): following "Dropbox" then "Share a new file" lands
# on a page titled "Share a new file" with an "Upload" button.
@common @tools
Feature: Dropbox tool
  In order to manage files in the course
  As a course administrator
  I want to open the Dropbox tool and access the upload dialog

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Admin opens Dropbox and sees the upload action
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Dropbox"
    And I wait for the page to be loaded
    Then I should see "Share a new file"
    And I follow "Share a new file"
    And I wait for the page to be loaded
    Then I should see "Upload"
