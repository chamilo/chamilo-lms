@slow-scenario
Feature: Learner report skills
  In order to see where a learner's skills sit in the skill tree
  As a platform administrator
  I need the learner report to lay out the acquired skills as the legacy report did

  # acostea is subscribed to TEMP by course_user_registration.feature.
  # The layout follows two settings, both back to their defaults at the end of the file:
  # an empty skill.skill_levels_names lists the acquired skills only; otherwise they are placed in
  # the skill tree, as columns when skill.skills_hierarchical_view_in_user_tracking is Yes, and as one
  # heading per branch when it is No. The parent the learner has not acquired is faded.
  # Skills cannot be deleted, only disabled: the last scenario disables the two it creates.
  # The tree of the learner course report reads the skills acquired in the course, which only the
  # gradebook grants: PHPUnit covers it (GlobalReportingLearnerDetailTest).
  # The skill assignment form, the report and the badge load their data after the page itself: every
  # step that acts on them first waits for an element only the loaded data renders. On a development
  # server these API calls queue on the session and can take a minute, hence @slow-scenario.

  Scenario: Admin creates a parent skill
    Given I am a platform administrator
    And I am on "main/skills/skill_create.php"
    And wait very long for the page to be loaded
    When I fill in the following:
      | title      | Tree parent skill |
      | short_code | trp               |
    And I press "submit"
    # The creation redirects to the skills wheel, whose 3D canvas a headless browser may never finish
    And I am on "main/skills/skill_list.php"
    Then I wait up to 90 seconds for the element "text=Tree parent skill" to appear

  Scenario: Admin creates a child skill under it
    Given I am a platform administrator
    And I am on "main/skills/skill_create.php"
    And wait very long for the page to be loaded
    When I fill in the following:
      | title      | Tree child skill |
      | short_code | trc              |
    And I select "Tree parent skill" from "parent_id"
    And I press "submit"
    And I am on "main/skills/skill_list.php"
    Then I wait up to 90 seconds for the element "text=Tree child skill" to appear

  Scenario: Admin assigns the child skill to the learner
    Given I am a platform administrator
    And I resolve the user id for "acostea"
    And I am on "/skill/assign/USER_ID" with the resolved user id
    And I wait up to 90 seconds for the element "text=Andrea Costea" to appear
    When I select the dropdown option "Tree child skill" in "skill-assignment-skill"
    And I fill in "argumentation" with "Listens to the other members"
    And I press "Save"
    Then I wait up to 90 seconds for the element "text=Skill acquired" to appear

  Scenario: Without skill level names, only the acquired skill is listed
    Given I am a platform administrator
    And I resolve the user id for "acostea"
    When I am on "/reporting/learners/USER_ID" with the resolved user id
    And I wait up to 90 seconds for the element "text=Tree child skill" to appear
    Then I should not see the "[data-testid='learner-skill-tree']" element
    And I should not see "Tree parent skill"

  Scenario: Admin names the skill levels
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=skill_levels_names"
    And I wait up to 90 seconds for the element "#form_skill_levels_names" to appear
    When I fill in "form_skill_levels_names" with "{\"1\":\"Basic\"}"
    And I press "Save settings"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: With skill level names, each branch is a heading above its skills
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=skills_hierarchical_view_in_user_tracking"
    And I wait up to 90 seconds for the element "#form_skills_hierarchical_view_in_user_tracking" to appear
    And I select "No" from "form_skills_hierarchical_view_in_user_tracking"
    And I press "Save settings"
    And I wait for the page to be loaded
    And I resolve the user id for "acostea"
    When I am on "/reporting/learners/USER_ID" with the resolved user id
    And I wait up to 90 seconds for the element "[data-testid='learner-skill-tree']" to appear
    Then I should see the "[data-testid='learner-skill-tree'] h3:has-text('Tree parent skill')" element
    And I should see the "[data-testid='learner-skill-tree'] a:has-text('trc')" element

  Scenario: As a hierarchical table, the parent the learner has not acquired is faded
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=skills_hierarchical_view_in_user_tracking"
    And I wait up to 90 seconds for the element "#form_skills_hierarchical_view_in_user_tracking" to appear
    And I select "Yes" from "form_skills_hierarchical_view_in_user_tracking"
    And I press "Save settings"
    And I wait for the page to be loaded
    And I resolve the user id for "acostea"
    When I am on "/reporting/learners/USER_ID" with the resolved user id
    And I wait up to 90 seconds for the element "[data-testid='learner-skill-tree']" to appear
    Then I should see the "[data-testid='learner-skill-tree'] span.opacity-40:has-text('trp')" element
    And I should see the "[data-testid='learner-skill-tree'] a:has-text('trc')" element

  Scenario: Admin restores both settings to their defaults
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=skills_hierarchical_view_in_user_tracking"
    And I wait up to 90 seconds for the element "#form_skills_hierarchical_view_in_user_tracking" to appear
    And I select "No" from "form_skills_hierarchical_view_in_user_tracking"
    And I press "Save settings"
    And I wait for the page to be loaded
    And I am on "/admin/settings/search_settings?keyword=skill_levels_names"
    And I wait up to 90 seconds for the element "#form_skill_levels_names" to appear
    When I fill in "form_skill_levels_names" with ""
    And I press "Save settings"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Admin removes the skill from the learner through its badge
    Given I am a platform administrator
    And I resolve the user id for "acostea"
    And I am on "/reporting/learners/USER_ID" with the resolved user id
    And I wait up to 90 seconds for the element "text=Tree child skill" to appear
    When I click the "a:has-text('Tree child skill')" element
    And I wait up to 90 seconds for the element "a:has-text('Remove')" to appear
    And I follow "Remove"
    And I wait up to 90 seconds for the element "text=Skill acquired" to appear
    And I press "Remove"
    And I press "Yes"
    Then I wait until I no longer see "Skill acquired"

  Scenario: Admin disables both skills
    Given I am a platform administrator
    And I am on "main/skills/skill_list.php"
    And I wait up to 90 seconds for the element "text=Tree child skill" to appear
    When I click the "a[title='Disable']" icon in the row for "Tree child skill"
    And I wait up to 90 seconds for the element "text=Tree parent skill" to appear
    And I click the "a[title='Disable']" icon in the row for "Tree parent skill"
    And I wait for the page to be loaded
    Then I should not see an error
