@common @tools
Feature: Course description tool
  In order to manage the course description
  As a course administrator and student
  I want to edit and view the course description


  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded


  Scenario: Admin edits the course description and sees the content
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Course description"
    And I wait for the page to be loaded
    And I click the "i.mdi-image-text" element
    And I fill in "course_description_title" with "General"
    And I fill in editor field "content" with "The surface web, also known as the visible web or indexed web, is the portion of the World Wide Web that is readily accessible to the general public through standard search engines such as Google and Bing, using conventional web browsers like Chrome or Firefox without requiring special software, authentication, or configuration.[1][2] It encompasses publicly available content that is crawled and indexed by search engine algorithms, allowing users to discover and navigate websites via simple URLs and keyword queries."
    And I press "course_description_submit"
    And I wait for the page to be loaded
    Then I should see "surface web"


  Scenario: Student views the course description
    Given I am a student
    And I wait for the page to be loaded
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Course description"
    And I wait for the page to be loaded
    Then I should see "surface web"
