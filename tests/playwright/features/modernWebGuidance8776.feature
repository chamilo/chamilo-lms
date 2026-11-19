@modern-web-guidance-8776
Feature: Modern Web Guidance browser checks for shared Chamilo interfaces
  The guidance review must verify real browser behavior before proposing broad modernization
  All scenarios are read-only and must not create or modify application records

  @mwg8776-forms
  Scenario: Invalid social group creation reports accessible errors without saving
    Given I am a platform administrator
    And I am on "/resources/usergroups"
    When I open the MWG8776 group creation dialog
    And I attempt to submit the empty MWG8776 group form
    Then the MWG8776 group form errors must be visibly associated with their controls

  @mwg8776-required
  Scenario: Required social group fields expose the requirement programmatically
    Given I am a platform administrator
    And I am on "/resources/usergroups"
    When I open the MWG8776 group creation dialog
    Then the MWG8776 required group name must be announced to assistive technologies

  @mwg8776-dialog
  Scenario: The PrimeVue group creation dialog manages focus and Escape
    Given I am a platform administrator
    And I am on "/resources/usergroups"
    When I open the MWG8776 group creation dialog
    Then the MWG8776 group dialog must manage focus and Escape

  @mwg8776-responsive
  Scenario: The social group form fits a narrow mobile viewport
    Given I am a platform administrator
    And I am on "/resources/usergroups"
    When I use a narrow MWG8776 viewport
    And I open the MWG8776 group creation dialog
    Then the MWG8776 group dialog must fit the viewport width

  @mwg8776-social
  Scenario Outline: The social search form has accessible labels for authenticated roles
    Given I am <role>
    And I am on "/social/search"
    Then the MWG8776 social search controls must be labeled
    And I record MWG8776 navigation timings

    Examples:
      | role                     |
      | a platform administrator |
      | a teacher                |
      | a student                |

  @mwg8776-custom-dialog
  Scenario: The social search message overlay must behave as an accessible dialog
    Given I am a platform administrator
    And I am on "/social/search?query=mmosquera&type=user"
    When I open the MWG8776 social search message overlay
    Then the MWG8776 social search message overlay must manage semantics and keyboard dismissal
