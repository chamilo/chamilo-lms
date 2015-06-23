# features/courseTools.feature
@common
Feature: Course tools basic testing
  In order to use a course
  As a teacher
  I need to be able to enter a course and each of its tools

  Scenario: Make sure the course exists
    Given I am a platform administrator
    Given course "TEMP" exists
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the course description tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/course_description/index.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the documents tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/document/document.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the learning path tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/newscorm/lp_controller.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the links tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/link/link.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the tests tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/exercice/exercice.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the announcements tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/announcements/announcements.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the assessments tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/gradebook/index.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the glossary tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/glossary/index.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the attendances tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/attendances/index.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the course progress tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/course_progress/index.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the agenda tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/calendar/agenda.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the forums tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/forum/index.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the dropbox tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/dropbox/index.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the users tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/user/index.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the groups tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/group/group.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the chat tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/chat/index.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the assignments tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/work/work.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the surveys tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/survey/index.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the wiki tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/wiki/index.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the notebook tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/notebook/index.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the projects tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/blog/blog_admin.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the reporting tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/tracking/courseLog.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the settings tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/course_info/infocours.php"
    Then I should not see an ".alert-danger" element

  Scenario: Make sure the backup tool is available
    Given I am a platform administrator
    Given I am on course "TEMP" homepage
    Given I am on "/main/course_info/maintenance.php"
    Then I should not see an ".alert-danger" element


