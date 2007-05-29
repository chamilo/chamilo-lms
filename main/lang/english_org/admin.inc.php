<?php // $Id: admin.inc.php 12502 2007-05-29 08:06:23Z pcool $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Roan Embrechts (VUB)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/*
==============================================================================
	This is a language translation file.
	This file provides the language sentences for the admin section.
==============================================================================
*/

$langOtherCategory	= "Other category";
$langSendMailToUsers = "Send a mail to users";

$langExampleXMLFile = "Example of XML file";
$langExampleCSVFile = "Example of CSV file";

$langCourseBackup="Make a back-up of this area";

$langCourseCode="Area code";
$langCourseTitular="Leader";
$langCourseTitle="Area title";
$langCourseFaculty="Area category";
$langCourseDepartment="Area department";
$langCourseDepartmentURL="Department URL";
$langCourseLanguage="Area language";
$langCourseAccess="Area access";
$langCourseSubscription="Area subscription";
$langPublicAccess="Public access";
$langPrivateAccess="Private access";
$langFromHomepageWithoutLogin="from portal homepage even without login";
$langSiteReservedToPeopleInMemberList="accessible only to people on the User list";
$langCode="Code";
$langUsers="Users";
$langLanguage="Language";
$langCategory="Category";

$langClassName="Class name";

$langDBManagementOnlyForServerAdmin="Database management is only available for the server administrator";

$langShowUsersOfCourse="Show users subscribed to this area";
$langShowClassesOfCourse="Show classes subscribed to this area";
$langShowGroupsOfCourse="Show groups of this area";
$langOfficialCode="Official code";
$langFirstName="First name";
$langLastName="Last name";
$langLoginName="Username";
$langPhone="Phone";
$langPhoneNumber="Phone number";
$langStatus="Status";
$langEmail="E-mail address";
$langPlatformAdmin="Platform administrator";
$langActions="Actions";
$langAddToCourse="Add to a area";
$langDeleteFromPlatform="Remove from the platform";
$langDeleteCourse="Delete this (these) area(s)";
$langDeleteFromCourse="Delete from this (these) area(s)";
$langDeleteSelectedClasses="Delete selected classes";
$langDeleteSelectedGroups="Delete selected groups";
$langAdministrator="Administrator";
$langTeacher="Leader";
$langUser="User";
$langAddPicture="Add a picture";
$langChangePicture="Change the picture";
$langDeletePicture="Delete the picture";
$langAddUsers="Add users";
$langAddGroups="Add groups";
$langAddClasses="Add classes";
$langExportUsers="Export the user list";
$langKeyword="Keyword";
$langGroupName="Group's name";
$langGroupTutor="Group's coach";
$langGroupForum="Group's forum";
$langGroupDescription="Group description";
$langNumberOfParticipants="Number of participants";
$langNumberOfUsers="Number of users";
$langMaximum="maximum";
$langMaximumOfParticipants="Maximum number of participants";
$langParticipants="participants";
$langGroup="Group";

$langFirstLetterClass="First letter (class name)";
$langFirstLetterUser="First letter (last name)";
$langFirstLetterCourse="First letter (code)";

$langCatCodeAlreadyUsed="A category with that code already exists !";
$langPleaseEnterCategoryInfo="Please enter the code and the name of the category !";

$langModifyUserInfo="Modify user information";
$langModifyClassInfo="Modify class information";
$langModifyGroupInfo="Modify group information";
$langModifyCourseInfo="Modify area information";
$langPleaseEnterClassName="Please enter the class name !";
$langPleaseEnterLastName="Please enter the user's last name !";
$langPleaseEnterFirstName="Please enter the user's first name !";
$langPleaseEnterValidEmail="Please enter a valid e-mail address !";
$langPleaseEnterValidLogin="Please enter a valid login !";
$langPleaseEnterCourseCode="Please enter the area code !";
$langPleaseEnterTitularName="Please enter the leader's name and firstname !";
$langPleaseEnterCourseTitle="Please enter the area title !";
$langAcceptedPictureFormats="Accepted formats are JPG, PNG and GIF !";
$langLoginAlreadyTaken="This login is already taken !";

$langImportUserListXMLCSV="Import a list of users from an XML/CSV file";
$langExportUserListXMLCSV="Export the user list into an XML/CSV file";
$langOnlyUsersFromCourse="Only users from the area";
$langUserListHasBeenExportedTo="The user list has been exported to";

$langAddClassesToACourse="Add classes to a area";
$langAddUsersToACourse="Add users to a area";
$langAddUsersToAClass="Add users to a class";
$langAddUsersToAGroup="Add users to a group";
$langAtLeastOneClassAndOneCourse="You must select at least one class and one area !";
$langAtLeastOneUser="You must select at least one user !";
$langAtLeastOneUserAndOneCourse="You must select at least one user and one area !";
$langClassList="Class list";
$langUserList="User list";
$langCourseList="Area list";
$langAddToThatCourse="Add to this (these) area(s)";
$langAddToClass="Add to the class";
$langRemoveFromClass="Remove from the class";
$langAddToGroup="Add to the group";
$langRemoveFromGroup="Remove from the group";

