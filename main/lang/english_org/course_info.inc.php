<?php // $Id: course_info.inc.php 1997 2004-07-07 14:55:42Z olivierb78 $
/*
      +----------------------------------------------------------------------+
      | CLAROLINE version 1.3.0 $Revision: 1997 $                            |
      +----------------------------------------------------------------------+
      | Copyright (c) 2001, 2003 Universite catholique de Louvain (UCL)      |
      +----------------------------------------------------------------------+
      |   English Translation                                                |
      +----------------------------------------------------------------------+
      |   This program is free software; you can redistribute it and/or      |
      |   modify it under the terms of the GNU General Public License        |
      |   as published by the Free Software Foundation; either version 2     |
      |   of the License, or (at your option) any later version.             |
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

$langOtherCategory	= "Other category";

$langModifInfo="Area settings";
$langModifDone="The information has been modified";
$langHome="Back to HomePage";
$langCode="Area code";
$langDelCourse="Delete this area";
$langProfessor="Leader";
$langProfessors="Leaders";
$langTitle="Title";
$langFaculty="Category";
$langDescription="Description";
$langConfidentiality="Confidentiality";
$langPublic="Public access from portal home page even without login";
$langPrivOpen="Private access, registration open";
$langPrivate="Private access (site accessible only to people on the <a href=../user/user.php>User list</a>)";
$langForbidden="Not allowed";
$langLanguage="Language";
$langConfTip="By default your area is public. But you can define the level of confidentiality above.";
$langTipLang="This language will be valid for every visitor of your area's website.";

// Change Home Page
$langAgenda="Agenda";
$langLink="Links";
$langDocument="Documents";
$langVid="Video";
$langWork="Contributions";
$langProgramMenu="Area program";
$langAnnouncement="Announcements";
$langUser="Users";
$langForum="Forums";
$langExercise="Tests";
$langStats="Statistics";
$langGroups ="Groups";
$langChat ="Discussion";
$langUplPage="Upload page and link to Home Page";
$langLinkSite="Add link to page on Home Page";
$langModifGroups="Groups";

// delete_course.php
$langDelCourse="Delete the whole Area";
$langCourse="The area ";
$langHasDel="has been deleted";
$langBackHome="Back to Home Page of ";
$langByDel="Deleting this area will permanently delete all the documents it contains and unregister all its members (not remove them from other areas).<p>Do you really want to delete it?";
$langY="YES";
$langN="NO";

$langDepartmentUrl = "Department URL";
$langDepartmentUrlName = "Department";
$langDescriptionCours  = "Area description";
$langArchive="Archive";
$langArchiveCourse = "Area backup";
$langRestoreCourse = "Restore a area";
$langRestore="Restore";
$langCreatedIn = "created in";
$langCreateMissingDirectories ="Creation of missing directories";
$langCopyDirectoryCourse = "Copy of area files";
$langDisk_free_space = "Free disk space";
$langBuildTheCompressedFile ="Creation of backup file";
$langFileCopied = "file copied";
$langArchiveLocation="Archive location";
$langSizeOf ="Size of";
$langArchiveName ="Archive name";
$langBackupSuccesfull = "Backup successfull";
$langBUCourseDataOfMainBase = "Backup of area data in main database for";
$langBUUsersInMainBase = "Backup of user data in main database for";
$langBUAnnounceInMainBase="Backup of announcements data in main database for";
$langBackupOfDataBase="Backup of database";
$langBackupCourse="Archive this Area";

$langCreationDate = "Created";
$langExpirationDate  = "Expiration date";
$langPostPone = "Post pone";
$langLastEdit = "Last edit";
$langLastVisit = "Last visit";

$langSubscription="Subscription";
$langCourseAccess="Area access";

$langDownload="Download";
$langConfirmBackup="Do you really want to backup the Area?";

$langCreateSite="Create an area";

$langRestoreDescription="The area is in an archive file which you can select below.<br><br>
Once you click on &quot;Restore&quot;, the archive will be uncompressed and the area recreated.";
$langRestoreNotice="This script doesn't allow yet to automatically restore users, but data saved in &quot;users.csv&quot; are sufficient for the administrator to do it manually.";
$langAvailableArchives="Available archives list";
$langNoArchive="No archive has been selected";
$langArchiveNotFound="The archive has not been found";
$langArchiveUncompressed="The archive has been uncompressed and installed.";
$langCsvPutIntoDocTool="The file &quot;users.csv&quot; has been put into Documents tool.";
?>