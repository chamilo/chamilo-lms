<?php # $Id: tracking.inc.php 950 2004-04-01 20:24:14Z olivierb78 $
/*
      +----------------------------------------------------------------------+
      | CLAROLINE version 1.5.0 $Revision: 950 $                            |
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
$langTrackingDisabled = "Le système de statistiques et de tracking a été désactivé par l'administrateur système.";
$langToolName="Statistiques";
$langShowAll = "Montrer tout";
$langShowNone = "Cacher tout";

$langCourseStats = "Statistiques du cours";
$langToolsAccess = "Accès aux outils";
$langCourseAccess = "Accès à cet espace";
$langLinksAccess = "Liens";
$langDocumentsAccess = "Documents";
$langScormAccess = "Scorm contenus";

$langLinksDetails = "Liens visités par le membre";
$langWorksDetails = "Contributions postées par le membre au nom de 'Auteurs'";
$langLoginsDetails = "Cliquez sur le nom du mois pour plus de détails";
$langDocumentsDetails = "Documents téléchargés par l'étudiant";
$langExercicesDetails = "Résultats des tests effectués";

$langBackToList = "Retourner à la liste des membres";
$langDetails = "Détails";
$langClose = "Fermer";

/* subtitles */
$langStatsOfCourse = "Statistiques de l'espace";
$langStatsOfUser = "Statistiques d'un membre";
$langStatsOfCampus = "Statistiques du portail";
/* course */
$langCountUsers = "Nombre d'utilisateurs inscrits";

/* course access */
$langCountToolAccess = "Nombre total de connexions à cet espace";

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
$langExercicesTitleExerciceColumn = "Test";
$langExercicesTitleScoreColumn = "Résultat";

/* documents */
$langDocumentsTitleDocumentColumn = "Document";
$langDocumentsTitleUsersColumn = "Téléchargements des inscrits";
$langDocumentsTitleCountColumn = "Total des téléchargements";

/* scorm */
$langScormContentColumn="Nom du contenu";
$langScormStudentColumn="Étudiants";
$langScormTitleColumn="Nom du cours";
$langScormStatusColumn="Status du cours";
$langScormScoreColumn="Résultat du cours";
$langScormTimeColumn="Durée";
$langScormNeverOpened="Cette leçon n'a jamais été ouverte par l'étudiant.";

/* works */
$langWorkTitle = "Titre";
$langWorkAuthors = "Auteurs";
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

$langCourse = "Espace";

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

$langExercicesResults = "Résultats des tests effectués";

$langVisits = "visites";
$langAt = "à";
$langLoginTitleDateColumn = "Date";
$langLoginTitleCountColumn = "Visites";

/* tutor view */
$langLoginsAndAccessTools = "Logins et accès aux outils";
$langWorkUploads = "Contributions envoyées";
$langErrorUserNotInGroup = "Ce membre n'est pas dans votre groupe." ;
$langListStudents = "Liste des membres de ce groupe";

/* details page */
$langPeriodHour = "Heure";
$langPeriodDay = "Jour";
$langPeriodWeek = "Semaine";
$langPeriodMonth = "Mois";
$langPeriodYear = "Année";

$langNextDay = "Jour suivant";
$langPreviousDay = "Jour précédent";
$langNextWeek = "Semaine suivante";
$langPreviousWeek = "Semaine précédente";
$langNextMonth = "Mois suivant";
$langPreviousMonth = "Mois précédent";
$langNextYear = "Année suivante";
$langPreviousYear = "Année précédente";

$langViewToolList = "Voir la liste de tous les outils";
$langToolList = "Liste de tous les outils";

$langFrom = "Du";
$langTo = "au";


/* traffic_details */
$langPeriodToDisplay = "Période";
$langDetailView = "Niveau de détail";

/* for interbredcrumps */
$langBredCrumpGroups = "Groupes";
$langBredCrumpGroupSpace = "Espace de groupe";
$langBredCrumpUsers = "Membres";

/* admin stats */
$langAdminToolName = "Statistiques d'administration";
$langPlatformStats = "Statistiques du portail";
$langStatsDatabase = "Statistiques de la base de données";
$langPlatformAccess = "Accès au portail";
$langPlatformCoursesAccess = "Accès aux espaces";
$langPlatformToolAccess = "Accès aux outils";
$langHardAndSoftUsed = "Pays Fournisseurs d'accès Navigateurs Os Référants";
$langStrangeCases = "Cas particuliers";
$langStatsDatabaseLink = "Cliquez ici";
$langCountCours = "Nombre d'espaces";
$langCountUsers = "Nombre de membres";
$langCountCourseByFaculte  = "Nombre d'espaces par catégorie";
$langCountCourseByLanguage = "Nombre d'espaces par langue";
$langCountCourseByVisibility = "Nombre d'espaces par visibilité";
$langCountUsersByCourse = "Nombre d'utilisateurs par espace";
$langCountUsersByFaculte = "Nombre d'utilisateurs par catégorie";
$langCountUsersByStatus = "Nombre d'utilisateurs par statut";
$langCourses = "Espaces";
$langUsers = "Utilisateurs";
$langAccess = "Accès";
$langCountries = "Pays";
$langProviders = "Fournisseurs d'accès";
$langOS = "OS";
$langBrowsers = "Navigateurs";
$langReferers = "Référants";
$langAccessExplain = "Lorsqu'un utilisateur accède au portail";
$langLogins = "Logins";
$langTotalPlatformAccess = "Total";
$langTotalPlatformLogin = "Total";
$langMultipleLogins = "Comptes avec le même <i>nom d'utilisateur</i>";
$langMultipleUsernameAndPassword = "Comptes avec le même <i>pseudo</i> et <i>mot de passe</i>";
$langMultipleEmails = "Comptes avec le même <i>Email</i>";
$langCourseWithoutProf = "Espaces sans responsable";
$langCourseWithoutAccess = "Espaces inutilisés";
$langLoginWithoutAccess  = "Comptes inutilisés";
$langAllRight = "Tout va bien.";
$langDefcon = "Aie , cas particuliers détectés !";
$langNULLValue = "Vide (ou <i>NULL</i>)";
$langTrafficDetails = "Détails du trafic";
?>
