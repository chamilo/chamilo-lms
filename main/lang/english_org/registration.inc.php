<?php # $Id: registration.inc.php 1997 2004-07-07 14:55:42Z olivierb78 $
//----------------------------------------------------------------------
// CLAROLINE
//----------------------------------------------------------------------
// Copyright (c) 2001-2003 Universite catholique de Louvain (UCL)
//----------------------------------------------------------------------
// This program is under the terms of the GENERAL PUBLIC LICENSE (GPL)
// as published by the FREE SOFTWARE FOUNDATION. The GPL is available
// through the world-wide-web at http://www.gnu.org/copyleft/gpl.html
//----------------------------------------------------------------------
// Authors: see 'credits' file
//----------------------------------------------------------------------
/*
	  +----------------------------------------------------------------------+
	  | Translator :                                                         |
	  |          Thomas Depraetere <depraetere@ipm.ucl.ac.be>                |
	  |          Andrew Lynn       <Andrew.Lynn@strath.ac.uk>                |
	  +----------------------------------------------------------------------+
 */

$langCourseAdministratorOnly = "Leader only";
$langDefineHeadings = "Define Headings";

$langAddNewUser = "Add a new user";

$langFirstname = "First name"; // by moosh
$langLastname = "Last name"; // by moosh
$langEmail = "Email";// by moosh
$langRetrieve ="Retrieve identification information";// by moosh
$langMailSentToAdmin = "A mail is sent to  administrator.";// by moosh
$langAccountNotExist = "Account not found.<BR>".$langMailSentToAdmin." They  would search manually.<BR>";// by moosh
$langAccountExist = "This account exists.<BR>".$langMailSentToAdmin."<BR>";// by moosh
$langWaitAMailOn = "A mail can be sent to ";// by moosh
$langCaseSensitiveCaution = "System is case sensitive.";// by moosh
$langDataFromUser = "Data sent by user";// by moosh
$langDataFromDb = "Data in the database";// by moosh
$langLoginRequest = "Login request";// by moosh
$langExplainFormLostPass = "Type data entered at first registration.";
// by moosh
$langTotalEntryFound = "Record found";// by moosh
$langEmailNotSent = "Somethink doesn't work, mail this to ";// by moosh
$langYourAccountParam = "This is your information to connect to";
$langTryWith ="Try with";// by moosh
$langInPlaceOf ="and  not with ";// by moosh
$langParamSentTo = "Identification information sent to ";// by moosh
$langAddVarUser="Enroll a list of users";

// REGISTRATION - AUTH - inscription.php
$lang_lost_password="Lost password";
$langRegistration="Registration";
$langName=$langFirstname;
$langSurname=$langLastname;
$langUsername="Username";
$langPass="Password";
$langConfirmation="Confirmation";
$langStatus="Status";
$langRegStudent="Follow areas";
$langRegAdmin="Create areas";

// inscription_second.php
$langPassTwice="You typed two different passwords. Use your browser's back button and try again.";
$langEmptyFields="You left some fields empty. Use your browser's back button and try again.";
$langUserFree="This username is already taken. Use your browser's back button and choose another.";
$langYourReg="Your registration on";
$langDear="Dear";
$langYouAreReg="You are registered on";
$langSettings="with the following settings :\n\nUsername :";
$langAddress="The address of ";
$langIs="is";
$langProblem="In case of problems, contact us.";
$langFormula="Yours sincerely";
$langManager="Manager";
$langPersonalSettings="Your personal settings have been registered";
$langMailHasBeenSent="An email has been sent to help you remember your username and password";

$langNowGoChooseYourCourses ="You can now go to select, in the list, the areas you want to access to";
$langNowGoCreateYourCourse  ="You can now go to create  your  area";

$langYourRegTo="Your are registered to";
$langIsReg="has been updated";
$langCanEnter="You can now <a href=../../index.php>enter the portal</a>";

// profile.php

$langModifProfile="Modify my profile";
$langViewProfile="View my profile";
$langPassTwo="You have typed two different passwords";
$langAgain="Try again!";
$langFields="You left some fields empty";
$langUserTaken="This username is already in use";
$langEmailWrong="The email address is not complete or contains some unvalid characters";
$langProfileReg="Your new profile has been saved";
$langHome="Back to Home Page";
$langMyStats = "View my tracking";

// user.php

