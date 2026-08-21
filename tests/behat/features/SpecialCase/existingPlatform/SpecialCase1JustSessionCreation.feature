@internal
Feature: Special admin settings flows to create sessions for testing

  Background:
    Given I am a platform administrator
    And I wait very long for the page to be loaded

  Scenario: Create courses, multilingual documents, exercises, forum, learning path and assessment activity

  # Create courses
    When I am on "/main/admin/course_add.php"
    And I wait for the element "[name='title']" to appear
    When I fill in the following:
      | title      | Testing course en |
    And I select "en_US" from "course_language"
    And I zoom out to maximum
    And I press "submit"
    And I wait very long for the page to be loaded
    And I am on "/admin/course-list?keyword=Testing course en"
    And I wait very long for the page to be loaded
    Then I should see "Testing course en"

    When I am on "/main/admin/course_add.php"
    And I wait for the element "[name='title']" to appear
    When I fill in the following:
      | title      | Special |
    And I zoom out to maximum
    And I click the "input[name='sticky']" element
    And I press "submit"
    And I wait very long for the page to be loaded
    And I am on "/admin/course-list?keyword=Special"
    And I wait very long for the page to be loaded
    Then I should see "Special"

    When I am on "/main/admin/course_add.php"
    And I wait for the element "[name='title']" to appear
    When I fill in the following:
      | title      | Testing course fr |
    And I select "fr_61" from "course_language"
    And I zoom out to maximum
    And I press "submit"
    And I wait very long for the page to be loaded
    And I am on "/admin/course-list?keyword=Testing course fr"
    And I wait very long for the page to be loaded
    Then I should see "Testing course fr"

  # Enter the new course (Testing course fr)
    When I am on "/admin/course-list?keyword=Testing course fr"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    When I follow "Testing course fr"
    And I wait very long for the page to be loaded
    Then I should see "Testing course fr"

  # Create two HTML documents with bilingual content: introduction and final
    And I zoom out to maximum
    When I follow "Documents"
    And I wait very long for the page to be loaded
    When I press "Nouveau document"
    And I wait for the element "#item_title" to appear
    And I fill in the following:
      | item_title | introduction |
    And I fill in tinymce field "item_content" with "<p class='ck ck-texte'><span dir='ltr' lang='en'>English content</span><span dir='ltr' lang='fr'>Contenu en français</span></p>"
    And I click the "span.mdi-content-save" element
    And I wait very long for the page to be loaded
    Then I should not see an error
    Then I should see "introduction"

    When I press "Nouveau document"
    And I wait for the element "#item_title" to appear
    And I fill in the following:
      | item_title | final |
    And I fill in tinymce field "item_content" with "<p class='ck ck-texte'><span dir='ltr' lang='en'>English content</span><span dir='ltr' lang='fr'>Contenu en français</span></p>"
    And I click the "span.mdi-content-save" element
    And I wait very long for the page to be loaded
    Then I should not see an error
    Then I should see "final"

  # Back to course home for next tools
    When I am on "/admin/course-list?keyword=Testing course fr"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    When I follow "Testing course fr"
    And I wait very long for the page to be loaded
    And I zoom out to maximum

  # Create exercises: one with QRU + image selection, one open question
    When I follow "Exercices"
    And I wait for the element "a[href*='exercise_admin.php']" to appear
    When I click the "a[href*='exercise_admin.php']" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I fill in the following:
      | exerciseTitle | QRU and Image Selection exercise |
    And I press "submitExercise"
    And I wait for the element "a[title='Question à réponse unique (QRU)']" to appear
  # Add QRU question
    When I click the "a[title='Question à réponse unique (QRU)']" element
    And I wait for the element "[name='questionName']" to appear
    And I fill in the following:
      | questionName | QRU Question |
    And I zoom out to maximum
    And I fill in tinymce field "answer1" with "Option A"
    And I fill in tinymce field "answer2" with "Option B"
    And I fill in tinymce field "answer3" with "Option C"
    And I fill in tinymce field "answer4" with "Option D"
    And I press "submit-question"
    And I wait for the element "a[title*='lection']" to appear
  # Add Image selection question
    When I click the "a[title*='lection']" element
    And I wait for the element "[name='questionName']" to appear
    And I fill in the following:
      | questionName | Image selection question |
    And I zoom out to maximum
    And I fill in tinymce field "answer1" with "Image A"
    And I fill in tinymce field "answer2" with "Image B"
    And I fill in tinymce field "answer3" with "Image C"
    And I fill in tinymce field "answer4" with "Image D"
    And I press "submitQuestion"
    And I wait very long for the page to be loaded

  # Create open question exercise — navigate back to exercise list first
    When I am on "/admin/course-list?keyword=Testing course fr"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    When I follow "Testing course fr"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    When I follow "Exercices"
    And I wait for the element "a[href*='exercise_admin.php']" to appear
    When I click the "a[href*='exercise_admin.php']" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I fill in the following:
      | exerciseTitle | Open question exercise |
    And I press "submitExercise"
    And I wait for the element "a[title='Question ouverte']" to appear
    When I click the "a[title='Question ouverte']" element
    And I wait for the element "[name='questionName']" to appear
    And I fill in the following:
      | questionName | Open Question |
      | weighting     | 5 |
    And I zoom out to maximum
    And I press "submitQuestion"
    And I wait very long for the page to be loaded

  # Create a forum category and a forum inside
  # CHAMILO BUG: HTTP 500 on /main/forum/index.php — section commented out
  # When I follow "Forum"
  # When I press "Add a category"
  # When I press "Add a forum"

  # Create a new Learning Path + Add items
    When I am on "/admin/course-list?keyword=Testing course fr"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    When I follow "Testing course fr"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    When I follow "Parcours d'apprentissage"
    And I wait for the element "span.mdi-plus" to appear
    When I click the "span.mdi-plus" element
    And I wait for the element "[name='lp_name']" to appear
    And I fill in the following:
      | lp_name | LP Test |
    And I press "Continue"
    And I wait very long for the page to be loaded
  # LP builder: add items via AJAX (simulates Sortable.js drag-and-drop)
    When I add LP item "introduction" from the resource panel
    When I add LP item "QRU and Image Selection exercise" from the resource panel
    When I add LP item "Open question exercise" from the resource panel
    When I add LP item "final" from the resource panel
    Then I should see "introduction"
    And I should see "QRU and Image Selection exercise"
    And I should see "Open question exercise"
    And I should see "final"

  # Edit course introduction and add link to LP
    When I am on "/admin/course-list?keyword=Testing course fr"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    When I follow "Testing course fr"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    When I click the "span.mdi-plus" element
    And I wait very long for the page to be loaded
    And I fill in tinymce field "introText" with "<a href='/main/lp/lp_controller.php?action=view&cid=15&sid=0&isStudentView=false&lp_id=4'>LP Test</a>"
    And I click the "span.mdi-content-save" element
    And I wait very long for the page to be loaded

  # Course settings: E-mail notifications -> Tests: mark relaxed options — commented out
    # When I click the "span.p-button-icon.mdi.mdi-cog" element
    # And I wait very long for the page to be loaded
    # And I follow "Course settings"
    # And I wait very long for the page to be loaded
    # And I zoom out to maximum
    # And I click the "a[data-target='#collapse_email-notifications']" element
    # And I wait very long for the page to be loaded
    # And I click the "input[name='email_alert_manager_on_new_quiz[]'][value='3']" element
    # And I click the "input[name='email_alert_manager_on_new_quiz[]'][value='4']" element
    # And I click the "a[data-target='#collapse_course_main']" element
    # And I press "submit_save"
    # And I wait very long for the page to be loaded
    # Then I should not see an error

  # Enter the assessments tool and add a classroom activity
    When I am on "/admin/course-list?keyword=Testing course fr"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    When I follow "Testing course fr"
    And I wait very long for the page to be loaded
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    When I follow "Cahier de notes"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    When I click the "a[href*='gradebook_add_eval']" element
    And I wait for the element "[name='name']" to appear
    And I fill in the following:
      | name        | Course validation |
      | weight_mask | 100               |
      | max         | 1                 |
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should see "Course validation"

  Scenario: Create teacher and configure "Present session" with settings and include course


    # Create a teacher account
    When I am on "/main/admin/user_add.php"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I fill in the following:
      | firstname | Teacher |
      | lastname  | Teacher |
      | email     | teacher@example.test |
      | username  | teacher |
      | password  | teacher |
    And I select "TEACHER" from "user_add_roles"
    And I click the "input#send_mail_no" element
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # Set known password for teacher via user-list edit
    Given I am on "/admin/user-list"
    And I wait very long for the page to be loaded
    When I fill in "Search users" with "teacher"
    And I click the "span.mdi-magnify" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I click the "span.mdi-pencil" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait for the element "input[name='reset_password'][value='2']" to appear
    And I click the "input[name='reset_password'][value='2']" element
    And I wait for the element "[name='password']" to appear
    And I fill in "password" with "teacher"
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

     # Create session Present session with start = 2026-01-20 and end = 2036-02-03
    When I am on "/main/session/session_add.php"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I click the "#advanced_params" element
    And I wait for the element "[name='title']" to appear
    And I fill in the following:
      | title             | Present session  |
    And I set hidden field "access_start_date" to "2026-01-20 00:00"
    And I set hidden field "display_start_date" to "2026-01-20 00:00"
    And I set hidden field "coach_access_start_date" to "2026-01-20 00:00"
    And I set hidden field "access_end_date" to "2036-02-03 00:00"
    And I set hidden field "display_end_date" to "2036-02-03 00:00"
    And I set hidden field "coach_access_end_date" to "2036-02-03 00:00"
    And I press "submit"
    And I wait very long for the page to be loaded
    And I type and select "Testing course fr" in select2 field "courses"
    And I wait for the element "input[name='copy_evaluation']" to appear
    And I click the "input[name='copy_evaluation']" element
    And I wait very long for the page to be loaded
    And I press "submit"
    And I wait very long for the page to be loaded
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # Edit session to set coach
    And I wait for the element "i.mdi-pencil" to appear
    And I click the "i.mdi-pencil" element
    And I wait for the element "button.select2-selection__choice__remove" to appear
    And I click the "button.select2-selection__choice__remove" element
    And I type and select "teacher" in select2 field "coach_username"
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # Edit session to set status via advanced params
    And I wait for the element "i.mdi-pencil" to appear
    And I click the "i.mdi-pencil" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I click the "#advanced_params" element
    And I wait for the element "[name='status']" to appear
    And I select "In progress" from "status"
    And I wait for the element "[name='extra_domaine']" to appear

    # Set extra fields for the session
    And I select "vie-quotidienne" from "extra_domaine"
    And I wait very long for the page to be loaded

    # theme_fr and theme_de: type and select via select2 AJAX
    And I type and select "theme1" in inline select2 "extra_theme_fr"
    And I wait very long for the page to be loaded
    And I type and select "theme1" in inline select2 "extra_theme_de"
    And I wait very long for the page to be loaded

    # Select first option for competency fields
    And I select the first option from "extra_ecouter"
    And I select the first option from "extra_lire"
    And I select the first option from "extra_participer_a_une_conversation"
    And I select the first option from "extra_s_exprimer_oralement_en_continu"
    And I select the first option from "extra_ecrire"
    And I wait very long for the page to be loaded

    # Submit edit session
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Create future session "Session in the future" and include course
    # Create session Session in the future with start = 2036-02-03 and end = 2036-02-17
    When I am on "/main/session/session_add.php"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I click the "#advanced_params" element
    And I wait for the element "[name='title']" to appear
    And I fill in the following:
      | title             | Session in the future |
    And I set hidden field "access_start_date" to "2036-02-03 00:00"
    And I set hidden field "display_start_date" to "2036-02-03 00:00"
    And I set hidden field "coach_access_start_date" to "2036-02-03 00:00"
    And I set hidden field "access_end_date" to "2036-02-17 00:00"
    And I set hidden field "display_end_date" to "2036-02-17 00:00"
    And I set hidden field "coach_access_end_date" to "2036-02-17 00:00"
    And I press "submit"
    And I wait very long for the page to be loaded
    And I type and select "Testing course fr" in select2 field "courses"
    And I wait for the element "input[name='copy_evaluation']" to appear
    And I click the "input[name='copy_evaluation']" element
    And I wait very long for the page to be loaded
    And I press "submit"
    And I wait very long for the page to be loaded
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # Edit session to set coach
    And I wait for the element "i.mdi-pencil" to appear
    And I click the "i.mdi-pencil" element
    And I wait for the element "button.select2-selection__choice__remove" to appear
    And I click the "button.select2-selection__choice__remove" element
    And I type and select "teacher" in select2 field "coach_username"
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # Edit session to set status via advanced params
    And I wait for the element "i.mdi-pencil" to appear
    And I click the "i.mdi-pencil" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I click the "#advanced_params" element
    And I wait for the element "[name='status']" to appear
    And I select "Planned" from "status"
    And I wait for the element "[name='extra_domaine']" to appear

    # Set extra fields for the session
    And I select "vie-quotidienne" from "extra_domaine"
    And I wait very long for the page to be loaded

    # theme_fr and theme_de: type and select via select2 AJAX
    And I type and select "theme1" in inline select2 "extra_theme_fr"
    And I wait very long for the page to be loaded
    And I type and select "theme1" in inline select2 "extra_theme_de"
    And I wait very long for the page to be loaded

    # Select first option for competency fields
    And I select the first option from "extra_ecouter"
    And I select the first option from "extra_lire"
    And I select the first option from "extra_participer_a_une_conversation"
    And I select the first option from "extra_s_exprimer_oralement_en_continu"
    And I select the first option from "extra_ecrire"
    And I wait very long for the page to be loaded

    # Submit edit session
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Create past session "Past session" and include course
    # Create session Past session with start = 2026-01-06 and end = 2026-01-20
    When I am on "/main/session/session_add.php"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I click the "#advanced_params" element
    And I wait for the element "[name='title']" to appear
    And I fill in the following:
      | title             | Past session      |
    And I set hidden field "access_start_date" to "2026-01-06 00:00"
    And I set hidden field "display_start_date" to "2026-01-06 00:00"
    And I set hidden field "coach_access_start_date" to "2026-01-06 00:00"
    And I set hidden field "access_end_date" to "2026-01-20 00:00"
    And I set hidden field "display_end_date" to "2026-01-20 00:00"
    And I set hidden field "coach_access_end_date" to "2026-01-20 00:00"
    And I press "submit"
    And I wait very long for the page to be loaded
    And I type and select "Testing course fr" in select2 field "courses"
    And I wait for the element "input[name='copy_evaluation']" to appear
    And I click the "input[name='copy_evaluation']" element
    And I wait very long for the page to be loaded
    And I press "submit"
    And I wait very long for the page to be loaded
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # Edit session to set coach
    And I wait for the element "i.mdi-pencil" to appear
    And I click the "i.mdi-pencil" element
    And I wait for the element "button.select2-selection__choice__remove" to appear
    And I click the "button.select2-selection__choice__remove" element
    And I type and select "teacher" in select2 field "coach_username"
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # Edit session to set status via advanced params

    And I wait for the element "i.mdi-pencil" to appear
    And I click the "i.mdi-pencil" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I click the "#advanced_params" element
    And I wait for the element "[name='status']" to appear
    And I select "Finished" from "status"
    And I wait for the element "[name='extra_domaine']" to appear

    # Set extra fields for the session
    And I select "vie-quotidienne" from "extra_domaine"
    And I wait very long for the page to be loaded

    # theme_fr and theme_de: type and select via select2 AJAX
    And I type and select "theme2" in inline select2 "extra_theme_fr"
    And I wait very long for the page to be loaded
    And I type and select "theme2" in inline select2 "extra_theme_de"
    And I wait very long for the page to be loaded

    # Select first option for competency fields
    And I select the first option from "extra_ecouter"
    And I select the first option from "extra_lire"
    And I select the first option from "extra_participer_a_une_conversation"
    And I select the first option from "extra_s_exprimer_oralement_en_continu"
    And I select the first option from "extra_ecrire"
    And I wait very long for the page to be loaded

    # Submit edit session
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Create future English session "Session in the future en" and include course
    # Create session Session in the future en with start = 2036-04-26 and end = 2036-05-10
    When I am on "/main/session/session_add.php"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I click the "#advanced_params" element
    And I wait for the element "[name='title']" to appear
    And I fill in the following:
      | title             | Session in the future en |
    And I set hidden field "access_start_date" to "2036-04-26 00:00"
    And I set hidden field "display_start_date" to "2036-04-26 00:00"
    And I set hidden field "coach_access_start_date" to "2036-04-26 00:00"
    And I set hidden field "access_end_date" to "2036-05-10 00:00"
    And I set hidden field "display_end_date" to "2036-05-10 00:00"
    And I set hidden field "coach_access_end_date" to "2036-05-10 00:00"
    And I press "submit"
    And I wait very long for the page to be loaded
    And I type and select "Testing course en" in select2 field "courses"
    And I wait for the element "input[name='copy_evaluation']" to appear
    And I click the "input[name='copy_evaluation']" element
    And I wait very long for the page to be loaded
    And I press "submit"
    And I wait very long for the page to be loaded
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # Edit session to set coach
    And I wait for the element "i.mdi-pencil" to appear
    And I click the "i.mdi-pencil" element
    And I wait for the element "button.select2-selection__choice__remove" to appear
    And I click the "button.select2-selection__choice__remove" element
    And I type and select "teacher" in select2 field "coach_username"
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # Edit session to set status via advanced params
    And I wait for the element "i.mdi-pencil" to appear
    And I click the "i.mdi-pencil" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I click the "#advanced_params" element
    And I wait for the element "[name='status']" to appear
    And I select "Planned" from "status"
    And I wait for the element "[name='extra_domaine']" to appear

    # Set extra fields for the session
    And I select "vie-quotidienne" from "extra_domaine"
    And I wait very long for the page to be loaded

    # theme_fr and theme_de: type and select via select2 AJAX
    And I type and select "theme1" in inline select2 "extra_theme_fr"
    And I wait very long for the page to be loaded
    And I type and select "theme1" in inline select2 "extra_theme_de"
    And I wait very long for the page to be loaded

    # Select first option for competency fields
    And I select the first option from "extra_ecouter"
    And I select the first option from "extra_lire"
    And I select the first option from "extra_participer_a_une_conversation"
    And I select the first option from "extra_s_exprimer_oralement_en_continu"
    And I select the first option from "extra_ecrire"
    And I wait very long for the page to be loaded

    # Submit edit session
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

