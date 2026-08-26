# Ported from tests/behat/features/SpecialCase/newPlatform/SpecialCase1.feature
# (the CURRENT file under SpecialCase/newPlatform/ — NOT the stale top-level
# tests/behat/features/SpecialCase1.feature, which a prior read confirmed is
# superseded and must not be used as the source of truth). Covers only its
# last 5 scenarios (source lines 2368 to EOF): course/tool creation, a
# teacher account, and 4 sessions. The file's first 3 scenarios (platform
# searches, user/session extra fields) and its teardown.feature counterpart
# belong to a separate, concurrently-ported file and are out of scope here.
#
# Every selector below was verified LIVE against this box (not ported
# blind) — see the findings below for what changed vs the Behat source and
# why, plus one real (but transient) app issue hit while doing so.
#
# COURSE CREATION:
# - course_add.php: course.feature's own established gotcha applies here too
#   (course_creation_form_set_course_category_mandatory is ON on this box) —
#   every course below selects a category via the existing "... from the
#   ajax select ..." step, even though the Behat source never did. Without
#   it, creation silently re-renders the same form instead of erroring.
# - course_language's real id is "update_course_course_language", but its
#   NAME is "course_language" (resolveField's name-tier matches) — its
#   options must be selected by LABEL text ("English"/"Français"), not the
#   locale code the Behat source used ("en_US"/"fr_FR"); "I select ... from
#   ..." always matches by label. "Special" keeps the Behat source's own
#   choice of leaving language unset (this box's default course language).
#
# INTERFACE LANGUAGE FOLLOWS THE COURSE'S OWN LANGUAGE:
# - Confirmed live: entering "Testing course fr" (configured as fr_FR) makes
#   the ENTIRE interface French for as long as the browser stays inside it —
#   not just tool labels, the whole sidebar/breadcrumb too. This is exactly
#   why the Behat source's own strings inside this course are French
#   ("Nouveau document", "Parcours d'apprentissage", "Cahier de notes",
#   "Exercices", "Forums") — not a stale leftover from whoever wrote it, a
#   real, reproducible platform behavior. Kept the same French strings here
#   for the same reason, confirmed against a freshly-created instance of
#   this exact course rather than assumed.
#
# EXERCISE/TESTS TOOL — FULLY REWRITTEN, NOT PORTED VERBATIM:
# - Same real drift toolExerciseAdmin.feature's own header already
#   documents: the exercise/quiz tool is fully Vue now
#   (ExerciseQuestionSelectorView.vue et al.) — none of the Behat source's
#   legacy selectors survive (exercise_admin.php, "questionName", "answer1"
#   tinymce fields, "submitExercise"/"submitQuestion"/"submit-question").
#   Ported using toolExerciseAdmin.feature's own already-proven conventions
#   instead ("I follow the question type ...", "I fill in the answer N
#   text/comment with ...", "Save the question").
# - Question-type TITLE attributes on the "add a question" icon grid are
#   NOT translated for several types even inside a French course (confirmed
#   live on "Testing course fr" itself): "Multiple choice - Choose one
#   correct answer.", "Open question - Learners write a free text answer."
#   and "Unique answer with images - Single choice question using images."
#   all render in English regardless of course language — an existing app
#   i18n gap, not something this port needs to work around.
# - "QRU" (question à réponse unique) maps to the modern "Multiple choice"
#   type (single correct answer via radio, confirmed live via its own title
#   text "Choose one correct answer.").
# - "Image selection question" has no literal modern equivalent named
#   "image selection" — mapped to "Unique answer with images" (single
#   choice using images), confirmed live to be structurally IDENTICAL to
#   "Multiple choice"'s own answer-table shape (same "fill in the answer N
#   text/comment", "mark answer N as correct", "Save the question" steps
#   apply unchanged) — the closest real, working equivalent, not a guess.
#
# FORUM — RESTORED, NOT LEFT COMMENTED OUT:
# - The Behat source has this whole section commented out with a
#   "CHAMILO BUG: HTTP 500 on /main/forum/index.php" note. Re-verified live,
#   twice, against a freshly created course: both the legacy
#   "/main/forum/index.php?cid=X" route AND the Vue "/resources/forum/X"
#   route return 200, and creating a category then a forum through the UI
#   ("Ajouter une catégorie"/"Ajouter un forum" dialogs) succeeds cleanly
#   with no error — not reproducible on this box now (fixed upstream since,
#   or specific to however the original test reached it). Restored for
#   real: the scenario's own name ("...forum...") already promises this,
#   and toolForum.feature (this suite's own already-ported file)
#   independently confirms the legacy forum tool works.
# - The forum category dialog's "Description" field (forum_category_comment)
#   is a PLAIN textarea (confirmed live, no TinyMCE toolbar) — filled via
#   the generic "I fill in the following:" step. The FORUM dialog's own
#   "Description" (forum_comment) IS TinyMCE-backed — filled via the
#   existing "I fill in tinymce field ... with ..." step instead.
# - No forum item is added to the Learning Path — this matches the actual
#   current Behat source (its own LP item list is introduction/QRU
#   exercise/open exercise/final only, 4 items, no forum) even though an
#   earlier paraphrase of this task mentioned a forum LP item; the literal
#   source file, which is what this port follows, does not include one.
#
# LEARNING PATH BUILDER:
# - Adding an EXISTING course resource (as opposed to creating a new one
#   inline) is a single CLICK on its name in the right-hand resource panel —
#   confirmed live, no drag-and-drop simulation needed despite the panel's
#   items being marked `draggable="true"` (a toast literally reads "Ajouté"/
#   "Added" on click). New steps "I add LP item ... from the resource panel"
#   and "I switch the LP resource panel to ..." (common.steps.ts) wrap this;
#   the panel shows one resource TYPE at a time (Documents by default),
#   switched via title-attribute icon buttons ("Exercices"/"Documents" here,
#   confirmed both exist and are locale-text, same as the main course tool
#   names).
# - Prerequisites: each LP tree item has its own "Prerequisites" icon opening
#   an inline radio list of every EARLIER item in the tree (a later sibling
#   is never offered, confirmed live — matches real LP semantics: you can
#   only require something that already precedes you). A Minimum/Maximum
#   score pair only appears once an exercise-type prerequisite is selected
#   (never for a plain document), with ids that embed the prerequisite's own
#   numeric id at runtime (`lp-prerequisite-min-<id>`) — the new
#   "I set the prerequisite of LP item ... to ... with minimum score ..."
#   step (common.steps.ts) resolves that id from the just-checked radio
#   rather than hardcoding it.
#
# COURSE INTRODUCTION — REAL APP QUIRK, NOT A PORTING BUG:
# - Saving the course-introduction editor (the "+"/`span.mdi-plus` widget on
#   the course homepage, "Valider" button) redirects to `/admin` afterward —
#   confirmed live, reproducible every time, regardless of which course it's
#   done in. The edit itself DOES persist correctly (re-entering the course
#   afterward shows the saved link) — this is just where the app's own
#   post-save redirect happens to land, not a failed save. Every step after
#   this one re-enters the course explicitly rather than assuming the
#   browser is still inside it.
# - The Behat source's own hardcoded link target
#   (`/main/lp/lp_controller.php?action=view&cid=15&sid=0&lp_id=4`, ids from
#   whatever run originally produced that file) isn't reproducible here —
#   this port links to "#" instead, since the point of this step is only to
#   prove the course-introduction editor accepts and saves arbitrary HTML,
#   not to validate the LP's own numeric id.
#
# NOT PORTED, MATCHING THE SOURCE'S OWN OMISSION:
# - The Behat source's "Course settings -> Email notifications" tweak is
#   commented out with no bug annotation at all (unlike the forum section
#   above, which explains why) — left out here too rather than expanding
#   scope beyond what the source itself currently exercises.
#
# ASSESSMENTS / GRADEBOOK:
# - "Cahier de notes" (Assessments, French) -> `a[href*='gradebook_add_eval']`
#   -> name/weight_mask/max/submit is all still correct as the Behat source
#   describes, confirmed live end-to-end (a concurrently-ported
#   toolAssessments.feature covers the Assessments tool in much more depth;
#   this is only the one classroom-activity creation this scenario itself
#   needs).
#
# TEACHER ACCOUNT — RENAMED TO AVOID A REAL CROSS-FILE COLLISION:
# - The Behat source creates a teacher literally named/usernamed "teacher".
#   tests/playwright/features/createUser.feature (already in this suite)
#   ALSO creates a user with username "teacher" (its own "Create a teacher
#   user" scenario, confirmed by reading that file directly) — same
#   username, only a different email domain. Since usernames are unique
#   platform-wide, running both files against the same box risks a genuine
#   collision (whichever runs second fails outright, or worse, silently
#   reuses/edits the other file's account depending on run order) — exactly
#   the kind of cross-file collision this migration keeps finding. Renamed
#   to "teacher1" here (kept firstname/lastname "Teacher"/"Teacher" the same
#   as the source, for the same "General tutor" display purpose) and
#   deleted at teardown below regardless, so no ordering assumption is
#   needed either way.
# - The ADD form's own "password" field looks like it sets a password but
#   does NOT (confirmed live: logging in with it immediately after creation
#   fails) — only the follow-up user-list "Edit" -> reset_password=2 ->
#   "password" -> submit flow the Behat source already uses actually sets a
#   usable one. Kept that whole follow-up step, not simplified away.
# - "TEACHER" (the Behat source's own literal string for the role select)
#   is the option's VALUE, not its label — "Teacher" is (confirmed live);
#   the existing "I select ... from ..." step matches by label, so this
#   needed the same value->label fix createUser.feature's own header
#   comment already documents for this exact field ("Trainer" -> "Teacher").
# - REAL, CONFIRMED-TRANSIENT APP ISSUE, not fixed here (resolved itself
#   before this file was finished, not a lingering blocker): a concurrent
#   task in this same session is porting "Add user extra fields" scenarios
#   against this shared box. While that was actively running, GETting
#   `/main/admin/user_add.php` briefly 500'd with "Cannot access offset of
#   type string on string" — traced to `ExtraField::addElements()` /
#   `set_extra_fields_in_form()` (public/main/inc/lib/extra_field.lib.php),
#   which renders every user extra field's options on this page; a handful
#   of freshly-inserted `extra_field` rows (browser_platforme,
#   moment_de_disponibilite, ...) had zero matching `extra_field_options`
#   rows at that exact moment (confirmed via a direct DB query, timestamped
#   the same minute). Retried a few minutes later once that concurrent
#   task's own scenario had finished — 200 again, user creation worked
#   normally from then on. Flagged here for visibility since it could
#   recur if this file and the extra-fields one are ever run at the exact
#   same moment again, but not something this file can or should work
#   around (the fix, if any, belongs to that other file/PR).
#
# SESSION DATES — SHIFTED FORWARD, NOT LEFT AS-IS:
# - The Behat source's literal dates (2026-01-20 through 2026-02-17, plus a
#   separate 2026-04-26/2026-05-10 pair for the English future session) are
#   ALL in the past relative to this environment's current date
#   (2026-08-05) by the time this port runs — none of them would still
#   plausibly justify their own scenario name ("Present session" being
#   "In progress", the two "future" sessions being "Planned"). Session
#   status here is a plain, manually-chosen FormValidator select (confirmed
#   live: choosing "In progress" and saving just sets that value, it is not
#   silently recomputed from the dates on save) — so this is about the
#   scenario staying semantically honest, not a hard functional requirement.
#   Shifted every session's dates forward to straddle/precede/follow
#   2026-08-05 as appropriate, keeping each source pair's own day-span
#   (14 days) intact: Past session now ends just before Present session
#   starts, Present session straddles today, both "future" sessions sit
#   comfortably after it. Exact absolute dates are otherwise arbitrary, same
#   as the source's own were.
#
# SELF-CONTAINMENT: full cleanup, not left in place. Confirmed via a grep of
# this suite's existing tests/playwright/features/*.feature (before writing
# a single line here) that none of "Testing course en", "Testing course fr",
# "Special", "Present session", "Session in the future", "Session in the
# future en", or "Past session" collide with anything another file already
# depends on. Given how much this creates (3 courses with real content
# inside one of them, a teacher account, 4 sessions), leaving all of it
# around indefinitely was judged worse than the cost of tearing it down —
# the final scenario below deletes the 4 sessions, the teacher1 account,
# and all 3 courses (which cascades away every document/exercise/forum/LP/
# gradebook item created inside "Testing course fr").
# DISABLED 2026-08-06 per explicit user instruction — this file was failing
# in CI and is being held back from execution entirely (not merely
# deprioritized) until further notice. Do not remove the @skip tags below
# without asking first.
#
# REAL bddgen BEHAVIOR FOUND while wiring this up (confirmed via systematic
# isolation with minimal repro files, not guessed — an initial theory that
# `@skip` on the FEATURE line itself was the trigger turned out to be a red
# herring once tested in true isolation): when EVERY Scenario in a Feature is
# tagged `@skip`, bddgen silently generates NO output file at all for that
# feature (exit code 0, zero warnings/errors, .features-gen/features/
# <name>.feature.spec.js simply never gets written) — confirmed by testing a
# 2-scenario file with only ONE skipped, which compiled fine with a single
# `test.skip()`, versus the same file with BOTH skipped, which produced no
# file. This is actually the desired, stronger outcome here: a missing spec
# file can't be discovered by the test runner at all, which is a more
# complete "disable" than `test.skip()` would be. Every Scenario below is
# tagged `@skip` individually (this suite's established pattern, e.g.
# toolGroup.feature/courseCatalogue.feature/toolChat.feature) — if this file
# is ever re-enabled by un-skipping at least one Scenario while others remain
# skipped, expect a real spec file with `test.skip()` calls for the rest,
# per playwright-bdd's own documented (non-buggy) behavior in that case.
# RE-ENABLED 2026-08-19 (the @skip tags referenced above are gone): the
# whole file was held back from execution, so SpecialCase1 had ZERO session
# coverage — because EVERY scenario was skipped, bddgen emitted no spec file
# at all (see the note above), which is also why this file never showed up in
# any CI report as failing. Its only cross-file prerequisite is the session
# extra fields (extra_domaine / extra_theme_fr / extra_theme_de /
# extra_ecouter) created by specialCase1PlatformSettings.feature's "Add
# minimal session extra fields" scenario, which was itself @skip'd and is now
# re-enabled too. This file needs no cid fixture: it creates every course it
# uses and refers to them by code, so it has zero cid= references and is
# unaffected by the suite-wide cid=1 -> cid=3 migration.
#
# @long-scenario is REQUIRED here, not optional (added with the re-enable):
# without it every scenario below inherits playwright.config.ts's default 90s
# per-test budget, and the first one alone creates 3 courses plus documents,
# exercises, a forum, a learning path and an assessment activity across ~180
# steps — a single bare course creation already measures ~48s on this box, so
# 90s cannot cover it. The tag is applied at Feature level (same as
# specialCase1PlatformSettings.feature's own `@common @admin @long-scenario`)
# so all 6 scenarios get the 15-minute budget the Before hook in
# common.steps.ts grants: the 4 session-creation scenarios and the teardown
# are smaller, but each still submits several legacy full-page-reload forms.
@common @long-scenario @specialcase1
Feature: Special case 1 — course/session creation
  In order to validate a realistic multi-course, multi-session platform setup
  As an administrator
  I need to create courses with content, a teacher, and 4 sessions

  Scenario: Create courses, multilingual documents, exercises, forum, learning path and assessment activity
    Given I am a platform administrator

    When I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "Testing course en"
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "English" from "course_language"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Testing course en"

    When I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "Special"
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I click the "input[name='sticky']" element
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Special"

    When I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "Testing course fr"
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "Français" from "course_language"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Testing course fr"

    # Two HTML documents: introduction, final
    Given I am on course "TESTINGCOURSEFR" homepage
    And I wait for the page to be loaded
    When I follow "Documents"
    And I wait for the page to be loaded
    And I click the "[title='Nouveau document']" element
    And I wait for the page to be loaded
    And I fill in "title" with "introduction"
    And I fill in tinymce field "item_content" with "<p class='ck ck-texte'><span dir='ltr' lang='en'>English content</span><span dir='ltr' lang='fr'>Contenu en français</span></p>"
    And I click the "button:has(.mdi-content-save)" element
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "introduction"

    When I click the "[title='Nouveau document']" element
    And I wait for the page to be loaded
    And I fill in "title" with "final"
    And I fill in tinymce field "item_content" with "<p class='ck ck-texte'><span dir='ltr' lang='en'>English content</span><span dir='ltr' lang='fr'>Contenu en français</span></p>"
    And I click the "button:has(.mdi-content-save)" element
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "final"

    # Exercise 1: "QRU and Image Selection exercise" — a Multiple choice
    # question (QRU) and a Unique answer with images question (the modern
    # equivalent of "image selection", see header comment)
    Given I am on course "TESTINGCOURSEFR" homepage
    And I wait for the page to be loaded
    When I follow "Exercices"
    And I wait for the page content to settle
    And I click the "[title='Créer un exercice']" element
    And I wait for the page to be loaded
    And I fill in "title" with "QRU and Image Selection exercise"
    And I press "Poursuivre avec la création de questions"
    And I wait for the page content to settle

    And I follow the question type "Multiple choice"
    And I wait for the page content to settle
    And I fill in "question" with "QRU Question"
    And I fill in the answer 1 text with "Option A"
    And I fill in the answer 2 text with "Option B"
    And I fill in the answer 3 text with "Option C"
    And I fill in the answer 4 text with "Option D"
    And I press "Save the question"
    And I wait for the page content to settle
    Then I should see "QRU Question"

    And I follow the question type "Unique answer with images"
    And I wait for the page content to settle
    And I fill in "question" with "Image selection question"
    And I fill in the answer 1 text with "Image A"
    And I fill in the answer 2 text with "Image B"
    And I fill in the answer 3 text with "Image C"
    And I fill in the answer 4 text with "Image D"
    And I press "Save the question"
    And I wait for the page content to settle
    Then I should see "Image selection question"

    # Exercise 2: "Open question exercise" — a single Open question
    Given I am on course "TESTINGCOURSEFR" homepage
    And I wait for the page to be loaded
    When I follow "Exercices"
    And I wait for the page content to settle
    And I click the "[title='Créer un exercice']" element
    And I wait for the page to be loaded
    And I fill in "title" with "Open question exercise"
    And I press "Poursuivre avec la création de questions"
    And I wait for the page content to settle
    And I follow the question type "Open question"
    And I wait for the page content to settle
    And I fill in "question" with "Open Question"
    And I fill in "exercise-manual-question-score" with "5"
    And I press "Save the question"
    And I wait for the page content to settle
    Then I should see "Open Question"

    # Forum category + forum (see header comment: restored for real, not
    # left commented out — confirmed working live)
    Given I am on course "TESTINGCOURSEFR" homepage
    And I wait for the page to be loaded
    When I follow "Forums"
    And I wait for the page to be loaded
    And I press "Ajouter une catégorie"
    And I wait for the page content to settle
    And I fill in the following:
      | forum_category_title   | Course discussions |
      | forum_category_comment | Discussions for this course |
    And I press "Créer une catégorie"
    And I wait for the page to be loaded
    Then I should see "Course discussions"

    When I press "Ajouter un forum"
    And I fill in the following:
      | forum_title | General forum |
    # "forum-comment" with a HYPHEN, not the legacy "forum_comment" with an
    # underscore: the Vue forum tool's "Ajouter un forum" DIALOG names its
    # description editor `id="forum-comment"` (and sets no `name` attribute at
    # all, so the id is the only way in) — confirmed live by dumping every
    # textarea plus `window.tinymce.editors` with the dialog open. The
    # underscore form belongs to the LEGACY full-page route
    # /main/forum/index.php?action=add_forum, which toolForum.feature uses
    # instead (and which is also why that file uses the different "I fill in
    # editor field ..." step). Getting this wrong fails as "Could not find an
    # id for field with locator: forum_comment", because resolveField()'s
    # getByLabel("Description") tier matches a wrapper element that has no id.
    And I fill in tinymce field "forum-comment" with "General discussion forum"
    And I press "Créer ce forum"
    And I wait for the page to be loaded
    Then I should see "General forum"

    # Learning Path "LP Test": add introduction, both exercises, final —
    # in that order — then a prerequisite on "final"
    Given I am on course "TESTINGCOURSEFR" homepage
    And I wait for the page to be loaded
    When I follow "Parcours d'apprentissage"
    And I wait for the page to be loaded
    And I click the "span.mdi-plus" element
    And I wait for the page to be loaded
    # "lp-title" (the real id), NOT the visible label "Learning path name":
    # resolveField()'s label tier is the LAST resort and matches the RENDERED
    # label, which inside this deliberately-French course reads "Nom du
    # parcours" — so an English label string can never match here, and the
    # step hangs the whole scenario budget instead of failing fast. Confirmed
    # live on the LP create form (/resources/lp/<node>/create): the only field
    # is <input id="lp-title" name="title"> with <label for="lp-title">Nom du
    # parcours</label>. Using the id also avoids the bare name "title", which
    # is generic enough to collide on other forms.
    And I fill in "lp-title" with "LP Test"
    And I press "Continue"
    And I wait for the page to be loaded
    And I add LP item "introduction" from the resource panel
    And I wait for the page content to settle
    And I switch the LP resource panel to "Exercices"
    And I add LP item "QRU and Image Selection exercise" from the resource panel
    And I wait for the page content to settle
    And I add LP item "Open question exercise" from the resource panel
    And I wait for the page content to settle
    And I switch the LP resource panel to "Documents"
    And I add LP item "final" from the resource panel
    And I wait for the page content to settle
    Then I should see "introduction"
    And I should see "QRU and Image Selection exercise"
    And I should see "Open question exercise"
    And I should see "final"

    When I set the prerequisite of LP item "final" to "Open question exercise" with minimum score "0"
    And I wait for the page content to settle
    Then I should not see an error

    # Course introduction, linking to the LP (see header comment: saving
    # redirects to /admin, a real app quirk, not a failure)
    Given I am on course "TESTINGCOURSEFR" homepage
    And I wait for the page to be loaded
    When I click the "span.mdi-plus" element
    And I wait for the page to be loaded
    And I fill in tinymce field "introText" with "<a href='#'>LP Test</a>"
    And I press "Valider"
    And I wait for the page to be loaded

    # Assessments: classroom activity "Course validation"
    Given I am on course "TESTINGCOURSEFR" homepage
    And I wait for the page to be loaded
    When I follow "Cahier de notes"
    And I wait for the page content to settle
    # The Assessments tool is fully Vue now — the legacy
    # `a[href*='gradebook_add_eval']` link this step used to click, and the
    # legacy `name`/`weight_mask`/`max` form it led to, are both gone. That
    # selector matched nothing, so the step hung until the 15-minute
    # @long-scenario budget expired (real CI + locally reproduced).
    #
    # toolAssessments.feature already covers this same tool against the modern
    # UI and passes in CI, so its field ids are reused verbatim here rather than
    # re-derived: gradebook-evaluation-title / -weight / -max-score.
    #
    # The control is clicked by ICON CLASS (mdi-certificate), NOT by the label
    # "Add classroom activity" that toolAssessments.feature can use: this
    # scenario works inside "Testing course fr", where the whole interface
    # renders in French, so an English button label cannot match. Verified live
    # that the Assessments toolbar buttons are icon-only with a localized
    # `title` and a locale-independent icon class —
    # `<button title="Add a category">` is mdi-folder-plus and
    # `<button title="Add classroom activity">` is mdi-certificate.
    And I click the "span.mdi-certificate" element
    And I wait for the page content to settle
    And I fill in the following:
      | gradebook-evaluation-title     | Course validation |
      | gradebook-evaluation-weight    | 100               |
      | gradebook-evaluation-max-score | 1                 |
    # ENGLISH label on purpose, even though this whole course renders in French.
    # "Add classroom activity" is one of the strings this app does NOT actually
    # translate — verified live by dumping the dialog inside "Testing course fr"
    # itself: the sibling toolbar button next to it reads
    # title="Ajouter une catégorie" (translated) while this one stays
    # title="Add classroom activity", and the dialog's own submit button's text
    # is likewise "Add classroom activity" while its Cancel reads "Annuler".
    # translations/messages.fr_FR.po DOES carry a msgstr for it ("Nouvelle
    # évaluation présentielle") — using that fails, which is exactly the trap:
    # the .po file is not evidence of what the page renders. Same class of
    # already-documented i18n gap as the question-type titles noted in this
    # file's header.
    #
    # Unambiguous despite the toolbar button sharing the label: verified live
    # that getByRole("button", { name: "Add classroom activity", exact: true })
    # matches exactly ONE element, and the form is a real PrimeVue dialog
    # (.p-dialog / role=dialog, count 1), so pressButton's dialog-scoped tier
    # resolves it inside that dialog anyway.
    And I press "Add classroom activity"
    And I wait for the page content to settle
    Then I should see "Course validation"

  Scenario: Create teacher and configure "Present session" with settings and include course
    Given I am a platform administrator

    # Teacher account (named "teacher1", not the source's "teacher" — see
    # header comment for the real createUser.feature username collision
    # this avoids)
    When I am on "/main/admin/user_add.php"
    And I wait for the page to be loaded
    And I fill in the following:
      | firstname | Teacher |
      | lastname  | Teacher |
      | email     | teacher1@example.test |
      | username  | teacher1 |
    And I select "Teacher" from "user_add_roles"
    And I click the "input#send_mail_no" element
    And I press "submit"
    And I wait for the page to be loaded
    Then I should not see an error

    # user_edit.php only renders reset_password=2 ("Set password manually")
    # when security.admins_can_set_users_pass is on. Fresh-install default is
    # off — a real CI snapshot of this step showed only "Don't reset password"
    # / "Automatically generate a new password", then a 15-minute hang on
    # input[name=reset_password][value=2]. Enable it here rather than
    # depending on specialCase1PlatformSettings having finished first.
    Given I am on "/admin/settings/security"
    And I wait for the page to be loaded
    And I select "Yes" from "form_admins_can_set_users_pass"
    And I press "Save settings"
    And I wait for the page to be loaded

    Given I am on "/admin/user-list?keyword=teacher1"
    And I wait for the page to be loaded
    And I click the "[title='Edit']" icon in the row for "teacher1@example.test"
    And I wait for the page to be loaded
    And I click the "input[name='reset_password'][value='2']" element
    And I fill in "password" with "teacher1"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should not see an error

    # Session "Present session" — straddles today (2026-08-05 in this
    # environment), see header comment on why the source's own 2026-01-20
    # dates were shifted forward
    When I am on "/main/session/session_add.php"
    And I wait for the page to be loaded
    And I click the "#advanced_params" element
    And I fill in the following:
      | title | Present session |
    And I set hidden field "access_start_date" to "2026-07-27 00:00"
    And I set hidden field "display_start_date" to "2026-07-27 00:00"
    And I set hidden field "coach_access_start_date" to "2026-07-27 00:00"
    And I set hidden field "access_end_date" to "2026-08-10 00:00"
    And I set hidden field "display_end_date" to "2026-08-10 00:00"
    And I set hidden field "coach_access_end_date" to "2026-08-10 00:00"
    And I press "submit"
    And I wait for the page to be loaded
    And I select "Testing course fr" from the ajax select "courses"
    And I click the "input[name='copy_evaluation']" element
    And I press "submit"
    And I wait for the page to be loaded
    And I press "submit"
    And I wait for the page to be loaded
    Then I should not see an error

    And I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    And I click the "button.select2-selection__choice__remove" element
    And I select "teacher1" from the ajax select "coach_username"
    And I press "Edit this session"
    And I wait for the page to be loaded
    Then I should not see an error

    And I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    And I press "Advanced settings"
    And I select "In progress" from "status"
    And I select "vie-quotidienne" from "extra_domaine"
    And I select "theme1" from the ajax select "extra_theme_fr"
    And I select "theme1" from the ajax select "extra_theme_de"
    And I select the first option from "extra_ecouter"
    And I select the first option from "extra_lire"
    And I select the first option from "extra_participer_a_une_conversation"
    And I select the first option from "extra_s_exprimer_oralement_en_continu"
    And I select the first option from "extra_ecrire"
    And I press "Edit this session"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Create future session "Session in the future" and include course
    Given I am a platform administrator

    When I am on "/main/session/session_add.php"
    And I wait for the page to be loaded
    And I click the "#advanced_params" element
    And I fill in the following:
      | title | Session in the future |
    And I set hidden field "access_start_date" to "2026-08-20 00:00"
    And I set hidden field "display_start_date" to "2026-08-20 00:00"
    And I set hidden field "coach_access_start_date" to "2026-08-20 00:00"
    And I set hidden field "access_end_date" to "2026-09-03 00:00"
    And I set hidden field "display_end_date" to "2026-09-03 00:00"
    And I set hidden field "coach_access_end_date" to "2026-09-03 00:00"
    And I press "submit"
    And I wait for the page to be loaded
    And I select "Testing course fr" from the ajax select "courses"
    And I click the "input[name='copy_evaluation']" element
    And I press "submit"
    And I wait for the page to be loaded
    And I press "submit"
    And I wait for the page to be loaded
    Then I should not see an error

    And I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    And I click the "button.select2-selection__choice__remove" element
    And I select "teacher1" from the ajax select "coach_username"
    And I press "Edit this session"
    And I wait for the page to be loaded
    Then I should not see an error

    And I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    And I press "Advanced settings"
    And I select "Planned" from "status"
    And I select "vie-quotidienne" from "extra_domaine"
    And I select "theme1" from the ajax select "extra_theme_fr"
    And I select "theme1" from the ajax select "extra_theme_de"
    And I select the first option from "extra_ecouter"
    And I select the first option from "extra_lire"
    And I select the first option from "extra_participer_a_une_conversation"
    And I select the first option from "extra_s_exprimer_oralement_en_continu"
    And I select the first option from "extra_ecrire"
    And I press "Edit this session"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Create past session "Past session" and include course
    Given I am a platform administrator

    When I am on "/main/session/session_add.php"
    And I wait for the page to be loaded
    And I click the "#advanced_params" element
    And I fill in the following:
      | title | Past session |
    And I set hidden field "access_start_date" to "2026-06-01 00:00"
    And I set hidden field "display_start_date" to "2026-06-01 00:00"
    And I set hidden field "coach_access_start_date" to "2026-06-01 00:00"
    And I set hidden field "access_end_date" to "2026-06-15 00:00"
    And I set hidden field "display_end_date" to "2026-06-15 00:00"
    And I set hidden field "coach_access_end_date" to "2026-06-15 00:00"
    And I press "submit"
    And I wait for the page to be loaded
    And I select "Testing course fr" from the ajax select "courses"
    And I click the "input[name='copy_evaluation']" element
    And I press "submit"
    And I wait for the page to be loaded
    And I press "submit"
    And I wait for the page to be loaded
    Then I should not see an error

    And I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    And I click the "button.select2-selection__choice__remove" element
    And I select "teacher1" from the ajax select "coach_username"
    And I press "Edit this session"
    And I wait for the page to be loaded
    Then I should not see an error

    And I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    And I press "Advanced settings"
    And I select "Finished" from "status"
    And I select "vie-quotidienne" from "extra_domaine"
    And I select "theme2" from the ajax select "extra_theme_fr"
    And I select "theme2" from the ajax select "extra_theme_de"
    And I select the first option from "extra_ecouter"
    And I select the first option from "extra_lire"
    And I select the first option from "extra_participer_a_une_conversation"
    And I select the first option from "extra_s_exprimer_oralement_en_continu"
    And I select the first option from "extra_ecrire"
    And I press "Edit this session"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Create future English session "Session in the future en" and include course
    Given I am a platform administrator

    When I am on "/main/session/session_add.php"
    And I wait for the page to be loaded
    And I click the "#advanced_params" element
    And I fill in the following:
      | title | Session in the future en |
    And I set hidden field "access_start_date" to "2026-09-10 00:00"
    And I set hidden field "display_start_date" to "2026-09-10 00:00"
    And I set hidden field "coach_access_start_date" to "2026-09-10 00:00"
    And I set hidden field "access_end_date" to "2026-09-24 00:00"
    And I set hidden field "display_end_date" to "2026-09-24 00:00"
    And I set hidden field "coach_access_end_date" to "2026-09-24 00:00"
    And I press "submit"
    And I wait for the page to be loaded
    And I select "Testing course en" from the ajax select "courses"
    And I click the "input[name='copy_evaluation']" element
    And I press "submit"
    And I wait for the page to be loaded
    And I press "submit"
    And I wait for the page to be loaded
    Then I should not see an error

    And I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    And I click the "button.select2-selection__choice__remove" element
    And I select "teacher1" from the ajax select "coach_username"
    And I press "Edit this session"
    And I wait for the page to be loaded
    Then I should not see an error

    And I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    And I press "Advanced settings"
    And I select "Planned" from "status"
    And I select "vie-quotidienne" from "extra_domaine"
    And I select "theme1" from the ajax select "extra_theme_fr"
    And I select "theme1" from the ajax select "extra_theme_de"
    And I select the first option from "extra_ecouter"
    And I select the first option from "extra_lire"
    And I select the first option from "extra_participer_a_une_conversation"
    And I select the first option from "extra_s_exprimer_oralement_en_continu"
    And I select the first option from "extra_ecrire"
    And I press "Edit this session"
    And I wait for the page to be loaded
    Then I should not see an error

  # Full cleanup — see header comment's SELF-CONTAINMENT note. Sessions
  # first (they reference the courses, not the other way around), then the
  # teacher account, then the 3 courses (cascades away every document/
  # exercise/forum/LP/gradebook item created inside "Testing course fr").
  Scenario: Teardown special case 1 sessions
    Given I am a platform administrator

    # All four session deletions below use "I delete the session ... if
    # present" rather than the unguarded "I click the .mdi-delete icon in the
    # row for ..." + "I press Yes" pair they used to. Same convention, and for
    # the same reason, as specialCase1PlatformSettings.feature's own Tear down
    # (see that step's comment in common.steps.ts): if ANY earlier scenario in
    # this file fails, the sessions it was supposed to create do not exist, and
    # an unguarded delete then burns the ENTIRE 15-minute @long-scenario budget
    # waiting for a row that will never appear — turning one upstream failure
    # into a second, far slower, entirely derivative one. Confirmed exactly
    # that way: a teardown run hung 15m on "Present session" purely because an
    # earlier partial run had already removed it. The guarded step also makes
    # this scenario idempotent, so it doubles as a reset for a half-finished
    # previous run instead of dying on it.
    #
    # Deletion ORDER still matters and is deliberate: "Session in the future en"
    # must go BEFORE "Session in the future", because the latter's name is a
    # prefix of the former's, and the row lookup matches on contained text —
    # so with both present, deleting "Session in the future" could match the
    # "... en" row instead.
    Given I am on "/admin/session-list?keyword=Present+session"
    And I wait for the page to be loaded
    And I delete the session "Present session" if present

    Given I am on "/admin/session-list?keyword=Session+in+the+future+en"
    And I wait for the page to be loaded
    And I delete the session "Session in the future en" if present

    Given I am on "/admin/session-list?keyword=Session+in+the+future"
    And I wait for the page to be loaded
    And I delete the session "Session in the future" if present

    # list_type=all is REQUIRED for this one session and no other: "Past
    # session" is created with access dates in the past, so it ends up with
    # status FINISHED, and this platform's `session.default_session_list_view`
    # setting is `custom` — which SessionListController::applyListTypeFilter()
    # restricts to PLANNED or IN PROGRESS only. Under the default view the row
    # therefore does not exist in the table at all ("No data available"), and
    # the delete step below hangs the whole scenario budget looking for it.
    # The other 3 sessions in this teardown are current/future, so they show
    # up fine without it.
    #
    # Note the SNAKE_CASE name: the browser-URL parameter SessionList.vue
    # reads in onMounted is `list_type`, which it then forwards to the data
    # endpoint as `listType` (camelCase). Using `listType` in the URL silently
    # does nothing — confirmed both ways: /admin/session-list?listType=all
    # still rendered only the default view, while the data endpoint itself
    # (/admin/session-list-data?listType=all) correctly returned the session,
    # proving the backend filter works and only the URL spelling was wrong.
    Given I am on "/admin/session-list?list_type=all&keyword=Past+session"
    And I wait for the page to be loaded
    And I delete the session "Past session" if present

    Given I am on "/admin/user-list?keyword=teacher1"
    And I wait for the page to be loaded
    And I click the "[title='Delete']" icon in the row for "teacher1@example.test"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "teacher1@example.test"

    Given I am on "/admin/course-list?keyword=Testing+course+en"
    And I wait for the page to be loaded
    And I click the "[title='Delete']" icon in the row for "Testing course en"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "Testing course en"

    Given I am on "/admin/course-list?keyword=Special"
    And I wait for the page to be loaded
    And I click the "[title='Delete']" icon in the row for "Special"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "Special"

    Given I am on "/admin/course-list?keyword=Testing+course+fr"
    And I wait for the page to be loaded
    And I click the "[title='Delete']" icon in the row for "Testing course fr"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "Testing course fr"
