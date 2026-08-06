# Ported from tests/behat/features/extraFieldUser.feature with drift fixes
# confirmed against ExtraField::return_form() (extra_field.lib.php):
#
# - Name field is `display_text`, not the old `user_field_display_text`
#   (same 1.11.x→2.x rename class as courseCategory's name→title).
# - Field-type <select> is name=`value_type` with id=`field_type`. Behat
#   targeted `#value_type` via a Bootstrap selectpicker helper
#   (`selectpicker('val', '1')` for FIELD_TYPE_TEXT). selectpicker is no
#   longer required here: Playwright's selectOption() works on the native
#   <select>, and the option label is get_lang('Text') — so the existing
#   "I select … from …" step is enough.
# - Submit button is still name="submit" (addButtonCreate → "Add" label);
#   pressing "submit" resolves via the name tier of pressButton().
# - Success used to assert "Item added" (a Display::addFlash confirmation).
#   That flash is stored in the Symfony flash bag and surfaced as a PrimeVue
#   toast via #app[data-flashes], but the legacy header()+exit redirect on
#   this page does not reliably deliver the bag to the next request (trace
#   confirmed data-flashes="[]" after a successful save that did create the
#   row). Asserting the redirect itself is the durable signal: save() only
#   Location-redirects to the list (no action=add) on success; validation
#   failure re-renders the add form and keeps action=add in the URL.
# - Scenario is not self-cleaning (leaves variable `behat_extra_field`
#   behind) — fine on fresh CI; hard-delete that row before a local re-run.
Feature: User extra fields
    In order to use the user extra fields
    As an administrator
    I need to be able to create an extra field

  Scenario: Create a text extra field
      Given I am a platform administrator
      And I am on "/main/admin/extra_fields.php?type=user&action=add"
      And I wait for the page to be loaded
      When I fill in the following:
          | display_text | Behat extra field |
          | variable     | behat_extra_field |
      And I select "Text" from "value_type"
      And I press "submit"
      And I wait for the page to be loaded
      Then the URL should not contain "action=add"
      And I should not see an error
