<?php # $Id: registration.inc.php 950 2004-04-01 20:24:14Z olivierb78 $
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

// lang vars
$langAdminOfCourse		= "admin";  //
$langSimpleUserOfCourse = "normal"; // strings for synopsis
$langIsTutor  			= "mod&eacute;rateur"; //

$langCourseCode			= "Espace";	// strings for list Mode
$langParamInTheCourse 	= "Statut"; //

$langSummaryTable = "Cette table dresse la liste des membres de l'espace.";
$langSummaryNavBar = "Barre de navigation"; 
$langAddNewUser = "Ajouter un membre au système";
$langMember ="inscrit";

$langDelete	="supprimer";
$langLock	= "bloquer";
$langUnlock	= "liberer";

$langHaveNoCourse = "Pas d'espace";

$langFirstname = "Prénom";
$langLastname = "Nom";
$langEmail = "Adresse e-mail";
$langAbbrEmail = "Email";
$langRetrieve ="Retrouver  mes paramètres d'identification";
$langMailSentToAdmin = "Un email à été adressé à l'administrateur du système.";
$langAccountNotExist = "Ce compte semble ne pas exister.<BR>".$langMailSentToAdmin." Il fera une recherche manuelle.<BR><BR>";
$langAccountExist = "Ce compte semble exister.<BR> Un email à été adressé à l'administrateur. <BR><BR>";
$langWaitAMailOn = "Attendez vous à une réponse sur ";
$langCaseSensitiveCaution = "Le système fait la différence entre les minuscules et les majuscules.";
$langDataFromUser = "Données envoyées par le membre";
$langDataFromDb = "Données correspondantes dans la base de donnée";
$langLoginRequest = "Demande de login";
$langExplainFormLostPass = "Entrez ce que  vous pensez avoir  introduit comme données lors de votre inscription.";
$langTotalEntryFound = " Nombre d'entrée trouvées";
$langEmailNotSent = "Quelque chose n'as pas fonctionné, veuillez envoyer ceci à";
$langYourAccountParam = "Voici les paramètres qui vous permettront de vous connecter sur";
$langTryWith ="essayez avec ";
$langInPlaceOf ="au lieu de";
$langParamSentTo = "Vos paramètres de connexion sont envoyés sur l'adresse";



// REGISTRATION - AUTH - inscription.php
$langRegistration="Inscription";
$langName=$langFirstname;
$langSurname=$langLastname;
$langUsername="Pseudo";
$langPass="Mot de passe";
$langConfirmation="Confirmation";
$langStatus="Action";
$langRegStudent="M'inscrire à des espaces";
$langRegAdmin="Créer des espaces";
$langTitular = "Responsable";
// inscription_second.php


$langRegistration = "Inscription";
$langPassTwice    = "Vous n'avez pas tapé deux fois le même mot de passe.
Utilisez le bouton de retour en arrière de votre navigateur
et recommencez.";

$langEmptyFields = "Vous n'avez pas rempli tous les champs.
Utilisez le bouton de retour en arrière de votre navigateur et recommencez.";

$langPassTooEasy ="Ce mot de passe est trop simple. Choisissez un autre password  comme par exemple : ";

$langUserFree    = "Le nom d'utilisateur que vous avez choisi est déjà pris.
Utilisez le bouton de retour en arrière de votre navigateur
et choisissez-en un autre.";

$langYourReg                = "Votre inscription sur";
$langDear                   = "Cher(ère)";
$langYouAreReg              = "Vous êtes inscrit(e) sur";
$langSettings               = "avec les paramètre suivants:\nPseudo:";
$langAddress                = "L'adresse de";
$langIs                     = "est";
$langProblem                = "En cas de problème, n'hésitez pas à prendre contact avec nous";
$langFormula                = "Cordialement";
$langManager                = "Responsable";
$langPersonalSettings       = "Vos coordonnées personnelles ont été enregistrées et un email vous a été envoyé
pour vous rappeler votre pseudo et votre mot de passe.</p>";
$langNowGoChooseYourCourses ="Vous  pouvez maintenant aller sélectionner les espaces auxquels vous souhaitez avoir accès.";
$langNowGoCreateYourCourse  = "Vous  pouvez maintenant aller créer votre espace";
$langYourRegTo              = "Vos modifications";
$langIsReg                  = "Vos modifications ont été enregistrées";
$langCanEnter               = "Vous pouvez maintenant <a href=../../index.php>entrer dans le portail</a>";

// profile.php

$langModifProfile = "Modifier mon profil";
$langViewProfile  = "Voir mon profil (non modifiable)";
$langPassTwo      = "Vous n'avez pas tapé deux fois le même mot de passe";
$langAgain        = "Recommencez!";
$langFields       = "Vous n'avez pas rempli tous les champs";
$langUserTaken    = "Le pseudo que vous avez choisi est déjà pris";
$langEmailWrong   = "L'adresse email que vous avez introduite n'est pas complète
ou contient certains caractères non valides";
$langProfileReg   = "Votre nouveau profil a été enregistré";
$langHome         = "Retourner à l'accueil";
$langMyStats      = "Voir mes statistiques";


// user.php

$langUsers    = "Membres";
$langModRight ="Modifier les droits de : ";
$langNone     ="non";
$langAll      ="oui";

