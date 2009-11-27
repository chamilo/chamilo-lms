<?php // $Id: admin.inc.php 3402 2005-02-17 11:44:22Z olivierb78 $
/*
      +----------------------------------------------------------------------+
      | CLAROLINE version 1.4.* $Revision: 3402 $                            |
      +----------------------------------------------------------------------+
      | Copyright (c) 2001, 2003 Universite catholique de Louvain (UCL)      |
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

$langOtherCategory	= "Autre catégorie";
$langSendMailToUsers = "Envoyer un mail aux utilisateurs";

$langExampleXMLFile = "Exemple de fichier XML";
$langExampleCSVFile = "Exemple de fichier CSV";

$langCourseBackup="Sauvegarder (archiver) ce cours";

$langCourseCode="Code du cours";
$langCourseTitular="Responsable du cours";
$langCourseTitle="Intitulé du cours";
$langCourseFaculty="Catégorie du cours";
$langCourseDepartment="Département du cours";
$langCourseDepartmentURL="URL du département";
$langCourseLanguage="Langue du cours";
$langCourseAccess="Accès à ce cours";
$langCourseSubscription="Inscription au cours";
$langPublicAccess="Accès public";
$langPrivateAccess="Accès privé";
$langFromHomepageWithoutLogin="depuis la page d'accueil du portail, sans identifiant";
$langSiteReservedToPeopleInMemberList="site réservé aux personnes figurant dans la liste membres";
$langCode="Code";
$langUsers="Utilisateurs";
$langLanguage="Langue";
$langCategory="Catégorie";

$langClassName="Nom de la classe";

$langDBManagementOnlyForServerAdmin="La gestion des bases de données n'est accessible qu'à l'administrateur du serveur";

$langShowUsersOfCourse="Afficher les utilisateurs inscrits au cours";
$langShowClassesOfCourse="Afficher les classes inscrites au cours";
$langShowGroupsOfCourse="Afficher les groupes du cours";
$langOfficialCode="Code officiel";
$langFirstName="Prénom";
$langLastName="Nom";
$langLoginName="Identifiant";
$langPhone="Téléphone";
$langPhoneNumber="Numéro de téléphone";
$langStatus="Statut";
$langEmail="Adresse e-mail";
$langPlatformAdmin="Administrateur de la plateforme";
$langActions="Actions";
$langAddToCourse="Inscrire à un cours";
$langDeleteFromPlatform="Supprimer de la plateforme";
$langDeleteCourse="Supprimer ce(s) cours";
$langDeleteFromCourse="Désinscrire de ce(s) cours";
$langDeleteSelectedClasses="Supprimer les classes sélectionnées";
$langDeleteSelectedGroups="Supprimer les groupes sélectionnés";
$langAdministrator="Administrateur";
$langTeacher="Enseignant/Chef";
$langUser="Utilisateur";
$langAddPicture="Ajouter une photo";
$langChangePicture="Changer la photo";
$langDeletePicture="Supprimer la photo";
$langAddUsers="Ajouter des utilisateurs";
$langAddGroups="Ajouter des groupes";
$langAddClasses="Ajouter des classes";
$langAddCourse="Créer un cours";
$langExportUsers="Exporter les utilisateurs";
$langKeyword="Mot-clé";
$langGroupName="Nom du groupe";
$langGroupTutor="Modérateur du groupe";
$langGroupForum="Forum du groupe";
$langGroupDescription="Description du groupe";
$langNumberOfParticipants="Nombre de participants";
$langNumberOfUsers="Nombre d'utilisateurs";
$langMaximum="maximum";
$langMaximumOfParticipants="Nombre maximum de participants";
$langParticipants="participants";
$langGroup="Groupe";

$langFirstLetterClass="Première lettre (classe)";
$langFirstLetterUser="Première lettre (nom)";
$langFirstLetterCourse="Première lettre (code)";

$langCatCodeAlreadyUsed="Une catégorie porte déjà ce code !";
$langPleaseEnterCategoryInfo="Veuillez introduire le code et le nom de la catégorie !";

$langModifyUserInfo="Modifier les informations d'un utilisateur";
$langModifyClassInfo="Modifier les informations d'une classe";
$langModifyGroupInfo="Modifier les informations d'un groupe";
$langModifyCourseInfo="Modifier les informations d'un cours";
$langPleaseEnterClassName="Veuillez introduire le nom de la classe !";
$langPleaseEnterLastName="Veuillez introduire le nom de l'utilisateur !";
$langPleaseEnterFirstName="Veuillez introduire le prénom de l'utilisateur !";
$langPleaseEnterValidEmail="Veuillez introduire une adresse e-mail valide !";
$langPleaseEnterValidLogin="Veuillez introduire un identifiant valide !";
$langPleaseEnterCourseCode="Veuillez introduire le code du cours !";
$langPleaseEnterTitularName="Veuillez introduire le nom du responsable !";
$langPleaseEnterCourseTitle="Veuillez introduire l'intitulé du cours !";
$langAcceptedPictureFormats="Les formats acceptés sont JPG, PNG et GIF !";
$langLoginAlreadyTaken="Cet identifiant est déjà pris !";

$langImportUserListXMLCSV="Importer une liste d'utilisateurs au format XML/CSV";
$langExportUserListXMLCSV="Exporter la liste des utilisateurs dans un fichier XML/CSV";
$langOnlyUsersFromCourse="Seulement les utilisateurs du cours";
$langUserListHasBeenExportedTo="La liste des utilisateurs a été exportée vers";

$langAddClassesToACourse="Inscrire des classes d'utilisateurs à un cours";
$langAddUsersToACourse="Inscrire des utilisateurs à un cours";
$langAddUsersToAClass="Inscrire des utilisateurs dans une classe";
$langAddUsersToAGroup="Inscrire des utilisateurs à un groupe";
$langAtLeastOneClassAndOneCourse="Vous devez sélectionner au moins une classe et un cours !";
$langAtLeastOneUser="Vous devez sélectionner au moins un utilisateur !";
$langAtLeastOneUserAndOneCourse="Vous devez sélectionner au moins un utilisateur et un cours !";
$langClassList="Liste des classes";
$langUserList="Liste des utilisateurs";
$langCourseList="Liste des cours";
$langAddToThatCourse="Inscrire à ce(s) cours";
$langAddToClass="Inscrire dans la classe";
$langRemoveFromClass="Désinscrire de la classe";
$langAddToGroup="Inscrire au groupe";
$langRemoveFromGroup="Désinscrire du groupe";

$langUsersOutsideClass="Utilisateurs en dehors de la classe";
$langUsersInsideClass="Utilisateurs dans la classe";
$langUsersOutsideGroup="Utilisateurs en dehors du groupe";
$langUsersInsideGroup="Utilisateurs dans le groupe";

$langImportFileLocation="Emplacement du fichier CSV / XML";
$langFileType="Type du fichier";
$langOutputFileType="Type du fichier de destination";
$langMustUseSeparator="doit utiliser le caractère ';' comme séparateur";
$langCSVMustLookLike="Le fichier CSV doit être dans le format suivant";
$langXMLMustLookLike="Le fichier XML doit être dans le format suivant";
$langMandatoryFields="les champs en <b>gras</b> sont obligatoires";
$langNotXML="Le fichier spécifié n'est pas au format XML !";
$langNotCSV="Le fichier spécifié n'est pas au format CSV !";
$langNoNeededData="Le fichier spécifié ne contient pas toutes les données nécessaires !";
$langMaxImportUsers="Vous ne pouvez pas importer plus de 500 utilisateurs à la fois !";

$langAdminDatabases="Bases de données (phpMyAdmin)";
$langAdminUsers="Utilisateurs";
$langAdminClasses="Classes d'utilisateurs";
$langAdminGroups="Groupes d'utilisateurs";
$langAdminCourses="Cours";
$langAdminCategories="Catégories de cours";
$langSubscribeUserGroupToCourse="Inscrire un utilisateur / groupe à un cours";
$langAddACategory="Ajouter une catégorie";
$langInto="dans";
$langNoCategories="Il n'y a aucune catégorie ici";
$langAllowCoursesInCategory="Permettre l'ajout de cours dans cette catégorie ?";
$langGoToForum="Aller sur le forum";

$langCategoryCode="Code de la catégorie";
$langCategoryName="Nom de la catégorie";

$langCourses="cours";
$langCategories="catégories";

$langEditNode = "Modifier cette catégorie";
$langOpenNode = "Ouvrir cette catégorie";
$langDeleteNode = "Supprimer cette catégorie";
$langAddChildNode ="Ajouter une sous-catégorie";
$langViewChildren = "Voir les fils";
$langTreeRebuildedIn = "Arborescence reconstruite en";
$langTreeRecountedIn = "Arborescence recomptée en";
$langRebuildTree="Reconstruire l'arborescence";
$langRefreshNbChildren="Raffraichir le nombre de fils";
$langShowTree = "Voir l'arborescence";
$langBack = "Retour en arrière";
$langLogDeleteCat  = "Catégorie supprimée";
$langRecountChildren = "Recompter les fils";
$langUpInSameLevel ="Monter au même niveau";

$langSeconds="secondes";
$langIn="Dans";

$langMailTo = "Contact : ";
$lang_no_access_here ="Pas d'accès ";
$lang_php_info = "information sur le système php";


$langAdminBy = "Administration  par";
$langAdministrationTools = "Outils d'administration";
$langTools = "Outils";
$langTechnicalTools = "administration technique";
$langConfig = "Configuration du système";
$langState = "Etat du système";
$langDevAdmin ="Administration du développement";
$langLinksToClaroProjectSite ="Liens vers le site du projet";
$langNomOutilTodo 		= "Gestion des suggestions"; // to do
$langNomPageAdmin 		= "Administration";
$langSysInfo  			= "Info Système";        // Show system status
$langCheckDatabase  	= "Vérificateur d'état des bases";        // Check Database
$langDiffTranslation 	= "Comparaison des traductions"; // diff of translation
$langStatOf 			= "Statistiques de "; // Stats of...
$langSpeeSubscribe 		= "Inscription Rapide comme Testeur d'un cours";
$langLogIdentLogout 	= "Liste des logins";
$langLogIdentLogoutComplete = "Liste étendue des logins";

// Stat
$langStatistiques = "Statistiques";

$langNbProf = "Nombre de responsables";
$langNbStudents = "Nombre de membres";
$langNbLogin = "Nombre de login";
$langToday   ="Aujourd'hui";
$langLast7Days ="Ces 7 derniers jours";
$langLast30Days ="Ces 30 derniers jours";


$langNbAnnoucement = "Nombre d'annonces";


// Check data base
$langCheckDatabase ="Analyse de la Base de données";

// Check Data base
$langPleaseCheckConfigForMainDataBaseName = "Verifiez les variables
<br>
Nom de base de donnée dans
<br>";
$langBaseFound ="trouvée
<br>
Vérification des tables de cette base";
$langNeeded = "obligatoire";
$langNotNeeded = "non exigé";
$langArchive   ="archive";
$langUsed      ="utilisé";
$langPresent  ="Ok";
$langCreateMissingNow = "Voulez vous créer les tables manquantes maintenant ?";
$langMissing   ="manquant";
$langCheckingCourses ="Vérification des espaces";
$langExist     ="existe";


// create  Claro table

$langCreateClaroTables ="Creation des tables de la base principale";
$langTableStructureDontKnow ="Structure of this table unknown";

$langSETLOCALE="FRENCH";
// UNIX TIME SETTINGS, "15h00" instead of "3pm", for instance, "ENGLISH" is a possibility
$langManage				= "Gestion du portail";


$langMaintenance 	= "Maintenance";
$langUpgrade		= "Upgrade de la plateforme";
$langWebsite		= "Dokeos website";
$langDocumentation	= "Documentation";
$langForum			= "Forum";
$langContribute		= "Contribute";

$langStatistics = "Statistiques";
$langYourDokeosUses = "Votre installation de Dokeos utilise actuellement";
$langOnTheHardDisk = "sur le disque dur";
?>