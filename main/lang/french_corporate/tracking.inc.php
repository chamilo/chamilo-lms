<?php
/*
      +----------------------------------------------------------------------+
      | CLAROLINE version 1.3.0 $Revision: 3 $                            |
      +----------------------------------------------------------------------+
      | Copyright (c) 2001, 2002 Universite catholique de Louvain (UCL)      |
      +----------------------------------------------------------------------+
      |   $Id: tracking.inc.php 3 2004-01-23 14:01:45Z olivierb78 $         |
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

FRENCH
*****************************************************************/

/* general */
$langToolName="Statistiques";
$langShowAll = "Montrer tout";
$langShowNone = "Cacher tout";

$langCourseStats = "Statistiques du site";
$langToolsAccess = "Accès aux outils";
$langCourseAccess = "Accès à ce site";
$langLinksAccess = "Liens";
$langDocumentsAccess = "Documents";

$langLinksDetails = "Liens visités par le cadre";
$langWorksDetails = "Travaux postés par le cadre au nom de 'Auteurs'";
$langLoginsDetails = "Cliquez sur le nom du mois pour plus de détails";
$langDocumentsDetails = "Documents téléchargés par le cadre"; 
$langExercicesDetails = "Résultats des Quizz effectués";

$langBackToList = "Retourner à la liste des utilisateurs";
$langDetails = "Détails";
$langClose = "Fermer";

/* subtitles */
$langStatsOfCourse = "Statistiques du site";
/* course */
$langCountUsers = "Nombre d'utilisateurs inscrits";

/* course access */
$langCountToolAccess = "Nombre total de connexions à ce site";


/* logins */
$langLoginsTitleMonthColumn = "Mois";
$langLoginsTitleCountColumn = "Nombre de logins";

/* tools */
$langToolTitleToolnameColumn = "Nom de l'outil";
$langToolTitleUsersColumn = "Clics des inscrits";
$langToolTitleCountColumn = "Total des clics";

/* links*/
$langLinksTitleLinkColumn = "Lien";
$langLinksTitleUsersColumn = "Clics des inscrits";
$langLinksTitleCountColumn = "Total des clics";

/* exercices */
$langExercicesTitleExerciceColumn = "Quizz";
$langExercicesTitleScoreColumn = "Résultat";

/* documents */
$langDocumentsTitleDocumentColumn = "Document";
$langDocumentsTitleUsersColumn = "Téléchargements des inscrits";
$langDocumentsTitleCountColumn = "Total des téléchargements";


/* works */
$langWorkTitle = "Titre";
$langWorkAuthors = "Auteur";
$langWorkDescription = "Description";

$langDate = "Date";

/* user list */
$informationsAbout = "Tracking de";
$langUserName = "Nom d'utilisateur";
$langFirstName = "Nom";
$langLastName = "Prénom";
$langEmail = "Email";
$langNoEmail = "Pas d'adresse email";
/* others */
$langNoResult = "Pas de résultat";

$langCourse = "Site";

$langHits = "Hits";
$langTotal = "Total";
$langHour = "Heure";
$langDay = "Jour";
$langLittleHour = "h.";
$langLast31days = "Ces derniers 31 jours";
$langLast7days = "Ces derniers 7 jours";
$langThisday  = "Aujourd'hui";

/* perso stats */
$langLogins = "Derniers logins";
$langLoginsExplaination = "Voici la liste de vos derniers logins ainsi que les outils utilisés pendant ces sessions.";

$langExercicesResults = "Résultats des exercices effectués";

$langVisits = "visites";
$langAt = "à";
$langLoginTitleDateColumn = "Date";
$langLoginTitleCountColumn = "Visites";

/* tutor view */
$langLoginsAndAccessTools = "Logins et accès aux outils";
$langWorkUploads = "Documents envoyés";
$langErrorUserNotInGroup = "Cet utilisateur n'est pas dans votre groupe." ;
$langListStudents = "Liste des utilisateurs de ce groupe";

/* details page */

$langPeriodDay = "Jour";
$langPeriodWeek = "Semaine";
$langPeriodMonth = "Mois";

$langNextDay = "Jour suivant";
$langPreviousDay = "Jour précédent";
$langNextWeek = "Semaine suivante";
$langPreviousWeek = "Semaine précédente";
$langNextMonth = "Mois suivant";
$langPreviousMonth = "Mois précédent";

$langViewToolList = "Voir la liste de tous les outils";
$langToolList = "Liste de tous les outils";

$langFrom = "De";
$langTo = "à";

/* for interbredcrumps */
$langGroups = "Groupes";
$langGroupSpace = "Espace de groupe";
$langModifyProfile = "Modifier mon profil";





/* admin stats */
$langAdminToolName = "Statistiques d'administration";
$langPlatformStats = "Statistiques du campus virtuel des cadres";
$langStatsDatabase = "Statistiques de la base de données";
$langPlatformAccess = "Accès au campus";
$langPlatformCoursesAccess = "Accès aux sites";
$langPlatformToolAccess = "Accès aux outils";
$langHardAndSoftUsed = "Pays Fournisseurs d'accès Navigateurs Os Référants";
$langStrangeCases = "Cas particuliers";
$langStatsDatabaseLink = "Cliquez ici";
$langCountCours = "Nombre de sites";
$langCountUsers = "Nombre d'utilisateurs";
$langCountCourseByFaculte  = "Nombre de sites par catégorie";
$langCountCourseByLanguage = "Nombre de sites par langue";
$langCountCourseByVisibility = "Nombre de sites par visibilité";
$langCountUsersByCourse = "Nombre d'utilisateurs par site";
$langCountUsersByFaculte = "Nombre d'utilisateurs par catégorie";
$langCountUsersByStatus = "Nombre d'utilisateurs par statut";
$langCourses = "Sites";
$langUsers = "Utilisateurs";
$langAccess = "Accès";
$langCountries = "Pays";
$langProviders = "Fournisseurs d'accès";
$langOS = "OS";
$langBrowsers = "Navigateurs";
$langReferers = "Référants";
$langAccessExplain = "Lorsqu'un utilisateur accède au campus des cadres";
$langLogins = "Logins";
$langTotalPlatformAccess = "Total";
$langTotalPlatformLogin = "Total";
$langMultipleLogins = "Comptes avec le même <i>nom d'utilisateur</i>";
$langMultipleUsernameAndPassword = "Comptes avec le même <i>nom d'utilisateur</i> et <i>mot de passe</i>";
$langMultipleEmails = "Comptes avec le même <i>Email</i>";
$langCourseWithoutProf = "Sites sans modérateur";
$langCourseWithoutAccess = "Sites inutilisés";
$langLoginWithoutAccess  = "Comptes inutilisés";
$langAllRight = "Tout va bien.";
$langDefcon = "Aie , cas particuliers détectés !";
$langNULLValue = "Vide (ou <i>NULL</i>)";
?>