@common @admin
Feature: Replace or hide the page backgrounds of the active visual theme
  Some pages show an image of the theme at their bottom (My courses, the administration, the
  sessions management, the social network). From the colors page, an administrator replaces it
  with an image of their own, hides it, and restores the image the theme ships.

  # Verified live on the colors page: each slot is a card whose file field is a BaseFileUpload
  # (a hidden <input type="file" name="page_background_<slot>">, so the scenarios wait for the
  # upload button rather than for it) and whose actions are BaseButtons
  # named page_background_{upload|hide|show|restore}_<slot>, because every card repeats the same
  # labels. The scenario works on the "Administration" image (cityscape): the colors page is itself
  # an administration page, so its own .app-main shows the effect of each action without leaving
  # it, and the inline background-image style is a durable signal, unlike the "Saved" toast.
  # Restoring asks for a PrimeVue confirmation ("Yes"). The scenario ends on the image of the
  # theme, leaving the theme directory as it found it.
  # The cards render once the theme's backgrounds are listed, which came last among the requests
  # of the page on the dev instance (about 15 seconds), and each change took up to 12 seconds
  # there: hence the long wait for the durable signal of each action, and the @slow-scenario budget.

  @slow-scenario
  Scenario: An administrator replaces, hides, shows and restores the image of the administration pages
    Given I am a platform administrator
    And I am on "/admin/configuration/colors"
    When I wait up to 60 seconds for the element "button[name='page_background_upload_cityscape']" to appear
    Then I should see "Page backgrounds"
    And I should not see the "button[name='page_background_restore_cityscape']" element
    When I attach the file "tests/playwright/fixtures/page-background.png" to "page_background_cityscape"
    And I click the "button[name='page_background_upload_cityscape']" element
    Then I wait up to 60 seconds for the element "button[name='page_background_restore_cityscape']" to appear
    And I should see the ".app-main[style*='bg-cityscape-custom.png']" element
    # The uploaded image is saved for every administration page, not only for this screen
    When I am on "/admin"
    Then I wait up to 60 seconds for the element ".app-main[style*='bg-cityscape-custom.png']" to appear
    When I am on "/admin/configuration/colors"
    And I wait up to 60 seconds for the element "button[name='page_background_hide_cityscape']" to appear
    And I click the "button[name='page_background_hide_cityscape']" element
    Then I wait up to 60 seconds for the element "button[name='page_background_show_cityscape']" to appear
    And I should not see the ".app-main[style*='bg-cityscape']" element
    When I click the "button[name='page_background_show_cityscape']" element
    Then I wait up to 60 seconds for the element "button[name='page_background_hide_cityscape']" to appear
    # Hiding kept the uploaded image: showing it again brings it back, not the image of the theme
    And I should see the ".app-main[style*='bg-cityscape-custom.png']" element
    When I click the "button[name='page_background_restore_cityscape']" element
    And I press "Yes"
    Then I wait up to 60 seconds for the element ".app-main[style*='images/bg-cityscape.png']" to appear
    And I should not see the "button[name='page_background_restore_cityscape']" element

  Scenario: A teacher cannot change the page backgrounds
    Given I am a teacher
    When I am on "/admin/configuration/colors"
    Then I should not see "Page backgrounds"
    And I should not see the "button[name='page_background_upload_cityscape']" element
