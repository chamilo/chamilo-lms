# Ported from tests/behat/features/socialGroup.feature.
#
# - group_add.php is still the live legacy page; "description" is a TinyMCE
#   field bound via window.setContentFromEditor (same helper career.feature
#   uses), handled by the existing "I fill in editor field ... with ..." step.
# - "Invite a friend to group" originally hardcoded social group id "1",
#   same category of assumption as the documented cid=3 policy — but a REAL
#   CI run proved this one false even on a genuinely fresh install: unlike
#   cid=3/TEMP (which gets its own dedicated, sequential "Seed test course"
#   CI step specifically so no other file can race it), usergroup rows have
#   no such protection, and class.feature (a DIFFERENT file, running
#   concurrently in a different worker) also inserts rows into that same
#   `usergroup` table — group_type differs (SOCIAL_CLASS vs NORMAL_CLASS) but
#   the id sequence is shared. A real CI trace confirmed it: class.feature's
#   own class won id 1 that run, so group_invitation.php?id=1 302-redirected
#   admin to Home ("not a group member") instead of showing the invite form.
#   Fixed by capturing the REAL id from group_add.php's own success redirect
#   (`group_view.php?id=<id>`, confirmed in its source) via the new
#   "I remember the created group id" step, instead of assuming any
#   particular number.
# - The original's hardcoded friend id ("11", then "10" after recounting the
#   seed fixture order) was wrong BOTH times, confirmed by two separate real
#   CI runs each reporting a different missing value. Unlike the group id
#   (which has no dedicated ordered seeding step), user seeding IS ordered
#   and dedicated — but evidently not stable enough in practice to hardcode
#   a numeric id against (other CI steps/fixtures can still shift it). Fixed
#   for good by not guessing at all: looks up fbaggins's real id at runtime
#   via message.ajax.php's own working "find_users" action (same endpoint
#   the real "New message" recipient search uses) and remembers it, the same
#   pattern already used for the created group's id above.
# - The three commented-out scenarios in the original (accept/deny invitation,
#   delete member) are dropped, not ported — they were already inert in the
#   original Behat suite too.
# - Also found on the same real CI run: FormValidator::addMultiSelect(
#   'invitation', ...) does NOT render a single plain <select multiple
#   name="invitation[]"> the way the original Behat step (and this port's
#   first attempt) assumed — it's a dual-listbox widget (a left "available
#   friends" <select id="invitation"> and a right "selected to invite"
#   <select id="invitation_to" name="invitation[]">, the one actually
#   submitted, empty until JS moves an option over). The invite step now
#   moves the friend's real <option> node across directly instead of trying
#   to select an option that was never there. Also, "Invitation sent" is a
#   Display::addFlash() call that does not reliably survive this legacy
#   header()+exit redirect — same already-documented limitation as
#   extraFieldUser.feature's own flash text — fixed the flash call itself
#   (Container::addFlash() + explicit session save) but, per that same
#   precedent, still asserting a durable signal instead: "Users already
#   invited" only renders once the invitation genuinely landed in the DB.
Feature: Social Group
  In order to use the Social Network
  As an administrator
  I need to be able to create a social group, invite users and post a message

  Scenario: Create a social group
    Given I am a platform administrator
    And I am on "/main/social/group_add.php"
    And I wait for the page to be loaded
    When I fill in the following:
      | title | Behat Test Group |
    Then I fill in editor field "description" with "This is a group created by Behat"
    And I press "submit"
    And wait very long for the page to be loaded
    Then I should not see an error
    And I remember the created group id

  Scenario: Invite a friend to group
    Given I am a platform administrator
    And I have a friend named "fbaggins"
    When I invite the friend to the social group I just created
    Then I should see "Users already invited"
    And I should not see an error