$langUsersOutsideClass="Users outside the class";
$langUsersInsideClass="Users inside the class";
$langUsersOutsideGroup="Users outside the group";
$langUsersInsideGroup="Users inside the group";

$langImportFileLocation="Location of the CSV / XML file";
$langFileType="File type";
$langOutputFileType="Output file type";
$langMustUseSeparator="must use the ';' character as a separator";
$langCSVMustLookLike="The CSV file must look like this";
$langXMLMustLookLike="The XML file must look like this";
$langMandatoryFields="fields in <b>bold</b> are mandatory";
$langNotXML="The specified file is not XML format !";
$langNotCSV="The specified file is not CSV format !";
$langNoNeededData="The specified file doesn't contain all needed data !";
$langMaxImportUsers="You can't import more than 500 users at once !";

$langAdminDatabases="Databases (phpMyAdmin)";
$langAdminUsers="Users";
$langAdminClasses="Classes of users";
$langAdminGroups="Groups of users";
$langAdminCourses="Areas";
$langAdminCategories="Categories of areas";
$langSubscribeUserGroupToCourse="Subscribe a user / group to a area";
$langAddACategory="Add a category";
$langInto="into";
$langNoCategories="There are no categories here";
$langAllowCoursesInCategory="Allow to add areas in this category ?";
$langGoToForum="Go to the forum";

$langCategoryCode="Category code";
$langCategoryName="Category name";

$langCourses="Areas";
$langCategories="categories";

$langEditNode = "Edit this category";
$langOpenNode = "Open this category";
$langDeleteNode = "Delete this category";
$langAddChildNode ="Add a sub-category";
$langViewChildren = "View children";
$langTreeRebuildedIn = "Tree rebuilded in";
$langTreeRecountedIn = "Tree recounted in";
$langRebuildTree="Rebuild the tree";
$langRefreshNbChildren="Refresh number of children";
$langShowTree = "Show tree";
$langBack = "Back to previous page";
$langLogDeleteCat  = "Category deleted";
$langRecountChildren = "Recount children";
$langUpInSameLevel ="Up in same level";

$langSeconds="seconds";
$langIn="In";

$langMailTo = "Mail to : ";
$lang_no_access_here ="No access here ";
$lang_php_info = "information about the system";

$langAddAdminInApache ="Add an administrator";
$langAddFaculties = "Add categories";
$langSearchACourse  = "Search for a area";
$langSearchAUser  ="Search for a user";

$langAdminBy = "Administration by ";
$langAdministrationTools = "Administration";
$langTools = "Tools";
$langTechnicalTools = "Technical";
$langConfig = "System config";
$langState = "State of system";
$langDevAdmin ="Development Administration";
$langLinksToClaroProjectSite ="Link to the project website";
$langNomOutilTodo 		= "Manage Todo list"; // to do
$langNomPageAdmin 		= "Administration";
$langSysInfo  			= "Info about the System";        // Show system status
$langCheckDatabase  	= "Check main dokeos database";        // Check Database
$langDiffTranslation 	= "Compare translations"; // diff of translation
$langStatOf 			= "Statistics of "; // Stats of...
$langSpeeSubscribe 		= "Quick subscribe as area Checker";
$langLogIdentLogout 	= "Login list";
$langLogIdentLogoutComplete = "Login list (extended)";

// Stat
$langStatistiques = "Statistics";


$langNbProf = "Number of leaders";
$langNbStudents = "Number of members";
$langNbLogin = "Number of logins";
$langToday   ="Today";
$langLast7Days ="Last 7 days";
$langLast30Days ="Last 30 days";

$langNbAnnoucement = "Number of announcements";

// Check Data base

$langPleaseCheckConfigForMainDataBaseName = "Please check these values<br>Main database name in <br>";
$langBaseFound ="Found<br>Checking tables of this base";
$langNotNeeded = "not needed";
$langNeeded = "needed";
$langArchive   ="archive";
$langUsed      ="used";
$langPresent   ="Ok";
$langCreateMissingNow = "Do you want to create tables now&nbsp;?";
$langCheckingCourses ="Checking areas";
$langMissing   ="missing";
$langExist     ="existing";

// Create Claro table
$langCreateClaroTables		= "Create Table for main Database";
$langTableStructureDontKnow	= "Structure of this table unknown";

$langServerStatus	= "Status of MySQL server&nbsp;: ";
$langDataBase		= "Database ";
$langRun			= "works";
$langClient			= "MySql Client ";
$langServer			= "MySql Server ";
$langtitulary		= "Owner";
$langUpgradeBase 	= "Upgrade database";
$langManage			= "Manage Portal";
$langErrorsFound 	= "errors found";

$langMaintenance 	= "Maintenance";
$langUpgrade		= "Upgrade Dokeos";
$langWebsite		= "Dokeos website";
$langDocumentation	= "Documentation";
$langForum			= "Forum";
$langContribute		= "Contribute";
$langInfoServer		= "Server Information";

$langStatistics = "Statistics";
$langYourDokeosUses = "Your Dokeos installation uses presently";
$langOnTheHardDisk = "on the hard disk";
?>