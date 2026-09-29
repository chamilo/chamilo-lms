# Ported from tests/behat/features/adminFillCourses.feature.
#
# - "Given I am logged as 'admin'" -> "Given I am a platform administrator"
#   (same underlying login, matches this suite's own convention).
# - The original clicks "button.btn--primary" to submit — confirmed via a
#   real DOM/code read this is actually subscribe_user2course.php's "Filter"
#   button (onclick="validate_filter()", which resubmits the SAME form with
#   form_sent=0). Server-side, `if (isset($_POST['form_sent']) &&
#   $_POST['form_sent'])` treats the string "0" as falsy, so that submission
#   never reaches the actual subscription logic at all — clicking it can
#   never produce "The selected users are subscribed to the selected
#   course". The real submit is the OTHER button on the same form
#   (`<button type="submit" class="btn btn--success">`, hidden field
#   form_sent defaults to "1"), confirmed live to trigger the subscription.
#   Uses "I press 'Add to the course(s)'" (that button's actual visible
#   text) instead.
# - "I select 'ywarnier' from 'UserList[]'" -> full label "Warnier Yannick
#   (ywarnier)": each `<option>`'s value attribute is the numeric user id,
#   not the username, and its visible label is "Lastname Firstname
#   (username)" (subscribe_user2course.php) — "ywarnier" alone is only a
#   substring of that label, never the whole thing, and Playwright's
#   selectOption({label}) requires an exact (trimmed) match. Confirmed live
#   that the bare original string times out finding "some options" while
#   the full label selects correctly.
Feature: Admin fill courses and subscribe users

  Background:
    Given I am a platform administrator

  Scenario: Admin fills courses then subscribes a user to a course with long waits
    Given I am on "/main/admin/filler.php?fill=courses"
    When wait very long for the page to be loaded
    When I am on "/main/admin/subscribe_user2course.php"
    And wait very long for the page to be loaded
    And I select "Warnier Yannick (ywarnier)" from "UserList[]"
    And I select "(SOLARSYSTEM) Our solar system" from "CourseList[]"
    When I press "Add to the course(s)"
    When wait very long for the page to be loaded
    Then I should see "The selected users are subscribed to the selected course"
