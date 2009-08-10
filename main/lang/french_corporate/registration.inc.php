<?php // $Id: registration.inc.php 3 2004-01-23 14:01:45Z olivierb78 $

/*
      +----------------------------------------------------------------------+
      | CLAROLINE version 1.4.0 $Revision: 3 $
      +----------------------------------------------------------------------+
      | Copyright (c) 2001, 2002 Universite catholique de Louvain (UCL)      |
      +----------------------------------------------------------------------+
      | Authors: Thomas Depraetere <depraetere@ipm.ucl.ac.be>                |
      |          Hugues Peeters    <peeters@ipm.ucl.ac.be>                   |
      |          Christophe Gesché <gesche@ipm.ucl.ac.be>                    |
      +----------------------------------------------------------------------+
 */
// user management

// lang vars
$langAdminOfCourse		= "admin";  //
$langSimpleUserOfCourse = "normal"; // strings for synopsis
$langIsTutor  			= "tuteur"; //

$langCourseCode			= "Cours";	// strings for list Mode
$langParamInTheCourse 	= "Statut"; //

$langAddNewUser = "Ajouter un utilisateur au système";
$langMember ="inscrit";

$langDelete	="supprimer";
$langLock	= "bloquer";
$langUnlock	= "liberer";
// $langOk

$langHaveNoCourse = "Pas de Cours";

$langFirstname = "Prenom";
$langLastname = "Nom";
$langEmail = "Adresse de courrier électronique";
$langRetrieve ="Retrouver  mes paramètres d'identification";
$langMailSentToAdmin = "Un email à été adressé à l'administrateur.";
$langAccountNotExist = "Ce compte semble ne pas exister.<BR>".$langMailSentToAdmin." Il fera une recherche manuelle.<BR><BR>";
$langAccountExist = "Ce compte semble exister.<BR> Un email à été adressé à l'administrateur. <BR><BR>";
$langWaitAMailOn = "Attendez vous à une réponse sur ";
$langCaseSensitiveCaution = "Le système fait la différence entre les minuscules et les majuscules.";
$langDataFromUser = "Données envoyées par l'utilisateur";
$langDataFromDb = "Données correspondantes dans la base de donnée";
$langLoginRequest = "Demande de login";
$langExplainFormLostPass = "Entrez ce que  vous pensez avoir  introduit comme données lors de votre inscription.";
$langTotalEntryFound = " Nombre d'entrée trouvées";
$langEmailNotSent = "Quelque chose n'as pas fonctionné, veuillez envoyer ceci à";
$langYourAccountParam = "Voici vos paramètres de connection";
$langTryWith ="essayez avec ";
$langInPlaceOf ="au lieu de";
$langParamSentTo = "Vos paramètres de connection sont envoyés sur l'adresse";



// REGISTRATION - AUTH - inscription.php
$langRegistration="Inscription";
$langName="Nom";
$langSurname="Prénom";
$langUsername="Nom d'utilisateur";
$langPass="Mot de passe";
$langConfirmation="confirmation";
$langStatus="Action";
$langRegStudent="M'inscrire à des cours";
$langRegAdmin="Créer des sites de cours";
$langTitular = "Titulaire";
// inscription_second.php


$langRegistration="Inscription";
$langPassTwice="Vous n'avez pas tapé deux fois le même mot de passe.
Utilisez le bouton de retour en arrière de votre navigateur
et recommencez.";

$langEmptyFields="Vous n'avez pas rempli tous les champs.
Utilisez le bouton de retour en arrière de votre navigateur et recommencez.";

$langPassTooEasy ="Ce mot de passe est trop simple. Choisissez un autre password  comme par exemple : ";

$langUserFree="Le nom d'utilisateur que vous avez choisi est déjà pris.
Utilisez le bouton de retour en arrière de votre navigateur
et choisissez-en un autre.";

$langYourReg="Votre inscription sur";
$langDear="Cher(ère)";
$langYouAreReg="Vous êtes inscrit(e) sur";
$langSettings="avec les paramètre suivants:\nNom d'utilisateur:";
$langAddress="L'adresse de";
$langIs="est";
$langProblem = "En cas de problème, n'hésitez pas à prendre contact avec nous";
$langFormula="Cordialement";
$langManager="Responsable";
$langPersonalSettings="Vos coordonnées personnelles ont été enregistrées et un email vous a été envoyé
pour vous rappeler votre nom d'utilisateur et votre mot de passe.</p>";
$langNowGoChooseYourCourses ="Vous  pouvez maintenant aller sélectionner les cours auxquels vous souhaitez avoir accès.";
$langNowGoCreateYourCourse  ="Vous  pouvez maintenant aller créer votre cours";
$langYourRegTo="Vos modifications";
$langIsReg="Vos modifications ont été enregistrées";
$langCanEnter="Vous pouvez maintenant <a href=../../index.php>entrer dans le campus</a>";

// profile.php

