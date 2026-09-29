Feature: Multi URLs admin dashboard
  In order to oversee a multi-URL Chamilo install
  As a global administrator
  I want to see a single dashboard listing every URL with its counts and admins

  # Read-only MVP for issue #8903: lists every AccessUrl with user/course/
  # session counts and assigned admins, plus install-wide general information.
  # Analytics, login/intrusion monitoring and export are deferred follow-ups,
  # not covered here. Gated by ROLE_GLOBAL_ADMIN, not the weaker ROLE_ADMIN.

  # The installer writes access_url.id=1 as http://localhost/ (the host the
  # web installer was browsed on). Admins then change that row to the real
  # public URL via /admin/urls/manage. This file's detail-page scenarios
  # assert the displayed URL, so that change has to happen here, once,
  # before any of those assertions — otherwise they look for
  # http://my.chamilo.net/ (or whatever BASE_URL is) and time out on the
  # installer's leftover http://localhost/. Idempotent if already updated.
  Scenario: The default access URL is the current site URL
    Given I am a platform administrator
    And I set the default access URL to the current site URL
    And I am on "/admin/urls"
    And wait for the page to be loaded
    Then I should see the current access URL
    And I should not see an error

  Scenario: The dashboard lists at least one URL
    Given I am a platform administrator
    And I am on "/admin/urls"
    And wait for the page to be loaded
    Then I should see "Multi URLs"
    And I should not see "No results found"
    And I should not see an error

  Scenario: Administrators are attributed per URL
    # "Users"/"Courses"/"Sessions" are deliberately not asserted here: they
    # also appear (hidden) in the admin sidebar's own submenus, so a bare
    # getByText match is ambiguous and would verify the wrong element. The
    # column headers already need to render for "John Doe" to be visible at
    # all, so checking the actual per-URL admin attribution is both the
    # meaningful check and the one that avoids the collision.
    Given I am a platform administrator
    And I am on "/admin/urls"
    And wait for the page to be loaded
    Then I should see "Administrators"
    And I should see "John Doe"

  Scenario: General information panel is visible
    Given I am a platform administrator
    And I am on "/admin/urls"
    And wait for the page to be loaded
    Then I should see "General information"
    And I should see "Installed version"
    And I should see "PHP version"

  Scenario: Logins chart is visible with a date range selector
    # Install-wide, not per-URL: track_e_login carries no access_url_id, so
    # unlike the rest of this page this chart is intentionally not scoped by
    # URL (see AccessUrlListController::logins()). Every login row counts
    # regardless of session duration, per this feature's own requirement.
    Given I am a platform administrator
    And I am on "/admin/urls"
    And wait for the page to be loaded
    Then I should see "Logins"
    And I should see "From"
    And I should see "To"

  Scenario: Reachable from the admin panel
    Given I am a platform administrator
    And I am on "/admin"
    And wait for the page to be loaded
    And I follow "Multi URLs"
    And wait for the page to be loaded
    Then the URL should contain "/admin/urls"
    And I should not see an error

  Scenario: A teacher cannot access the dashboard
    Given I am a teacher
    And I am on "/admin/urls"
    And wait for the page to be loaded
    Then I should not see "General information"

  Scenario: The legacy CRUD is reachable from the dashboard
    # "Configure multiple access URL" (create/edit URLs, assign users/courses/
    # sessions — not replaced by this MVP) has no entry of its own in /admin
    # anymore; this button is now its only entry point.
    Given I am a platform administrator
    And I am on "/admin/urls"
    And wait for the page to be loaded
    And I follow "Configure multiple access URL"
    And wait for the page to be loaded
    Then I should not see an error

  Scenario: User directory shows each user's URL attribution
    # Login frequency / time-spent metrics are not covered here: neither is
    # stored per URL anywhere in the codebase (see issue #8903 follow-up
    # scope), so only the URL membership itself — which IS differentiable —
    # is shown.
    Given I am a platform administrator
    And I am on "/admin/urls"
    And wait for the page to be loaded
    Then I should see "User directory"
    And I should see "Username"
    And I should see "URLs"

  Scenario: Course directory shows each course's URL distribution
    # Comparative usage statistics are not covered here for the same reason:
    # course access/activity tracking is not stored per URL anywhere in the
    # codebase, so only URL membership is shown.
    Given I am a platform administrator
    And I am on "/admin/urls"
    And wait for the page to be loaded
    Then I should see "Course directory"
    And I should see "Code"

  Scenario: A user's info modal shows their email and URL attribution
    # The install has 61 users sorted by lastname, so "John Doe" is not on
    # the User directory's default first page — searching narrows it down
    # to guarantee the row (and its Information icon) are actually visible.
    #
    # Email is actionInstall.feature's own emailForm value (admin@example.com),
    # NOT the older installer default webmaster@localhost.localdomain this
    # file originally asserted — that string is never written by the current
    # install scenario, so a real CI run timed out looking for it while the
    # row already showed admin@example.com.
    Given I am a platform administrator
    And I am on "/admin/urls"
    And wait for the page to be loaded
    Then I should see "Email"
    And I fill in "usersSearch" with "admin"
    And I press "Search"
    Then I should see "admin@example.com"
    And I click the "button[aria-label='Information']" icon in the row for "John Doe"
    Then I should see "User details"
    And I should see "admin@example.com"

  Scenario: A course's info modal shows its URL distribution
    Given I am a platform administrator
    And I am on "/admin/urls"
    And wait for the page to be loaded
    And I click the "button[aria-label='Information']" icon in the row for "AIACT"
    Then I should see "Course details"
    And I should see "AIACT"

  Scenario: The View details icon opens the per-URL user detail page
    # A second hostname (your.chamilo.net) is not created by the installer
    # and is not added here: enabling multiple URLs would leak into every
    # other parallel worker (users/courses become URL-scoped). The detail
    # page is still asserted against the (now-updated) default URL.
    Given I am a platform administrator
    And I am on "/admin/urls"
    And wait for the page to be loaded
    And I fill in "usersSearch" with "admin"
    And I press "Search"
    And I click the "a[title='View details']" icon in the row for "John Doe"
    And wait for the page to be loaded
    Then the URL should contain "/admin/urls/users/"
    And I should see "User details"
    And I should see "John Doe"
    And I should see the current access URL
    And I should see "AI Act"
    And I should not see an error

  Scenario: The View details icon opens the per-course detail page
    Given I am a platform administrator
    And I am on "/admin/urls"
    And wait for the page to be loaded
    And I click the "a[title='View details']" icon in the row for "AIACT"
    And wait for the page to be loaded
    Then the URL should contain "/admin/urls/courses/"
    And I should see "Course details"
    And I should see "AI Act"
    And I should see the current access URL
    And I should see "Direct enrollment belongs to the course as a whole"
    And I should not see an error

  Scenario: A teacher cannot access the course detail page
    Given I am a teacher
    And I am on "/admin/urls/courses/1"
    And wait for the page to be loaded
    Then I should not see "Course details"

  Scenario: A teacher cannot access the user detail page
    Given I am a teacher
    And I am on "/admin/urls/users/1"
    And wait for the page to be loaded
    Then I should not see "User details"
