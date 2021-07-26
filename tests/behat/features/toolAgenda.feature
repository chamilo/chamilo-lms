Feature: Agenda tool
  In order to use the Agenda tool
  The admin should be able to add an event

  Background:
    Given I am a platform administrator

  Scenario: Create a personal event
    Given I am on "/main/calendar/agenda.php?action=add&type=personal"
    When I fill in the following:
      | title | Event 1 |
    Then I fill in editor field "content" with "Description event"
    Then wait for the page to be loaded
    And I focus "date_range"
    And I fill in "date_range" with "2017-03-07 12:15 / 2017-03-07 12:15"
    And I press "Add event"
    And wait very long for the page to be loaded
    Then I should see "Event added"
# TODO we need to check if the event appears in the personal agenda on resources/ccalendarevent
# For the moment it does not appear

  Scenario: Create an event inside course TEMP
    Given I am on "/main/calendar/agenda.php?action=add&type=course&cid=1"
    When I fill in the following:
      | title | Event in course |
    Then I fill in editor field "content" with "Description event"
#    And I fill in select bootstrap input "users_to_send[]" with "Everyone" and select "Everyone"
    Then wait for the page to be loaded
    And I focus "date_range"
    And I fill in "date_range" with "2017-03-07 12:15 / 2017-03-07 12:15"
    And I press "Add event"
    And wait very long for the page to be loaded
    Then I should see "Event added"

#TODO This scenario needs to be fixed because I do not know how to set the start date and the end date of the event.
#There is an input id but it is impossible to know it. I put startDate and endDate for the moment.
#  Scenario: Create a personal event from the general agenda
#    Given I am on "/resources/ccalendarevent"
#    When I follow "Add event"
#    Then I fill in the following:
#      | title | Personal event from general agenda |
#      | Content | Content for personal event from general agenda |
#    And I fill in the following:
#      | startDate | 2021-07-26 14:15 |
#      | endDate | 2021-07-26 14:45 |
#    And I press "Add"
#    Then I should see "Personal event from general agenda created"

#TODO This scenario needs to be fixed because I do not know how to set the start date and the end date of the event.
#There is an input id but it is impossible to know it. I put startDate and endDate for the moment.
#We also need to add the user seleccion in the form and finaly connect as the invitee to check access and mail notification in inbox
#  Scenario: Create a personal event from the general agenda and invite user who can not modify the event
#    Given I am on "/resources/ccalendarevent"
#    When I follow "Add event"
#    Then I fill in the following:
#      | title | Personal event from general agenda with invitees not editable |
#      | Content | Content for personal event from general agenda with invitees not editable |
#    And I fill in the following:
#      | startDate | 2021-07-27 14:15 |
#      | endDate | 2021-07-27 14:45 |
#    And I focus "Shere with User"
#    And I fill in "Share with User" with "abagg"
#    And I wait for the page to be loaded
#    Then I select "abaggins" from "Share with User"
#    And I press "Add"
#    Then I should see "Personal event from general agenda with invitees not editable created"
#    Then I am user abaggins
#    And I am on "/resources/ccalendarevent"
#    Then I should see "Personal event from general agenda with invitees not editable"
#    And I follow "Personal event from general agenda with invitees not editable"
#    And I should not see "edit"
#    Then I am a platform administrator
#    And I am on "/resources/ccalendarevent"
#    Then I should see "Personal event from general agenda with invitees not editable"
#    And I follow "Personal event from general agenda with invitees not editable"
#    And I should see "Delete"
#    Then I follow "Delete"
#    And I am on "/resources/ccalendarevent"
#    Then I should not see "Personal event from general agenda with invitees editable"
#    Then I am user abaggins
#    And I am on "/resources/ccalendarevent"
#    Then I should not see "Personal event from general agenda with invitees not editable"

#TODO This scenario needs to be fixed because I do not know how to set the start date and the end date of the event.
#There is an input id but it is impossible to know it. I put startDate and endDate for the moment.
#We also need to add the user seleccion in the form and finaly connect as the invitee to check access and mail notification in inbox
#  Scenario: Create a personal event from the general agenda and invite user who can modify the event
#    Given I am on "/resources/ccalendarevent"
#    When I follow "Add event"
#    Then I fill in the following:
#      | title | Personal event from general agenda with invitees editable |
#      | Content | Content for personal event from general agenda with invitees editable |
#    And I fill in the following:
#      | startDate | 2021-07-28 14:15 |
#      | endDate | 2021-07-28 14:45 |
#    And I focus "Share with User"
#    And I fill in "Share with User" with "abagg"
#    And I wait for the page to be loaded
#    Then I select "abaggins" from "Share with User"
#    Then I check "Is it editable by the invitees?"
#    And I press "Add"
#    Then I should see "Personal event from general agenda with invitees editable created"
#    Then I am user abaggins
#    And I am on "/resources/ccalendarevent"
#    Then I should see "Personal event from general agenda with invitees editable"
#    And I follow "Personal event from general agenda with invitees editable"
#    And I should not see "Edit"
#    Then I follow "Delete"
#    And I should see "Are you sure you want to delete this event?"
#    And I follow "OK"
#    Then I should not see "Personal event from general agenda with invitees editable"
#    Then I am a platform administrator
#    And I am on "/resources/ccalendarevent"
#    And I should see "Personal event from general agenda with invitees editable" 
#    And I follow "Personal event from general agenda with invitees editable"
#    And I should not see "Edit"
#    And I follow "Edit"
#    Then I should not see "abaggins"
#    Then I follow "Cancel"
#    Then I should see "Delete"
#    Then I follow "Delete"
#    And I should see "Are you sure you want to delete this event?"
#    And I follow "OK"
#    And I am on "/resources/ccalendarevent"
#    Then I should not see "Personal event from general agenda with invitees editable"
