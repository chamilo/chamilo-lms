# New — not ported from Behat (translate_html's fallback chain didn't exist
# yet when the Behat suite was written).
#
# translate_html (assets/js/translatehtml.js) lets a course description mark
# up several language variants inside one HTML blob
# (<span class="mce-translatehtml" lang="...">...</span>), then hides every
# block except the one matching the viewer's own locale. Before this fix, if
# NO block matched the viewer's locale, every block stayed hidden and the
# content rendered blank. It now falls back to the course language, then the
# platform default language, before giving up.
#
# Both scenarios below deliberately avoid ever tagging a block with the real
# admin viewer's own interface locale: course/platform languages are set to
# German ("Deutsch") / Spanish ("Español"), and the decoy block uses the
# nonsense tag "xx" — none of which should ever match a real viewer locale on
# this instance. So whichever block ends up visible got there via the
# course-language or platform-default fallback tier being tested, not an
# accidental exact match on the viewer's own locale. This intentionally does
# NOT cover fallback-priority ordering (does an exact viewer-locale match
# still win over the course-language fallback?) — that would need a second
# test user with a controlled interface locale; deferred as a follow-up.
#
# language.platform_language is mutated by the second scenario and restored
# by registerSettingsGuard() (tests/playwright/steps/common.steps.ts), same
# convention as every other feature file in this suite that touches a
# platform setting — see that file's own comment for why.
#
# Follows "Kursbeschreibung" (German for "Course description"), not the
# English link text: confirmed live that setting a course's own language
# away from the admin's session language (the whole point of these
# scenarios — both use "Deutsch" as the course language) also renders that
# course's own tool menu in German, so the English link text is never on the
# page at all. assets/locales/de.json's "Course description" key confirms
# the exact rendered label.
@settings-translateHtml
Feature: translate_html language fallback
  In order for multi-language course content to never render blank
  As a viewer with no matching language block
  I want translate_html to fall back to the course language, then the platform default language

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded
    Given I am on "/admin/settings/editor"
    And I wait for the page to be loaded
    And I select the value "true" from "form_translate_html"
    And I press "Save settings"
    And I wait for the page to be loaded

  Scenario: Falls back to the course language when the viewer's locale has no matching block
    Given I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "TrHtmlDe"
    And I select "Deutsch" from "course_language"
    And I press "submit"
    And wait very long for the page to be loaded

    Given I am on course "TrHtmlDe" homepage
    And I wait for the page to be loaded
    And I follow "Kursbeschreibung"
    And I wait for the page to be loaded
    And I click the "span.mdi-image-text" element
    And I fill in "course_description_title" with "Fallback test"
    And I fill in tinymce field "course_description_content" with "<span class=\"mce-translatehtml\" lang=\"de\">GERMANFALLBACKTEXT</span><span class=\"mce-translatehtml\" lang=\"xx\">DECOYTEXT</span>"
    And I press "save"
    And I wait for the page to be loaded
    Then I should see "GERMANFALLBACKTEXT"
    And I should not see "DECOYTEXT"

    Given I am on "/admin/course-list?keyword=TrHtmlDe"
    And I wait for the page to be loaded
    Then I click the "[title='Delete']" icon in the row for "TrHtmlDe"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "TrHtmlDe"

  Scenario: Falls back to the platform default language when neither the viewer nor the course language matches
    Given I am on "/admin/settings/language"
    And I wait for the page to be loaded
    And I select "Español" from "form_platform_language"
    And I press "Save settings"
    And I wait for the page to be loaded

    Given I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "TrHtmlEs"
    And I select "Deutsch" from "course_language"
    And I press "submit"
    And wait very long for the page to be loaded

    Given I am on course "TrHtmlEs" homepage
    And I wait for the page to be loaded
    And I follow "Kursbeschreibung"
    And I wait for the page to be loaded
    And I click the "span.mdi-image-text" element
    And I fill in "course_description_title" with "Fallback test"
    And I fill in tinymce field "course_description_content" with "<span class=\"mce-translatehtml\" lang=\"es\">SPANISHFALLBACKTEXT</span><span class=\"mce-translatehtml\" lang=\"xx\">DECOYTEXT</span>"
    And I press "save"
    And I wait for the page to be loaded
    Then I should see "SPANISHFALLBACKTEXT"
    And I should not see "DECOYTEXT"

    Given I am on "/admin/course-list?keyword=TrHtmlEs"
    And I wait for the page to be loaded
    Then I click the "[title='Delete']" icon in the row for "TrHtmlEs"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "TrHtmlEs"