$langUsers="Users";
$langModRight="Modify admin rights of";
$langNone="None";
$langAll="All";
$langNoAdmin="has now <b>NO leader rights on this site</b>";
$langAllAdmin="has now <b>ALL leader rights on this site</b>";
$langModRole="Modify the description of";
$langRole="Description";
$langIsNow="is now";
$langInC="in this area";
$langFilled="You have left some fields empty.";
$langUserNo="The username you choose ";
$langTaken="is already in use. Choose another one.";
$langOneResp="One of the area administrators";
$langRegYou="has registered you on this area";
$langTheU="The user";
$langAddedU="has been added. An email has been sent to give him his username ";
$langAndP="and his password";
$langDereg="has been unregistered from this area";
$langAddAU="Add a user";
$langImportUserList="Import a list of users";
$langStudent="Member";
$langBegin="begin.";
$langPreced50 = "Previous 50";
$langFollow50 = "Next 50";
$langEnd = "end";
$langAdmR="Admin. rights";
$langUnreg = "Unregister";
$langAddHereSomeCourses = "<font size=2 face='Arial, Helvetica'><big>Modify areas list</big><br><br>Check the areas you want to follow.<br>Uncheck the ones you don't want to follow anymore.<br> Then click Ok at the bottom of the list";

$langTitular = "Author";
$langCanNotUnsubscribeYourSelf = "You can't unsubscribe yourself from a area that you lead, only another leader can do that.";

$langGroup="Group";
$langUserNoneMasc="-";
$langTutor="Coach";
$langTutorDefinition="Coach (right to supervise groups)";
$langAdminDefinition="Admin (right to modify Area)";
$langDeleteUserDefinition="Unregister (delete from users list of <b>this</b> area)";
$langNoTutor = "is not coach for this area";
$langYesTutor = "is coach for this area";
$langUserRights="Users rights";
$langNow="now";
$langOneByOne="Add user manually";
$langUserMany="Import a user list through a CSV / XML file";
$langNo="no";
$langYes="yes";
$langUserAddExplanation="every line of file to send will necessarily an only
		include 5 fields: <b>First name&nbsp;&nbsp;&nbsp;Last name&nbsp;&nbsp;&nbsp;
		Login&nbsp;&nbsp;&nbsp;Password&nbsp;
		&nbsp;&nbsp;Email</b> separated by tabs and in this order.
		Users will receive email confirmation with login/password.";
$langSend="Send";
$langDownloadUserList="Upload list";
$langUserNumber="number";
$langGiveAdmin="Make admin";
$langRemoveRight="Remove this right";
$langGiveTutor="Make coach";
$langUserOneByOneExplanation="He (she) will receive email confirmation with login and password";
$langBackUser="Back to users list";
$langUserAlreadyRegistered="A user with same name is already registered	in this area.";

$langAddedToCourse="has been registered to your area";
$langGroupUserManagement="Group management";
$langIsReg="Your modifications have been registered";
$langPassTooEasy ="this password  is too simple. Use a pass like this ";

$langIfYouWantToAddManyUsers="If you want to add a list of users in
			your area, please contact your web administrator.";

$langCourses="Areas.";

$langLastVisits="My last visits";
$langSee		= "Go&nbsp;to";
$langSubscribe	= "Subscribe";
$langCourseName	= "Name&nbsp;of&nbsp;course";
$langLanguage	= "Language";

$langConfirmUnsubscribe = "Confirm user unsubscription";
$langAdded = "Added";
$langDeleted = "Deleted";
$langPreserved = "Preserved";

$langDate = "Date";
$langAction = "Action";
$langLogin = "Log In";
$langModify = "Modify";

$langUserName = "User name";

$lang_enter_email_and_well_send_you_password  = "Enter the e-mail address that you used to register and we will send you back your password.";
$lang_your_password_has_been_emailed_to_you="Your password has been emailed to you.";
$password_request="You have asked to reset your password.\nIf you did not ask, then ignore this mail.\n\nTo reset your password click on the reset link.";

$langEdit = "Edit";
$langCourseManager = "Leader";
$langAddImage= "Picture";
$langImageWrong="The file size should be smaller than";
$langUpdateImage = "Change picture"; //by Moosh
$langDelImage = "Remove picture"; 	//by Moosh
$langOfficialCode = "Official Code (ID)";

$langAuthInfo = "Authentication";
$langEnter2passToChange = "Enter your password twice to change it. Otherwise, leave the fields empty.";

$langTracking="Tracking";

$langShouldBeCSVFormat="File should be CSV format. Do not add spaces. Structure should be exactly :";
?>
