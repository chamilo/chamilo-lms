<?php // $Id: create_course.inc.php 4412 2005-04-25 08:35:49Z olivierb78 $
/*
      +----------------------------------------------------------------------+
      | CLAROLINE version 1.3.0 $Revision: 4412 $                            |
      +----------------------------------------------------------------------+
      | Copyright (c) 2001, 2002 Universite catholique de Louvain (UCL)      |
      +----------------------------------------------------------------------+
	  |   English Translation                                                |
      +----------------------------------------------------------------------+
      | Authors: Thomas Depraetere <depraetere@ipm.ucl.ac.be>                |
      |          Hugues Peeters    <peeters@ipm.ucl.ac.be>                   |
      |          Christophe Gesché <gesche@ipm.ucl.ac.be>                    |
      +----------------------------------------------------------------------+
      | Translator :                                                         |
      |          Thomas Depraetere <depraetere@ipm.ucl.ac.be>                |
      |          Andrew Lynn       <Andrew.Lynn@strath.ac.uk>                |
      +----------------------------------------------------------------------+
 */
// add_course
$langNewCourse 			= "New Area";
$langAddNewCourse 		= "Add a new Area";
$langRestoreCourse		= "Restore a Area";
$langOtherProperties  	= "Other properties found in the archive";
$langSysId 				= "System ID";
$langDescription  		= "Description";
$langDepartment	  		= "Department";
$langDepartmentUrl	  	= "Url";
$langScoreShow  		= "Show score";
$langVisibility  		= "Visibility";
$langLastVisit  		= "Last visit";
$langLastEdit  			= "Last contribution";
$langExpire 			= "Expiration";
$langChoseFile 			= "Select file";
$langFtpFileTips 		= "File on a FTP server";
$langHttpFileTips		= "File on a Web (HTTP) server";
$langLocalFileTips		= "File on the platform server";
$langPostFileTips		= "File on your local computer";

$langOtherCategory	= "Other category";

// create_course.php
$langLn="Language";


$langCreateSite="Create an area";
$langFieldsRequ="All fields required";
$langTitle="Area title";
$langEx="e.g. <i>Innovation management</i>";
$langFac="Category";
$langTargetFac="This is the department or any other category where the area is delivered"; 
$langCode="Area code";
$langMax = "max. 20 characters, e.g. <i>INNOV21</i>";
$langDoubt="If you doubt on your area code, consult ";
$langProgram="Area Program</a>. If your area has no code, whatever the reason, invent one. For instance <i>INNOVATION</i> if the area is about Innovation Management";
$langProfessors="Leaders";
$langExplanation="Once you click OK, a website with Forum, Agenda, Document manager etc. will be created. Your login, as creator of the website, allows you to modify it along your own requirements.";
$langEmpty="You left some fields empty.<br>Use the <b>Back</b> button on your browser and try again.<br>If you ignore your area code, see the area Program";
$langCodeTaken="This area code is already in use.  <br>Use the <b>Back</b> button on your browser and try again";


// tables MySQL
$langFormula="Yours sincerely";
$langForumLanguage="english";	// other possibilities are english, spanish (this uses phpbb language functions)
$langTestForum="Test forum";
$langDelAdmin="Remove this through the forum admin tool";
$langMessage="When you remove the test forum, it will remove all messages in that forum too.";
$langExMessage="Example message";
$langAnonymous="Anonymous";
$langExerciceEx="Sample test";
$langAntique="Irony";
$langSocraticIrony="Socratic irony is...";
$langManyAnswers="(more than one answer can be true)";
$langRidiculise="Ridiculise one's interlocutor in order to have him concede he is wrong.";
$langNoPsychology="No. Socratic irony is not a matter of psychology, it concerns argumentation.";
$langAdmitError="Admit one's own errors to invite one's interlocutor to do the same.";
$langNoSeduction="No. Socratic irony is not a seduction strategy or a method based on the example.";
$langForce="Compell one's interlocutor, by a series of questions and sub-questions, to admit he doesn't know what he claims to know.";
$langIndeed="Indeed. Socratic irony is an interrogative method. The Greek \"eirotao\" means \"ask questions\"";
$langContradiction="Use the Principle of Non Contradiction to force one's interlocutor into a dead end.";
$langNotFalse="This answer is not false. It is true that the revelation of the interlocutor's ignorance means showing the contradictory conclusions where lead his premisses.";



// Home Page MySQL Table "accueil"
$langAgenda="Agenda";
$langLinks="Links";
$langDoc="Documents";
$langScormtool="Learning Path";
$langScormbuildertool="Scorm Path builder";
$langPathbuildertool="Learning Path builder";
$langVideo="Video";
$langWorks="Assignments";
$langCourseProgram="Area program";
$langAnnouncements="Announcements";
$langUsers="Users";
$langForums="Forums";
$langExercices="Tests";
$langStatistics="Tracking";
$langAddPageHome="Upload page and link to Homepage";
$langLinkSite="Add a link";
$langModifyInfo="Area settings";
$langOnlineConference="Conference";


// Other SQL tables
$langAgendaTitle="Tuesday the 11th of December - First meeting. Room: LIN 18";
$langAgendaText="General introduction to project management";
$langMicro="Street interviews";
$langVideoText="This is an example of a RealVideo file. You can upload any audio and video file type (.mov, .rm, .mpeg...), as far as your members have the corresponding plug-in to read them";
$langAgendaCreationTitle="Area creation";
$langAgendaCreationContenu="This area has been created on this moment.";
$langGoogle="Quick and powerful search engine";
$langIntroductionText="This is the introduction text. To replace it by your own text, click below on <b>the pencil</b>.";
$langOnlineDescription="This is an example of description for the Conference tool";
$langIntroductionTwo="This page allows any user or group to upload a document on the Area.";
$langCourseDescription="Write here the description that will appear in the area list.";
$langProfessor="Leader";
$langAnnouncementEx="This is an announcement example. Only area leaders are allowed to publish announcements.";
$langJustCreated="You just created the Area";
$langEnter="Back to my areas list";
$langMillikan="Millikan experiment";
$langCourseDesc = "Area description";
 // Groups
$langGroups="Groups";
$langCreateCourseGroups="Groups";
$langCatagoryMain="Main";
$langCatagoryGroup="Groups forums";
$langChat ="Chat";
$langDropbox = "Dropbox";

$langOnly = "Only";
$langRandomLanguage = "Shuffle selection in aivailable languages";

?>
