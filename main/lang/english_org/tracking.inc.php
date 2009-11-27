<?php
/*
      +----------------------------------------------------------------------+
      | CLAROLINE version 1.3.0 $Revision: 1997 $                            |
      +----------------------------------------------------------------------+
      | Copyright (c) 2001, 2002 Universite catholique de Louvain (UCL)      |
      +----------------------------------------------------------------------+
      |   $Id: tracking.inc.php 1997 2004-07-07 14:55:42Z olivierb78 $         |
      +----------------------------------------------------------------------+
      |   This program is free software; you can redistribute it and/or      |
      |   modify it under the terms of the GNU General Public License        |
      |   as published by the Free Software Foundation; either version 2     |
      |   of the License, or (at your option) any later version.             |
      |                                                                      |
      |   This program is distributed in the hope that it will be useful,    |
      |   but WITHOUT ANY WARRANTY; without even the implied warranty of     |
      |   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the      |
      |   GNU General Public License for more details.                       |
      |                                                                      |
      |   You should have received a copy of the GNU General Public License  |
      |   along with this program; if not, write to the Free Software        |
      |   Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA          |
      |   02111-1307, USA. The GNU GPL license is also available through     |
      |   the world-wide-web at http://www.gnu.org/copyleft/gpl.html         |
      +----------------------------------------------------------------------+
      | Authors: Thomas Depraetere <depraetere@ipm.ucl.ac.be>                |
      |          Hugues Peeters    <peeters@ipm.ucl.ac.be>                   |
      |          Christophe Gesché <gesche@ipm.ucl.ac.be>                    |
      |          Piraux Sébastien  <piraux_seb@hotmail.com>                  |
      +----------------------------------------------------------------------+
 */


/***************************************************************
*                   Language translation
****************************************************************
GOAL
****
Translate the interface in chosen language
ENGLISH
*****************************************************************/

/* general */
$langTrackingDisabled = "Tracking has been disabled by system administrator.";
$langToolName="Tracking";
$langShowAll = "Show all";
$langShowNone = "Show none";

$langCourseStats = "Area Stats";
$langToolsAccess = "Access to tools";
$langCourseAccess = "Access to this area";
$langLinksAccess = "Links";
$langDocumentsAccess = "Documents";
$langScormAccess = "Learning path - Scorm format areas";

$langLinksDetails = "Links accessed by the user";
$langWorksDetails = "Works uploaded by the user in the name of 'Authors'";
$langLoginsDetails = "Click on the month name for more details";
$langDocumentsDetails = "Documents downloaded by the user"; 
$langExercicesDetails = "Score of tests done";

$langBackToList = "Back to users list";
$langDetails = "Details";
$langClose = "Close";

/* subtitles */
$langStatsOfCourse = "Statistics of Area";
$langStatsOfUser = "Statistics of user";
$langStatsOfCampus = "Statistics of portal";
/* area */
$langCountUsers = "Number of subscribed users";

/* area access */
$langCountToolAccess = "Total number of connections to this area";


/* logins */
$langLoginsTitleMonthColumn = "Month";
$langLoginsTitleCountColumn = "Number of logins";

/* tools */
$langToolTitleToolnameColumn = "Name of the tool";
$langToolTitleUsersColumn = "Users Clicks";
$langToolTitleCountColumn = "Total Clicks";

/* links*/
$langLinksTitleLinkColumn = "Link";
$langLinksTitleUsersColumn = "Users Clicks";
$langLinksTitleCountColumn = "Total Clicks";

/* exercises */
$langExercicesTitleExerciceColumn = "Test";
$langExercicesTitleScoreColumn = "Score";

/* documents */
$langDocumentsTitleDocumentColumn = "Document";
$langDocumentsTitleUsersColumn = "Users Downloads";
$langDocumentsTitleCountColumn = "Total Downloads";

/* scorm */
$langScormContentColumn="Area Title";
$langScormStudentColumn="Users";
$langScormTitleColumn="Lesson";
$langScormStatusColumn="Status";
$langScormScoreColumn="Score";
$langScormTimeColumn="Time";
$langScormNeverOpened="This area was never opened by this user.";

/* works */
$langWorkTitle = "Title";
$langWorkAuthors = "Authors";
$langWorkDescription = "Description";