$langNoAdmin            = "n'a désormais <b>aucun droit de responsable dans cet espace</b>";
$langAllAdmin           = "a désormais <b>tous les droits de responsable dans cet espace</b>";
$langModRole            = "Modifier le rôle de";
$langRole               = "Descriptif";
$langIsNow              = "est désormais";
$langInC                = "dans cet espace";
$langFilled             = "Vous n'avez pas rempli tous les champs.";
$langUserNo             = "Le pseudo que vous avez choisi";
$langTaken              = "est déjà pris. Choisissez-en un autre.";
$langOneResp            = "L'un des responsables de cet espace";
$langRegYou             = "vous a inscrit sur";
$langTheU               ="Le membre";
$langAddedU             ="a été ajouté. Si vous avez introduit son adresse, un message lui a été envoyé pour lui communiquer son pseudo";
$langAndP               = "et son mot de passe";
$langDereg              = "a été désinscrit de cet espace";
$langAddAU              = "Ajouter des membres";
$langStudent            = "membre";
$langBegin              = "début";
$langPreced50           = "50 précédents";
$langFollow50           = "50 suivants";
$langEnd                = "fin";
$langAdmR               = "Admin";
$langUnreg              = "Désinscrire";
$langAddHereSomeCourses = "<font size=2 face='arial, helvetica'><big>Mes cours</big><br><br>
			Cochez les espaces auxquels vous souhaitez participer et décochez ceux auxquels vous 
			ne voulez plus participer (les espaces dont vous êtes responsable 
			ne peuvent être décochés). Cliquez ensuite sur Ok en bas de la liste.";

$langCanNotUnsubscribeYourSelf = "Vous ne pouvez pas vous désinscrire
				vous-même d'un espace dont vous êtes responsable. 
				Seul un autre responsable peut le faire.";

$langGroup="Groupe";
$langUserNoneMasc="-";

$langTutor                = "Modérateur";
$langTutorDefinition      = "Modérateur (droit de superviser des groupes)";
$langAdminDefinition      = "Responsable (droit de modifier le contenu de l'espace)";
$langDeleteUserDefinition ="Désinscrire (supprimer de la liste des membres de <b>ce</b> cours)";
$langNoTutor              = "n'est pas modérateur pour cet espace";
$langYesTutor             = "est modérateur pour cet espace";
$langUserRights           = "Droits des membres";
$langNow                  = "actuellement";
$langOneByOne             = "Ajouter manuellement un utilisateur";
$langUserMany             = "Importer une liste d'utilisateurs via un fichier texte";
$langNo                   = "non";
$langYes                  = "oui";

$langUserAddExplanation   = "Chaque ligne du fichier à envoyer 
		contiendra nécessairement et uniquement les 
		5 champs <b>Nom&nbsp;&nbsp;&nbsp;Prénom&nbsp;&nbsp;&nbsp;
		Pseudo&nbsp;&nbsp;&nbsp;Mot de passe&nbsp;
		&nbsp;&nbsp;Courriel</b> séparés par des tabulations
		et présentés dans cet ordre. Les utilisateurs recevront 
		par courriel pseudo et mot de passe.";

$langSend             = "Envoyer";
$langDownloadUserList = "Envoyer la liste";
$langUserNumber       = "nombre";
$langGiveAdmin        = "Rendre responsable";
$langRemoveRight      = "Retirer ce droit";
$langGiveTutor        = "Rendre tuteur";

$langUserOneByOneExplanation = "Il recevra par courriel nom d'utilisateur et mot de passe";
$langBackUser                = "Retour à la liste des utilisateurs";
$langUserAlreadyRegistered   = "Un utilisateur ayant mêmes nom et prénom est déjà inscrit dans l'espace.";

$langAddedToCourse           = "a été inscrit à votre espace";

$langGroupUserManagement     = "Gestion des groupes";

$langIfYouWantToAddManyUsers = "Si vous voulez ajouter une liste des membres de votre espace, contactez votre web administrateur.";

$langCourses    = "espace.";
$langLastVisits = "Mes dernières visites";
$langSee        = "Voir";
$langSubscribe  = "M'inscrire<br>coché&nbsp;=&nbsp;oui";
$langCourseName = "Nom de l'espace";
$langLanguage   = "Langue";

$langConfirmUnsubscribe = "Confirmez la désincription de ce membre";
$langAdded              = "Ajoutés";
$langDeleted            = "Supprimés";
$langPreserved          = "Conservés";
$langDate               = "Date";
$langAction             = "Action";
$langLogin              = "Log In";
//$langLogout             = "Quitter";
$langModify             = "Modifier";
$langUserName           = "Nom membre";
$langEdit               = "Editer";

$langCourseManager       = "Responsable";
$langManage              = "Gestion du portail";
$langAdministrationTools = "Outils d'administration";
$langUserProfileReg	     = "La modification a été effectuée";
$lang_lost_password      = "Mot de passe perdu";

$lang_enter_email_and_well_send_you_password  = "Entrez l'adresse de courrier électronique que vous avez utilisée pour vous enregistrer et nous vous enverrons votre mot de passe.";
$lang_your_password_has_been_emailed_to_you   = "Votre mot de passe vous a été envoyé par courrier électronique.";
$lang_no_user_account_with_this_email_address = "Il n'y a pas de compte utilisateur avec cette adresse de courrier électronique.";
$langCourses4User  = "Espaces pour cet utilisateur";
$langCoursesByUser = "Vue d'ensemble des cours par membre";

$langAddImage = "Ajoutez une photo";
$langUpdateImage = "Changer de photo";
$langDelImage = "Supprimer la photo";
$langOfficialCode = "Code officiel (ID)";

$langAuthInfo = "Paramètres de connexion";
$langEnter2passToChange = "Introduisez 2x votre mot de passe pour le modifier. Laissez les champs vides dans le cas contraire.";
?>
