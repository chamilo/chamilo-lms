# Each navigable administration block has its own landing page and breadcrumb ancestor.
Feature: Administration block navigation and breadcrumbs
  As an administrator
  I want a working landing page for each administration block
  So that breadcrumbs never lead to an empty section

  Scenario: The Security block title opens a standalone page with its existing tools
    Given I am a platform administrator
    When I am on "/admin"
    And I wait for the element ".block-admin-security .admin-block__section-link" to appear
    And I click the ".block-admin-security .admin-block__section-link" element
    And I wait for the element ".block-admin-security" to appear
    Then I should see the ".app-breadcrumb a[href='/admin']" element
    And I should see the ".block-admin-security a[href='/admin/security/password-strength']" element

  Scenario Outline: Security reports link back to their landing page
    Given I am a platform administrator
    When I am on "<path>"
    And I wait for the element ".app-breadcrumb" to appear
    Then I should see the ".app-breadcrumb a[href='/admin/security']" element

    Examples:
      | path |
      | /admin/security/password-strength |
      | /admin/security/login-attempts |
      | /admin/security/simple-ids |
      | /admin/security/file-integrity |

  Scenario: User creation includes the User management landing page
    Given I am a platform administrator
    When I am on "/admin/user-add"
    And I wait for the element ".app-breadcrumb" to appear
    Then I should see the ".app-breadcrumb a[href='/admin/users']" element

  Scenario: The System status page links back to the System landing page
    Given I am a platform administrator
    When I am on "/admin/system-status"
    And I wait for the element ".app-breadcrumb" to appear
    Then I should see the ".app-breadcrumb a[href='/admin/system']" element

  Scenario: The Rooms list links back to the Rooms landing page
    Given I am a platform administrator
    When I am on "/resources/rooms"
    And I wait for the element ".app-breadcrumb" to appear
    Then I should see the ".app-breadcrumb a[href='/admin/rooms']" element

  Scenario: Non-administrators cannot open a protected administration block
    Given I am not logged
    And I am a student
    When I am on "/admin/security"
    And I wait for the page to be loaded
    Then the page path should not start with "/admin/security"
    And I should not see the ".block-admin-security" element

  # Legacy admin tools preserve their own PHP breadcrumbs. The new section
  # ancestor must be present without changing the tool's form or data URL.
  Scenario Outline: Legacy admin tools link back to the owning block
    Given I am a platform administrator
    When I am on "<path>"
    And I wait for the element ".app-breadcrumb" to appear
    Then I should see the ".app-breadcrumb a[href$='<section>']" element

    Examples:
      | path | section |
      | /main/admin/course_export.php | /admin/courses |
      | /main/admin/user_export.php | /admin/users |
      | /main/admin/languages.php | /admin/platform |
      | /main/mail_template/list.php | /admin/platform |
      | /main/admin/reports_catalog.php | /admin/analytics |
      | /main/admin/activities_audit.php | /admin/security |

  Scenario Outline: Extra fields choose the owning block from the validated type
    Given I am a platform administrator
    When I am on "<path>"
    And I wait for the element ".app-breadcrumb" to appear
    Then I should see the ".app-breadcrumb a[href$='<section>']" element

    Examples:
      | path | section |
      | /main/admin/extra_fields.php?type=user | /admin/users |
      | /main/admin/extra_fields.php?type=course | /admin/courses |
      | /main/admin/extra_fields.php?type=session | /admin/sessions |

  Scenario: A legacy administration breadcrumb opens the section landing page
    Given I am a platform administrator
    When I am on "/main/admin/course_export.php"
    And I wait for the element ".app-breadcrumb a[href$='/admin/courses']" to appear
    And I click the ".app-breadcrumb a[href$='/admin/courses']" element
    And I wait for the element ".block-admin-courses" to appear
    Then I should see the ".block-admin-courses" element


  # These Symfony/Twig pages set the legacy window.breadcrumb before Vue boots.
  Scenario Outline: Symfony administration tools link back to their owning section
    Given I am a platform administrator
    When I am on "<path>"
    And I wait for the element ".app-breadcrumb" to appear
    Then I should see the ".app-breadcrumb a[href='<section>']" element

    Examples:
      | path | section |
      | /admin/files_info | /admin/system |
      | /admin/resources_info | /admin/system |
      | /admin/cleanup-temp-uploads | /admin/system |
      | /admin/email_tester | /admin/system |
      | /admin/settings/platform | /admin/platform |
      | /admin/settings/search_settings | /admin/platform |

  # No admin breadcrumb is forced onto a shared personal or course page.
  Scenario: Pages opened from the Platform block have a return breadcrumb
    Given I am a platform administrator
    When I am on "/resources/pages?admin_origin=platform"
    And I wait for the element ".app-breadcrumb" to appear
    Then I should see the ".app-breadcrumb a[href='/admin/platform']" element

  Scenario: The global agenda opened from the Platform block has a return breadcrumb
    Given I am a platform administrator
    When I am on "/resources/ccalendarevent?type=global&admin_origin=platform"
    And I wait for the element ".app-breadcrumb" to appear
    Then I should see the ".app-breadcrumb a[href='/admin/platform']" element