$langDate = "Date";

/* user list */
$informationsAbout = "Tracking of";
$langUserName = "Username";
$langFirstName = "FirstName";
$langLastName = "Lastname";
$langEmail = "Email";
$langNoEmail = "No email address specified";
/* others */
$langNoResult = "No Result";

$langCourse = "Area";

$langHits = "Hits";
$langTotal = "Total";
$langHour = "Hour";
$langDay = "Day";
$langLittleHour = "h.";
$langLast31days = "In the last 31 days";
$langLast7days = "In the last 7 days";
$langThisday  = "This day";

/* perso stats */
$langLogins = "My last logins";
$langLoginsExplaination = "Here is the list of your last logins with the tools you visited during these sessions.";

$langExercicesResults = "Score of the tests done";

$langVisits = "visits";
$langAt = "at";
$langLoginTitleDateColumn = "Date";
$langLoginTitleCountColumn = "Visits";

/* coach view */
$langLoginsAndAccessTools = "Logins and access to tools";
$langWorkUploads = "Contributions uploads";
$langErrorUserNotInGroup = "Invalid user : this user doesn't exist in your group" ;
$langListStudents = "List of users in this group";

/* details page */
$langPeriodHour = "Hour";
$langPeriodDay = "Day";
$langPeriodWeek = "Week";
$langPeriodMonth = "Month";
$langPeriodYear = "Year";

$langNextDay = "Next Day";
$langPreviousDay = "Previous Day";
$langNextWeek = "Next Week";
$langPreviousWeek = "Previous Week";
$langNextMonth = "Next Month";
$langPreviousMonth = "Previous Month";
$langNextYear = "Next Year";
$langPreviousYear = "Previous Year";


$langViewToolList = "View List of All Tools";
$langToolList = "List of all tools";

$langFrom = "From";
$langTo = "to";

/* traffic_details */
$langPeriodToDisplay = "Period";
$langDetailView = "View by";

/* for interbredcrumps */
$langBredCrumpGroups = "Groups";
$langBredCrumpGroupSpace = "Group Area";
$langBredCrumpUsers = "Users";

/* admin stats */
$langAdminToolName = "Admin Stats";
$langPlatformStats = "Platform Statistics";
$langStatsDatabase = "Stats Database";
$langPlatformAccess = "Access to portal";
$langPlatformCoursesAccess = "Access to areas";
$langPlatformToolAccess = "Access to tools";
$langHardAndSoftUsed = "Countries Providers Browsers Os Referers";
$langStrangeCases = "Problematic cases";
$langStatsDatabaseLink = "Click Here";
$langCountCours = "Number of areas";
$langCountUsers = "Number of users";
$langCountCourseByFaculte  = "Number of areas by category";
$langCountCourseByLanguage = "Number of areas by language";
$langCountCourseByVisibility = "Number of areas by visibility";
$langCountUsersByCourse = "Number of users by area";
$langCountUsersByFaculte = "Number of users by category";
$langCountUsersByStatus = "Number of users by status";
$langCourses = "Areas";
$langUsers = "Users";
$langAccess = "Access";
$langCountries = "Countries";
$langProviders = "Providers";
$langOS = "OS";
$langBrowsers = "Browsers";
$langReferers = "Referers";
$langAccessExplain = "(When an user open the index of the portal)";
$langLogins = "Logins";
$langTotalPlatformAccess = "Total";
$langTotalPlatformLogin = "Total";
$langMultipleLogins = "Accounts with same <i>Username</i>";
$langMultipleUsernameAndPassword = "Accounts with same <i>Username</i> AND same <i>Password</i>";
$langMultipleEmails = "Accounts with same <i>Email</i>";
$langCourseWithoutProf = "Areas without leader";
$langCourseWithoutAccess = "Areas not used";
$langLoginWithoutAccess  = "Logins not used";
$langAllRight = "There is no strange case here";
$langDefcon = "Ooops, problematic cases detected !!";
$langNULLValue = "Empty (or NULL)";
$langTrafficDetails = "Traffic Details";

$langSeeIndividualTracking= "For individual tracking see <a href=../user/user.php>Users</a> tool.";
?>