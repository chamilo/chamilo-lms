<?php // $Id: course_info.inc.php 950 2004-04-01 20:24:14Z olivierb78 $
/*
      +----------------------------------------------------------------------+
      | DOKEOS 1.5 $Revision: 950 $                                          |
      +----------------------------------------------------------------------+
      | Copyright (c) 2001, 2003 Universite catholique de Louvain (UCL)      |
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
 */

/***************************************************************
*                   Language translation
****************************************************************
GOAL
****
Translate the interface in chosen language
*****************************************************************/

$langModifInfo="Propriétés de l'espace courant";
$langModifDone="Les informations ont été modifiées";
$langHome="Retour à la page d'accueil";
$langCode="Code de cet espace";
$langDelCourse="Supprimer cet espace";
$langProfessor="Responsable";
$langProfessors="Co-responsables";
$langTitle="Intitulé";
$langFaculty="Catégorie";
$langDescription="Description";
$langConfidentiality="Confidentialité";
$langPublic="Accès public (depuis la page d'accueil du portail, sans identifiant)";
$langPrivOpen="Accès privé, inscription ouverte";
$langPrivate="Accès privé (site réservé aux personnes figurant dans la liste <a href=../user/user.php>utilisateurs</a>)";
$langForbidden="Accès non autorisé";
$langLanguage="Langue";
$langConfTip="Par défaut votre espace est public. Mais vous pouvez définir le niveau de confidentialité ci-dessus.";
$langTipLang="Cette langue vaudra pour tous les visiteurs de votre espace.";

// Change Home Page
$langAgenda="Agenda";
$langLink="Liens";
$langDocument="Documents";
$langVid="Vidéo";
$langWork="Travaux";
$langProgramMenu="Cahier des charges";
$langAnnouncement="Annonces";
$langUser="Membres";
$langForum="Forums";
$langExercise="Exercices";
$langStats="Statistiques";
$langGroups ="Groupes";
$langChat ="Discussion";
$langUplPage="Déposer page et lier à l\'accueil";
$langLinkSite="Ajouter un lien sur la page d\'accueil";
$langModifGroups="Groupes";

// delete_course.php
$langDelCourse="Supprimer l'espace";
$langCourse="L'espace ";
$langHasDel="a été supprimé";
$langBackHome="Retour à la page d'accueil de ";
$langByDel="En supprimant cet espace, vous supprimerez tous les documents
qu'il contient et désinscrirez tous les étudiants qui y sont inscrits. <p>Voulez-vous réellement supprimer cet espace";
$langY="OUI";
$langN="NON";

$langDepartmentUrl = "URL du département";
$langDepartmentUrlName = "Département";
$langDescriptionCours  = "Description de cet espace";

$langArchive="Archive";
$langArchiveCourse = "Archivage de cet espace";
$langRestoreCourse = "Restauration d'un espace";
$langRestore="Restaurer";
$langCreatedIn = "créé dans";
$langCreateMissingDirectories ="Création des répertoires manquants";
$langCopyDirectoryCourse = "Copie des fichiers de l'espace";
$langDisk_free_space = "Espace libre";
$langBuildTheCompressedFile ="Création du fichier compressé";
$langFileCopied = "fichier copié";
$langArchiveLocation = "Emplacement de l'archive";
$langSizeOf ="Taille de";
$langArchiveName ="Nom de l'archive";
$langBackupSuccesfull = "Archivé avec succès";
$langBUCourseDataOfMainBase = "Archivage des données du cours dans la base de données principale pour";
$langBUUsersInMainBase = "Archivage des données des membres dans la base de données principale pour";
$langBUAnnounceInMainBase="Archivage des données des annonces dans la base de données principale pour";
$langBackupOfDataBase="Archivage de la base de données";
$langBackupCourse="Archiver cet espace";

$langCreationDate = "Créé";
$langExpirationDate  = "Date d'expiration";
$langPostPone = "Post pone";
$langLastEdit = "Dernière édition";
$langLastVisit = "Dernière visite";

$langSubscription="Inscription";
$langCourseAccess="Accès à l'espace";

$langDownload="Télécharger";
$langConfirmBackup="Voulez-vous vraiment archiver cet espace";

$langCreateSite="Créer un espace";

$langRestoreDescription="L'espace se trouve dans une archive que vous pouvez sélectionner ci-dessous.<br><br>
Lorsque vous aurez cliqué sur &quot;Restaurer&quot;, l'archive sera décompressée et l'espace recréé.";
$langRestoreNotice="Ce script ne permet pas encore la restauration automatique des membres, mais les données sauvegardées dans le fichier &quot;users.csv&quot; sont suffisantes pour que l'administrateur puisse effectuer cette opération manuellement.";
$langAvailableArchives="Liste des archives disponibles";
$langNoArchive="Aucune archive n'a été sélectionnée";
$langArchiveNotFound="Archive introuvable";
$langArchiveUncompressed="L'archive a été décompressée et installée.";
$langCsvPutIntoDocTool="Le fichier &quot;users.csv&quot; a été placé dans l'outil Documents.";
?>
