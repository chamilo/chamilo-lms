# Ported from tests/behat/features/SpecialCase/newPlatform/SpecialCase2.feature
# (git show 98c77757ea6:tests/behat/features/SpecialCase/newPlatform/SpecialCase2.feature).
# Covers that file's scenarios 2-5 (tutor creation and learner assignment,
# diagnosis + finalization message, student report + legal agreement +
# skill assignment + learning-path exercises, legal-agreement deletion).
# Scenario 1 (self-registration) lives in specialCase2Registration.feature,
# whose "parkur01" account this file depends on; scenario 6 (teacher tools)
# lives in specialCase2TeacherTools.feature. Every selector below was
# verified LIVE against testparkur.beeznest.com.
#
# TUTOR CREATION — /main/admin/user_add.php IS GONE, NOT PORTED BLIND:
# - Same finding specialCase1Sessions.feature's own header already documents
#   for "teacher1": master commit d89fa1a9395 blanked the legacy user_add.php
#   down to a deprecated stub. "/admin/user-add" (the Vue replacement) is
#   used instead, same field/role/password mechanics already proven there
#   (multiselect role picker, "Set password manually" radio, "No" mail
#   radio).
# - "STUDENT_BOSS" (the Behat source's literal role value) is "Superior
#   (n+1)" as a LABEL — confirmed live via the roles multiselect's own
#   option list — same value-vs-label trap createUser.feature's own header
#   already documents for "Teacher".
# - The interface-language field ("user_edit_locale" in the Behat source) is
#   now a PrimeVue Select (`#locale`, no real `<select>` behind it) — reuses
#   the existing "I select the dropdown option ... in ..." step (see that
#   step's own header comment in common.steps.ts for why plain "I select"
#   cannot work here).
#
# LEARNER ASSIGNMENT — REROUTED AROUND AN UNSTABLE FILTER, NOT A UI SKIP:
# - The Behat source's own path (My Space > star icon > "Student's superior
#   follow up" > filter by language > "Add learner") still exists
#   (confirmed live at /reporting/admin/student-bosses), but its "Language"
#   filter is a PrimeVue Select whose id is assigned sequentially per page
#   load (`pv_id_<n>`) — not stable across runs, and unusable as a fixed
#   selector. The list also already holds 19+ real superiors on this box, so
#   scrolling/guessing a row is not reliable either. "I assign the learner
#   ... to the student's superior ..." (new step, common.steps.ts) exercises
#   the exact same underlying action (tc_report.php's add_user), just reached
#   by resolving the superior's id via find_users instead of that filter —
#   see the step's own comment for the full reasoning.
#
# NOT PORTED FROM THESE SCENARIOS, MATCHING THE SOURCE'S OWN SCOPE:
# - The Behat source's own commented-out Videoconference/BBB check (its
#   prerequisites are out of scope for this file, same as
#   specialCase2Registration.feature's own omission of it).
#
# SELF-CONTAINMENT: tuteur_fr and tuteur_en are created and deleted here.
# parkur01 is created in specialCase2Registration.feature and ALSO deleted
# here, even though specialCase2TeacherTools.feature is the last file to
# actually USE the account in the scenario narrative (it subscribes parkur01
# to its own throwaway courses after this file's own tutor/diagnosis
# scenarios run) — what decides teardown ownership is Playwright's actual
# spec FILE EXECUTION ORDER, not narrative order: this suite runs its spec
# files in plain alphabetical order, and "specialCase2TutorWorkflow" sorts
# AFTER "specialCase2TeacherTools" ("Tut" > "Tea"). Confirmed live: putting
# the deletion in TeacherTools instead left it running BEFORE this file,
# so every scenario below that needs parkur01 found the account already
# gone. Whichever file the suite actually runs last is the one that must
# own a shared fixture's final teardown.
@common @long-scenario @specialcases @specialcase2
Feature: Special case 2 — tutor workflow
  In order to validate the tutor/superior follow-up workflow
  As a platform administrator and a tutor
  I need to create tutors, assign a learner, and follow up on their diagnosis and progress

  Scenario: Admin creates tutors with language and assigns learner parkur01
    Given I am a platform administrator

    # Tutor 1 — French language
    When I am on "/admin/user-add"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | firstname | Tuteur    |
      | lastname  | Francais  |
      | email     | tuteur.fr@example.test |
      | username  | tuteur_fr |
    And I check the "Set password manually" radio button
    And I fill in "password" with "TuteurFr01!"
    And I check the "No" radio button
    And I press the multiselect option "Superior (n+1)" in "roles"
    And I select the dropdown option "Français" in "locale"
    And wait very long for the page to be loaded
    And I press "Add"
    And wait very long for the page to be loaded
    Then I should not see an error

    # Tutor 2 — English language (default, no change needed)
    When I am on "/admin/user-add"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | firstname | Tuteur   |
      | lastname  | Anglais  |
      | email     | tuteur.en@example.test |
      | username  | tuteur_en |
    And I check the "Set password manually" radio button
    And I fill in "password" with "TuteurEn01!"
    And I check the "No" radio button
    And I press the multiselect option "Superior (n+1)" in "roles"
    And wait very long for the page to be loaded
    And I press "Add"
    And wait very long for the page to be loaded
    Then I should not see an error

    # ---- ASSIGNMENT OF PARKUR01 TO THE FRENCH TUTOR ----
    And I wait for the page content to settle
    And I assign the learner "parkur01" to the student's superior "tuteur_fr"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Tuteur_fr opens diagnosis page and sends finalization message
    # tuteur_fr's own interface language is French (set at creation above),
    # so every assertion below is the FRENCH string actually rendered —
    # confirmed live, same "interface language follows the account's own
    # language" behaviour specialCase1Sessions.feature's own header comment
    # already documents for a course's language.
    Given I am not logged
    And I am logged as "tuteur_fr" with password "TuteurFr01!"
    And I wait for the page to be loaded

    # ---- MESSAGING: verify learner assignment ----
    When I am on "/resources/messages"
    And I wait for the page to be loaded
    Then I should see "L'apprenant Test Learner vous a été assigné"

    And I resolve the user id for "parkur01"

    # ---- LEARNER PROFILE ----
    # myStudents.php?student=<id> — NOT the Behat source's hardcoded
    # "student=67" (never reproducible; see the "USER_ID" step's own
    # header comment) — still the real page for this, confirmed live:
    # /reporting/learners/<id> (the modern report the sidebar's own
    # "Apprenants" link points to) renders an empty panel for a learner
    # with no course/session activity yet, which parkur01 genuinely has
    # none of at this point in the story.
    When I am on "/main/my_space/myStudents.php?student=USER_ID" with the resolved user id
    And I wait for the page to be loaded
    Then I should see "Test Learner"
    And I should see "Statut"
    And I should see "Code officiel"
    And I should see "Tél"
    And I should see "Zone horaire"

    # ---- DIAGNOSTIC PAGE ----
    When I am on "/main/search/load_search.php"
    And I wait for the page to be loaded
    Then I should see "Chargement du diagnostic"
    And I should not see an error

    # "Afficher le diagnostic" — a real <button> (confirmed live), not a
    # link — loads the diagnosis accordion for the "Test Learner" pre-
    # selected in the "Utilisateur" field.
    And I press "Afficher le diagnostic"
    And I wait for the page content to settle
    And I click element "div.display-panel-collapse__header" containing text "Les thèmes qui m’intéressent"
    And I wait for the page content to settle
    # A native <select>'s own currently-selected <option> is not exposed as
    # visible DOM text the way a rendered div/span is (confirmed live: even
    # its own DISPLAY label, not just the raw value, fails a plain "I should
    # see" — Playwright's visibility check finds no rendered <option> node to
    # match), so this checks the underlying field VALUE directly instead,
    # same step toolGroup.feature's own settings-persistence checks use for
    # exactly this reason.
    Then the field "extra_domaine_0" should have value "vie-quotidienne"
    And the field "extra_domaine_1" should have value "arrivee-sur-mon-poste-de-travail"
    And the field "extra_domaine_2" should have value "competente-dans-mon-domaine-de-specialite"
    And the field "extra_theme_fr_0" should have value "theme1"

    # ---- SEND FINALIZATION MESSAGE ----
    # "Inviter à l'entretien de conseil" — confirmed live as the current
    # rendering of the Behat source's "Send diagnostic finalization
    # message" — a real <a href="/resources/messages/new?send_to_user=
    # <id>&prefill=diagnosticFinalizationMessage">, navigated to directly
    # rather than clicked through the diagnostic page's own French text.
    When I am on "/resources/messages/new?send_to_user=USER_ID&prefill=diagnosticFinalizationMessage" with the resolved user id
    And I wait for the page to be loaded
    Then I should not see an error

    # ---- LEGAL AGREEMENT ----
    # "Envoyer le contrat d'apprentissage" on myStudents.php — confirmed
    # live as a plain GET link with the send_legal action baked into the
    # query string; no separate confirmation/submit step exists (the
    # Behat source's own "I press 'Send legal agreement'" is stale — the
    # legacy multi-step page it targeted no longer works that way).
    When I am on "/main/my_space/myStudents.php?action=send_legal&student=USER_ID&course=" with the resolved user id
    And I wait for the page to be loaded
    Then I should not see an error

    # ---- ASSIGNED SESSIONS ----
    # See the two new steps' own header comments in common.steps.ts: the
    # Behat source's "click i.mdi-plus 5 times" doesn't translate directly
    # (that row's own icon never flips on reload — a real grid-staleness
    # bug, confirmed live against the session_rel_users API — so clicking
    # the same first match 5 times would just re-target one session, not
    # five). "At least 4", not 5: this exact platform already carried one
    # left-over subscription from verifying this very step live.
    And I subscribe the learner "parkur01" to the first 5 available sessions

    # ---- LOGOUT AND LOGIN BACK AS ADMIN ----
    Given I am not logged
    And I am a platform administrator
    And I wait for the page to be loaded

    # Checked as admin, not as tuteur_fr right after subscribing: confirmed
    # live that /api/session_rel_users?user=... is access-controlled per
    # SessionRelUserExtension (see CLAUDE.md's own reference for this exact
    # resource) — as tuteur_fr it always reports 0 regardless of what
    # actually got subscribed, since a tutor has no standing to list another
    # user's session_rel_user rows; admin sees the real count.
    Then the user "parkur01" should be subscribed to at least 4 sessions

    # ---- SESSION LIST ----
    # The Behat source's own next check ("follow 'Present session', should
    # see 'general coach Teacher Teacher'") is skipped here: "Present
    # session" belongs to specialCase1Sessions.feature, which — unlike the
    # Behat source's original, run-everything-together assumption — is a
    # separate, self-contained file that deletes its own session at the end
    # of its own Teardown scenario. Depending on another file's un-torn-down
    # data would break this file whenever specialCase1Sessions.feature is
    # (correctly) run and cleaned up on its own.
    When I am on "/admin/session-list"
    And I wait for the page to be loaded
    Then I should see "Users"
    And I should see "Session Status"

    # ---- "LOG IN AS" TUTEUR_EN ----
    # span.mdi-account-key — confirmed live as the current "log in as this
    # user" admin action (same feature CLAUDE.md's own CSRF notes reference
    # as "login_as"), still icon-only with no title text, hence the raw
    # class selector rather than a `[title=...]` one.
    When I am on "/admin/user-list?keyword=tuteur_en"
    And I wait for the page to be loaded
    And I click the "span.mdi-account-key" icon in the row for "tuteur_en"
    And I wait for the page to be loaded

    # ---- ACCOUNT HOME PAGE ----
    When I am on "/account/home"
    And I wait for the page to be loaded
    Then I should see "Tuteur Anglais"

    # ---- LOGOUT AND LOGIN BACK AS ADMIN ----
    Given I am not logged
    And I am a platform administrator
    And I wait for the page to be loaded

    # ---- TC FOLLOW-UP ----
    # "General Coaches planning" — the Behat source's own literal link text
    # — has been renamed to "General tutor planning" (confirmed live: the
    # full "Available reports" list has no "Coaches" entry at all anymore,
    # only "tutor"/"tutors" ones throughout). Its own former filter-icon
    # step is likewise gone: Start/End date fields plus Search/Reset are
    # always visible now, no icon needed to reveal them.
    When I am on "/main/my_space/index.php"
    And I wait for the page to be loaded
    And I click the "i.mdi-star-outline" element
    And I wait for the page content to settle
    And I follow "General tutor planning"
    And I wait for the page to be loaded
    Then I should see "General tutor planning"
    And I should see "Sessions"

    # NOT PORTED — the Behat source's own remaining steps here (create a
    # "temptest" session via /admin/session-list's own "Add" wizard, delete
    # it again) exercise the exact same session_add.php mechanism this
    # suite's specialCase1Sessions.feature already covers end to end
    # (confirmed live: /admin/session-list's "Add session" link still
    # points at session_add.php, not a separate modern wizard — the Behat
    # source's own "em.mdi-arrow-right"/"em.mdi-check" 2-step flow no
    # longer exists). Re-testing the identical mechanism here under a
    # different session name would be duplicate coverage, not new
    # validation.

    # ---- SOCIAL NETWORK ----
    When I am on "/social"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Tuteur_fr assigns a skill and parkur01 is notified
    # Covers the Behat source's "skill assignment" and "learner
    # notification" portions of "Tuteur_fr visits student report and sends
    # legal agreement". Its own remaining portion — parkur01 logging in,
    # accepting the terms of use, then taking two exercises inside "LP
    # Test" reached via "Present session" / course id 15 via a legacy
    # `<iframe id="content_name">` runtime — is NOT ported: that course,
    # session and learning path all belong to
    # specialCase1Sessions.feature's own self-contained, self-cleaning
    # world (confirmed gone once that file's own Teardown scenario runs),
    # and the iframe-based exercise RUNTIME itself looks stale on top of
    # that — every exercise interaction verified live elsewhere in this
    # whole porting effort (specialCase1Sessions.feature's own question
    # creation, this file's own diagnostic forms) is a plain in-page Vue
    # view now, never an iframe. Re-creating a whole course+session+LP here
    # just to re-prove "a student can answer a question" would duplicate
    # coverage that already exists, on a mechanism this box shows no
    # evidence still exists.
    Given I am not logged
    And I am logged as "tuteur_fr" with password "TuteurFr01!"
    And I wait for the page to be loaded
    And I resolve the user id for "parkur01"

    # ---- OPEN SKILLS PANEL ----
    # /main/skills/assign.php?user=<id> — reached live via the same
    # "i.mdi-shield-star" icon on myStudents.php the Behat source itself
    # used, still exactly that icon.
    When I am on "/main/my_space/myStudents.php?student=USER_ID" with the resolved user id
    And I wait for the page to be loaded
    And I click the "i.mdi-shield-star" element
    And I wait for the page to be loaded
    Then I should see "Assigner la compétence"

    # ---- SELECT A SKILL ----
    # "NewSkill" (the Behat source's own literal option) no longer exists —
    # confirmed live: "skill" is now the top of a 4-level cascading
    # picker (category -> capacité -> dimension -> a single terminal
    # leaf), each level a plain native <select> whose 'change' event
    # triggers a full page reload carrying the choice made so far in its
    # own query string (?current=sub_skill_id_N&sub_skill_list=...). Picks
    # one concrete, real leaf skill instead of a fixture name that was
    # never going to exist on this platform.
    And I select "Compétences linguistiques" from "skill"
    And I wait for the page to be loaded
    And I select "Savoir agir dans la langue cible" from "sub_skill_id_1"
    And I wait for the page to be loaded
    And I select "Se faire comprendre au quotidien" from "sub_skill_id_2"
    And I wait for the page to be loaded
    And I fill in "argumentation" with "test skills"
    And I press "assign_skill_save"
    And I wait for the page to be loaded
    Then I should not see an error

    # ---- LEARNER NOTIFICATION ----
    Given I am not logged
    And I am logged as "parkur01" with password "Parkur01Test!"
    And I wait for the page to be loaded
    When I am on "/resources/messages"
    And I wait for the page to be loaded
    Then I should see "vous avez obtenu une nouvelle compétence"

  Scenario: Tuteur deletes legal agreement and generates document
    # "Delete legal agreement" only appears once the learner has actually
    # ACCEPTED the agreement — confirmed live: myStudents.php showed
    # "Envoyer le contrat d'apprentissage" (Send) both before AND right
    # after the tutor sends it in the earlier scenario, and only switches
    # to "Supprimer le contrat d'apprentissage" (Delete) once parkur01 goes
    # through this exact acceptance flow. This is the missing half of that
    # earlier scenario's own skipped portion (see its own header comment):
    # accepting via tc.php needs no course/session/LP at all, unlike the
    # exercise-taking part that came after it in the Behat source.
    Given I am not logged
    And I am logged as "parkur01" with password "Parkur01Test!"
    And I wait for the page to be loaded
    When I am on "/main/auth/tc.php"
    And I wait for the page to be loaded
    And I press "Accept Terms and Conditions"
    And I wait for the page to be loaded
    Then I should not see an error

    Given I am not logged
    And I am logged as "tuteur_fr" with password "TuteurFr01!"
    And I wait for the page to be loaded
    And I resolve the user id for "parkur01"
    When I am on "/main/my_space/myStudents.php?student=USER_ID" with the resolved user id
    And I wait for the page to be loaded
    Then I should see "Supprimer le contrat d’apprentissage"

    # "Générer" — confirmed live as the Certificate row's own action
    # (generate_certificate), matching the Behat source's own "Generate"
    # exactly, just under its current French label. Not navigated to via
    # "I am on ... with the resolved user id": confirmed live it's a real
    # file download, not a page navigation — that step's own page.goto()
    # throws "Download is starting" rather than landing anywhere, so this
    # uses the download-aware variant instead, which treats that exact
    # outcome as success.
    When I trigger the download at "/main/my_space/myStudents.php?action=generate_certificate&student=USER_ID&cid=0&course=" with the resolved user id
    Then I should not see an error

  Scenario: Teardown — delete the tutors and parkur01
    # parkur01 IS deleted here, not in specialCase2TeacherTools.feature even
    # though that file is the last one to actually USE the account — see
    # this file's own header comment for why (actual alphabetical spec-file
    # execution order, not narrative order, decides teardown ownership).
    # Same delete-icon-then-confirm sequence specialCase1Sessions.feature's
    # own teardown already uses.
    Given I am a platform administrator
    When I am on "/admin/user-list?keyword=tuteur_fr"
    And I wait for the page to be loaded
    And I click the "[title='Delete']" icon in the row for "tuteur.fr@example.test"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "tuteur.fr@example.test"

    When I am on "/admin/user-list?keyword=tuteur_en"
    And I wait for the page to be loaded
    And I click the "[title='Delete']" icon in the row for "tuteur.en@example.test"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "tuteur.en@example.test"

    When I am on "/admin/user-list?keyword=parkur01"
    And I wait for the page to be loaded
    And I click the "[title='Delete']" icon in the row for "parkur01@example.test"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "parkur01@example.test"

    # A soft-delete alone (the platform's own default "Delete" action above)
    # leaves the username/email PERMANENTLY reserved — confirmed live across
    # this whole suite's own repeated dev-iteration runs against this shared
    # box: a real, documented platform behaviour (`USER_SOFT_DELETED = -2`,
    # this repo's own Doctrine/DQL notes), not something specific to this
    # box or a flaw in the "Delete" step above. A real single CI run would
    # never hit it (it only recreates a username that never existed before),
    # but this suite's OWN re-runnability — and this shared box's — depends
    # on truly freeing it. UserList.vue's "Deleted users" tab
    # (?view=deleted) has a real "Delete permanently" action for exactly
    # this; confirmed live via source (icon "delete-forever").
    When I am on "/admin/user-list?view=deleted&keyword=tuteur_fr"
    And I wait for the page to be loaded
    And I click the "[title='Delete permanently']" icon in the row for "tuteur.fr@example.test"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "tuteur.fr@example.test"

    When I am on "/admin/user-list?view=deleted&keyword=tuteur_en"
    And I wait for the page to be loaded
    And I click the "[title='Delete permanently']" icon in the row for "tuteur.en@example.test"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "tuteur.en@example.test"

    When I am on "/admin/user-list?view=deleted&keyword=parkur01"
    And I wait for the page to be loaded
    And I click the "[title='Delete permanently']" icon in the row for "parkur01@example.test"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "parkur01@example.test"