$langModifProfile="Modifier mon profil";
$langPassTwo="Vous n'avez pas tapé deux fois le même mot de passe";
$langAgain="Recommencez!";
$langFields="Vous n'avez pas rempli tous les champs";
$langUserTaken="Le nom d'utilisateur que vous avez choisi est déjà pris";
$langEmailWrong="L'adresse email que vous avez introduite n'est pas complète
ou contient certains caractères non valides";
$langProfileReg="Votre nouveau profil a été enregistré";
$langHome="Retourner à l'accueil";
$langMyStats = "Voir mes statistiques";


// user.php

$langUsers="Utilisateurs";
$langModRight="Modifier les droits de : ";
$langNone="non";
$langAll="oui";
$langNoAdmin="n'a désormais <b>aucun droit d'administration sur ce site</b>";
$langAllAdmin="a désormais <b>tous les droits d'administration sur ce site</b>";
$langModRole="Modifier le rôle de";
$langRole="Rôle (facultatif)";
$langIsNow="est désormais";
$langInC="dans ce cours";
$langFilled="Vous n'avez pas rempli tous les champs.";
$langUserNo="Le nom d'utilisateur que vous avez choisi";
$langTaken="est déjà pris. Choisissez-en un autre.";
$langOneResp="L'un des responsables du cours";
$langRegYou="vous a inscrit sur";
$langTheU="L'utilisateur";
$langAddedU="a été ajouté. Si vous avez introduit son adresse, un 
			message lui a été envoyé pour lui communiquer son nom d'utilisateur";
$langAndP="et son mot de passe";
$langDereg="a été désinscrit de ce cours";
$langAddAU="Ajouter des utilisateurs";
$langStudent="participant";
$langBegin="début";
$langPreced50="50 précédents";
$langFollow50="50 suivants";
$langEnd="fin";
$langAdmR="Admin";
$langUnreg="Désinscrire";
$langAddHereSomeCourses = "<font size=2 face='arial, helvetica'><big>Mes cours</big><br><br>
			Cochez les cours que vous souhaitez suivre et décochez ceux que vous 
			ne voulez plus suivre (les cours dont vous êtes responsable 
			ne peuvent être décochés). Cliquez ensuite sur Ok en bas de la liste.";

$langCanNotUnsubscribeYourSelf = "Vous ne pouvez pas vous désinscrire
				vous-même d'un cours dont vous êtes administrateur. 
				Seul un autre administrateur du cours peut le faire.";

$langGroup="Groupe";
$langUserNoneMasc="-";

$langTutor="Tuteur";
$langTutorDefinition="Tuteur (droit de superviser des groupes)";
$langAdminDefinition="Administrateur (droit de modifier le contenu du site)";
$langDeleteUserDefinition="Désinscrire (supprimer de la liste des utilisateurs de <b>ce</b> cours)";
$langNoTutor = "n'est pas tuteur pour ce cours";
$langYesTutor = "est tuteur dans ce cours";
$langUserRights="Droits des utilisateurs";
$langNow="actuellement";
$langOneByOne="Ajouter manuellement un utilisateur";
$langUserMany="Importer une liste d'utilisateurs via un fichier texte";
$langNo="non";
$langYes="oui";
$langUserAddExplanation="Chaque ligne du fichier à envoyer 
		contiendra nécessairement et uniquement les 
		5 champs <b>Nom&nbsp;&nbsp;&nbsp;Prénom&nbsp;&nbsp;&nbsp;
		Nom d'utilisateur&nbsp;&nbsp;&nbsp;Mot de passe&nbsp;
		&nbsp;&nbsp;Courriel</b> séparés par des tabulations 
		et présentés dans cet ordre. Les utilisateurs recevront 
		par courriel nom d'utilisateur et mot de passe.";
$langSend="Envoyer";
$langDownloadUserList="Envoyer la liste";
$langUserNumber="nombre";
$langGiveAdmin="Rendre admin";
$langRemoveRight="Retirer ce droit";
$langGiveTutor="Rendre tuteur";
$langUserOneByOneExplanation="Il recevra par courriel nom d'utilisateur et mot de passe";
$langBackUser="Retour à la liste des utilisateurs";
$langUserAlreadyRegistered="Un utilisateur ayant mêmes nom et prénom est déjà inscrit dans le cours.";

$langAddedToCourse="a été inscrit à votre site";

$langGroupUserManagement="Gestion des groupes";

$langIfYouWantToAddManyUsers="Si vous voulez ajouter une liste d'utilisateurs à votre site, 
		contactez votre web administrateur.";

$langCourses="cours.";
$langLastVisits="Mes dernières visites";

$langSee		= "Voir";
$langSubscribe	= "M'inscrire<br>coché&nbsp;=&nbsp;oui";
$langCourseName	= "Nom&nbsp;du&nbsp;cours";
$langLanguage	= "Langue";

$langConfirmUnsubscribe = "Confirmez la désincription de cet utilisateur";
$langAdded = "Ajoutés";
$langDeleted = "Supprimés";
$langPreserved = "Conservés";
$langDate = "Date";
$langAction = "Action";
$langCourseManager = "Gestionnaire du site";
$langManage				= "Gestion du portail";
$langAdministrationTools = "Outils d'administration";

?>