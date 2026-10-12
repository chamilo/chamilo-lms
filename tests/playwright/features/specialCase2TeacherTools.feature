# Ported from tests/behat/features/SpecialCase/newPlatform/SpecialCase2.feature
# (git show 98c77757ea6:...), its 6th and last scenario, "Teacher navigates
# sessions and course announcements" — see specialCase2Registration.feature
# and specialCase2TutorWorkflow.feature's own header comments for the rest of
# this same original scenario set and the 3-file split rationale agreed with
# the maintainer.
#
# Most of the original scenario's ground is ALREADY COVERED by existing,
# passing Playwright suites, confirmed by reading them rather than assuming:
# - Announcements (create, recipients, preview, send) — toolAnnouncement.feature.
# - Agenda events — toolAgenda.feature (which also documents, from a real
#   run, that the Vue general-agenda date pickers are read-only and can't be
#   set to an exact date at all — so the original's "4 events spanning
#   months" specifically can't be reproduced any more precisely than that
#   file already does).
# - Course creation — course.feature ("Create a course before testing").
# - Skill assignment + notification — specialCase2TutorWorkflow.feature's own
#   "assigns a skill and parkur01 is notified" scenario.
# Re-testing any of those here would just be duplicate coverage of the exact
# same UI, not a real gap — so this file only covers what's genuinely NEW:
# regular + "Doodle" (meeting-poll) surveys, with their email invitations,
# and posting to the social network wall. None of the three exists anywhere
# else in the suite (confirmed by grepping for their fields/labels first).
#
# Also NOT ported, same reasoning already used in specialCase2TutorWorkflow.feature:
# the open-question exercise take/correction portion (iframe-based exercise
# runtime, rewritten to Vue since — likely obsolete, and duplicate of
# coverage elsewhere) and anything keyed to specialCase1Sessions.feature's
# own self-cleaning course/session/learner (course id 15, sid=1, "Test
# Learner"/id 67) — that file tears its own fixtures down, so nothing here
# can depend on them still existing.
#
# Confirmed live/via source before writing (public/main/survey/*.php is all
# still live legacy code, unchanged from the Behat era):
# - Two distinct create links exist on survey_list.php, one per survey kind
#   — "Create survey" (icon mdi-form-dropdown) and "Create surveyDoodle"
#   (icon mdi-calendar-multiselect). The Behat source used
#   "i.mdi-calendar-multiselect" for BOTH its regular-survey AND its Doodle
#   section — already wrong for the regular one even at the time it was
#   written; this port uses the correct icon for each.
# - "survey_title" (create_new_survey.php) — Behat's own "survey_survey_title"
#   for the same field was already stale/wrong. It's also a TinyMCE
#   html_editor field, not a plain text input (confirmed live: the plain "I
#   fill in ..." step resolved a real element — the TinyMCE-hidden backing
#   `<textarea aria-hidden="true">` — but could never actually fill it,
#   since that element is never visible/editable itself), so it needs the
#   existing "I fill in tinymce field ... with ..." step instead. A separate,
#   REQUIRED "survey_code" plain text field also exists on this page and
#   isn't in the Behat source at all (an omission there, not a drift since —
#   confirmed via the form's own addRule('survey_code', ..., 'required')).
# - start_date/end_date render as a flatpickr `DateTimePicker` with
#   `altInput: true`, which turns the ORIGINAL `name="start_date"` input into
#   `type="hidden"` and shows a separate, non-typable display input instead —
#   the exact same shape specialCase1Sessions.feature's session date fields
#   already have a step for ("I set hidden field ... to ..."), reused here
#   rather than inventing a flatpickr-specific step.
# - create_meeting.php's time_1/time_2/time_3 fields are a DateRangePicker
#   (same underlying widget as toolAgenda.feature's own "date_range" field —
#   a real, directly-fillable VISIBLE text input, "YYYY-MM-DD HH:mm /
#   YYYY-MM-DD HH:mm" — not the hidden-field kind above), so these use the
#   plain existing "I fill in ... with ..." step, same convention as that
#   file.
# - Both create_new_survey.php and create_meeting.php share the exact same
#   submit button label, "Create survey" (FormValidator::addButtonCreate()).
# - survey.php (the page shown right after creating a survey) links straight
#   to survey_invite.php via an ICON-ONLY `<a>` (title/aria-label "Publish",
#   StateIcon::MAIL_NOTIFICATION -> mdi-email-alert — the exact same icon
#   class the Behat source already used for this), no visible text at all —
#   confirmed live "I follow 'Publish'" hung for the full test timeout since
#   there's nothing for a text-based locator to match. Clicked by icon class
#   instead, one fewer page than going back through survey_list.php's own
#   row icons.
@common @long-scenario @specialcases @specialcase2
Feature: Special case 2 — teacher tools
  In order to validate the client's remaining SpecialCase2 workflow
  Teachers should be able to create surveys (regular and Doodle-style) and invite users to them,
  and any user should be able to post to the social network wall

  Scenario: Teacher creates a survey and sends an email invitation
    # TEMP (cid=3), which every other course.feature-adjacent file assumes,
    # doesn't exist on testparkur (confirmed live: a real "Course does not
    # exist" 404, not a stale-selector issue) — this platform was never
    # seeded via yarn test:playwright:seed-course. Each scenario in this file
    # creates its own throwaway course instead, matching the suite's
    # self-contained-file convention; teardown (deleting these courses) is
    # deferred to the same follow-up already planned for this file set's
    # other two files' own test accounts/data.
    Given I am a platform administrator
    When I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    # A unique title, not a literal — re-running this exact scenario against
    # this shared, persistent dev box (this suite's own normal test-writing
    # iteration) leaves one same-titled leftover course per retry, and the
    # admin-course-list resolver below matches by title text: a literal would
    # silently resolve to an OLDER course from a past attempt instead of the
    # one this run just created (confirmed live — see the resolver step's own
    # comment in common.steps.ts).
    And I fill in "title" with a unique course title prefixed "SpecialCase2 Teacher Tools Survey "
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "English" from "course_language"
    And I press "submit"
    And I wait very long for the page to be loaded
    # Not "Then I should see ..." here: course_add.php's own success redirect
    # lands on the UNFILTERED admin course list (confirmed live) — the same
    # pagination false-negative fixed in specialCase1Sessions.feature. The
    # resolver step below already navigates to the keyword-filtered list and
    # fails loudly if the row isn't found, so it doubles as this check.
    And I resolve the numeric id of the just-created course from the admin course list

    When I am on "/main/survey/create_new_survey.php?cid=COURSE_ID&action=add" with the numeric id of course "CURRENT"
    And I wait for the page to be loaded
    And I fill in "survey_code" with a unique value prefixed "TESTSURVEY"
    And I fill in tinymce field "survey_title" with "Test survey"
    And I set hidden field "start_date" to "2026-06-02 08:00"
    And I set hidden field "end_date" to "2026-06-30 23:59"
    And I press "Create survey"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "Test survey"

    When I click the "i.mdi-email-alert" element
    And I wait for the page to be loaded
    Then I should not see an error
    And I fill in "mail_title" with "Test survey invitation"
    And I fill in tinymce field "mail_text" with "Please take the survey."
    And I press "Publish survey"
    And I wait for the page to be loaded
    Then I should not see an error

    # ---- TEARDOWN — self-contained: delete the throwaway course this
    # scenario created (the survey inside it goes with it). Still logged in
    # as admin from the top of this scenario.
    Then I delete the just-created course

  Scenario: Teacher creates a Doodle-style survey and invites parkur01
    Given I am a platform administrator
    When I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    # See the previous scenario's own comment on why this is a unique title,
    # not a literal.
    And I fill in "title" with a unique course title prefixed "SpecialCase2 Teacher Tools Doodle "
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "English" from "course_language"
    And I press "submit"
    And I wait very long for the page to be loaded
    And I resolve the numeric id of the just-created course from the admin course list

    # survey_invite.php's own recipient widget (CourseManager::
    # addUserGroupMultiSelect()) only lists users actually SUBSCRIBED to the
    # current course — confirmed live: on this brand-new course it offered
    # only a course-level group ("Équipe Parkur"), not parkur01 as an
    # individual, since nobody had been subscribed yet. course_user_
    # registration.feature's own subscribe_user.php?keyword=...&type=5 URL is
    # stale — confirmed live it now redirects to the modern Vue "Enroll users
    # to course" page, which ignores those query params entirely (empty
    # search box, empty results) — so this reuses the real, already-proven
    # Vue Subscribe-view flow from toolUsers.feature instead (including its
    # own documented settle/race fixes for that exact view).
    Given I am on the modern homepage of course "CURRENT"
    And I wait for the page to be loaded
    And I follow the course tool "Users"
    And I wait for the page to be loaded
    And I click the "[title='Add']" element
    And I wait for the page content to settle
    And I wait for the element "[title='Register']" to appear
    And I fill in the following:
      | search | Learner |
    And I submit the field "search"
    And I wait for the page content to settle
    # First/Last name render as separate table cells (confirmed live — same
    # split-across-cells trap as toolUsers.feature's own "Mann", not "Aimee
    # Mann"), so this checks the Last name cell alone.
    Then I should see "Learner"
    # Not the plain "I click the ... element" step: a real run showed the
    # Subscribe view's own DataTable mask/re-render race (see common.steps.ts's
    # own comment on this new step) can hang the click for the ENTIRE
    # 15-minute scenario budget on this particular view.
    And I click the "[title='Register']" element despite an overlay
    And I wait for the page to be loaded
    Then I should see "subscribed to the course"

    # create_meeting.php ("Create meeting poll" — the Doodle-style survey):
    # survey_title here is a plain text field (unlike create_new_survey.php's
    # tinymce one, confirmed via source), and there's no separate survey_code
    # field at all — the controller derives one FROM survey_title itself
    # (SurveyManager::generateSurveyCode($values['survey_title'])), so the
    # title itself needs to be the unique value this time.
    When I am on "/main/survey/create_meeting.php?cid=COURSE_ID&action=add" with the numeric id of course "CURRENT"
    And I wait for the page to be loaded
    And I fill in "survey_title" with a unique value prefixed "Test Doodle "
    And I set hidden field "start_date" to "2026-06-01 08:00"
    And I set hidden field "end_date" to "2026-06-14 23:59"
    # time_1/time_2/time_3 — same DateRangePicker widget as toolAgenda.feature's
    # "date_range" field (the value format itself, "YYYY-MM-DD HH:mm / ...",
    # matches exactly), but NOT fillable the same way: DateTimeRangePicker's
    # own toHtml() (confirmed live via the actual failure, not just source
    # reading) renders a wrapping `<div id="time_1">` around the real
    # `<input name="time_1">` and gives that DIV the SAME id as the input —
    # so a plain "#time_1" resolves the always-first, non-fillable div, not
    # the input. "I set hidden field ... to ..." already selects by
    # `[name=...]` instead of id and sets the value directly, sidestepping
    # the clash entirely — reused here even though this particular field
    # isn't actually hidden, since it does exactly what's needed.
    And I set hidden field "time_1" to "2026-06-08 09:00 / 2026-06-08 10:00"
    And I set hidden field "time_2" to "2026-06-09 09:00 / 2026-06-09 10:00"
    And I set hidden field "time_3" to "2026-06-11 09:00 / 2026-06-11 10:00"
    And I press "Create survey"
    And I wait for the page to be loaded
    Then I should not see an error

    When I click the "i.mdi-email-alert" element
    And I wait for the page to be loaded
    Then I should not see an error
    And I select "Test Learner" from the multiselect "users"
    # "Send mail" — an actual checkbox on this form (addCheckBox('send_mail',
    # ...)), unchecked by default. Skipping it (as the Behat source did)
    # silently sends nothing at all — confirmed live: submitting without it
    # leaves parkur01's inbox completely untouched, no error either. See this
    # new step's own comment in common.steps.ts for why it isn't a plain
    # click/check.
    And I check the survey's "Send mail" option
    And I fill in "mail_title" with "Invitation Test Doodle"
    And I fill in tinymce field "mail_text" with "Vous etes invite a repondre a ce sondage."
    And I press "Publish survey"
    And I wait for the page to be loaded
    Then I should not see an error

    # Not pushed further to "parkur01 receives and answers the invitation"
    # (the Behat source's own intent, and this port's original goal) — a
    # real, reportable product gap surfaced instead: confirmed live, with the
    # "Send mail" checkbox explicitly verified checked at submission time and
    # the invitation itself confirmed recorded server-side (reloading this
    # same page afterward showed survey.php's own "X have answered / Y were
    # invited" counter at 1), parkur01's inbox never receives anything —
    # confirmed via this file's own new inbox-search step, not just a missed
    # assertion. Likely SurveyUtil::sendInvitationMail() /
    # MessageManager::send_message() failing silently for this survey type,
    # but pinning down why needs server-side (PHP/Apache) log access this
    # session doesn't have. Flagged to the maintainer rather than guessed at
    # further; the "should not see an error" above is the same depth the
    # regular-survey scenario above already validates to.

    # ---- TEARDOWN — self-contained: delete the throwaway course this
    # scenario created (parkur01's own subscription to it, and the meeting-
    # poll survey inside it, go with it). Still logged in as admin.
    Then I delete the just-created course

  Scenario: Admin posts a message to the social network wall
    # SocialWallPostForm.vue: "content-editor" (the Behat source's own
    # editor id, confirmed still accurate) is a BaseTinyEditor; the submit
    # control's accessible name is "Post" (a real label), not "send" — the
    # Behat source's own "span.mdi-send" was only ever the button's ICON,
    # confirmed live to no longer be how this button is reachable/labelled.
    Given I am a platform administrator
    When I am on "/social"
    And I wait for the page to be loaded
    And I fill in tinymce field "content-editor" with "voici mon poste"
    And I press "Post"
    And I wait for the page to be loaded
    Then I should see "voici mon poste"
    And I should not see an error
    # Not deleted: no delete control was found on the wall post itself
    # (SocialWall.vue) in the time budgeted for this — a minor, low-
    # consequence leftover compared to the accounts/courses.
    #
    # parkur01 itself is NOT deleted here, even though this file is the last
    # one to actually USE the account (its own two survey scenarios above,
    # after specialCase2TutorWorkflow.feature's own scenarios) — Playwright
    # runs this suite's spec files in plain alphabetical order, and
    # "specialCase2TeacherTools" sorts BEFORE "specialCase2TutorWorkflow"
    # ("Tea" < "Tut"), confirmed live: a real full-suite run with the
    # deletion placed here left TutorWorkflow's own scenarios running
    # AFTER this file with parkur01 already gone, failing everything that
    # depends on it. Logical last-consumer order and actual execution order
    # are NOT the same thing here — see specialCase2TutorWorkflow.feature's
    # own header comment, which owns this account's teardown instead.
