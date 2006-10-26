<?php
/**
 * Dropbox module for Dokeos
 * language file in French.
 * To make a version in your own language, you have 2 options:
 * 		- if you want to make use of the multilanguage tool in Claroline (this way you
 * 		can make 2 seperate courses in 2 different languages and Claroline will take
 * 		care of the translations) this file must be placed in the .../claroline/lang/English/
 * 		directory and the copy of this file that contains the translations must be placed in
 * 		the .../claroline/lang/YourLang/ directory. Be sure to give the translated version the same
 * 		name as this one.
 * 		- if you're sure you will only need the dropbox module in 1 language, you can just leave this
 * 		file in the current directory (.../claroline/plugin/dropbox/) and translate each variable into
 * 		the correct language.
 *
 * @version 1.20
 * @copyright 2004
 * @author Jan Bols <jan@ivpv.UGent.be>
 * with contributions by René Haentjens <rene.haentjens@UGent.be> (see RH)
 */
/**
 * +----------------------------------------------------------------------+
 * |   This program is free software; you can redistribute it and/or      |
 * |   modify it under the terms of the GNU General Public License        |
 * |   as published by the Free Software Foundation; either version 2     |
 * |   of the License, or (at your option) any later version.             |
 * |                                                                      |
 * |   This program is distributed in the hope that it will be useful,    |
 * |   but WITHOUT ANY WARRANTY; without even the implied warranty of     |
 * |   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the      |
 * |   GNU General Public License for more details.                       |
 * |                                                                      |
 * |   You should have received a copy of the GNU General Public License  |
 * |   along with this program; if not, write to the Free Software        |
 * |   Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA          |
 * |   02111-1307, USA. The GNU GPL license is also available through     |
 * |   the world-wide-web at http://www.gnu.org/copyleft/gpl.html         |
 * +----------------------------------------------------------------------+
 * |   Authors: Jan Bols          <jan@ivpv.UGent.be>              	      |
 * +----------------------------------------------------------------------+
 */

/*
* General variables
*/
$dropbox_lang["dropbox"] = "Partage de fichiers";
$dropbox_lang["help"] = "Aide";

//$dropbox_cnf["version"] = "1.2";	//This variable is used to find out if this language file is outdated or not
									//When outdated, it will not be used.
									//The number must be the same as the version number in the dorpbox_init1.inc.php file

/**
 * error variables
 */
$dropbox_lang["aliensNotAllowed"] = "Seuls les membres de cet espace peuvent utiliser l'outil de partage. Vous n'êtes pas membre de cet espace.";
$dropbox_lang["queryError"] = "Erreur de base de données. Veuillez contacter l'administrateur.";
$dropbox_lang["generalError"] = "Une erreur s'est produite. Veuillez contacter l'administrateur.";
$dropbox_lang["badFormData"] = "La soumission a échoué : données du formulaire erronées. Veuillez contacter l'administrateur.";
$dropbox_lang["noUserSelected"] = "Veuillez sélectionner le destinataire de ce fichier.";
$dropbox_lang["noFileSpecified"] = "Vous n'avez sélectionné aucun fichier à transférer.";
$dropbox_lang["tooBig"] = "Le fichier sélectionné est trop gros.";
$dropbox_lang["uploadError"] = "Erreur lors du transfert. Veuillez contacter l'administrateur.";
$dropbox_lang["errorCreatingDir"] = "Impossible de créer le répertoire. Veuillez contacter l'administrateur.";
$dropbox_lang["installError"] = "Impossible d'installer les tables nécessaires au module. Veuillez contacter l'administrateur.";

/**
 * upload file variables
 */
$dropbox_lang["uploadFile"] = "Envoyer un document";
// $dropbox_lang["titleWork"] = "Paper Title";	//this var isn't used anymore
$dropbox_lang["authors"] = "Auteurs";
$dropbox_lang["description"] = "Description";
$dropbox_lang["sendTo"] = "Envoyer à";

/**
 * Sent/Received list variables
 */
$dropbox_lang["receivedTitle"] = "RECU";
$dropbox_lang["sentTitle"] = "ENVOYE";
$dropbox_lang["confirmDelete"] = "Ceci supprimera le fichier de votre liste, et non de celle des autres membres.";
$dropbox_lang["all"] = "tous les fichiers";
$dropbox_lang["workDelete"] = "Supprimer";
$dropbox_lang["sentBy"] = "Envoyé par";
$dropbox_lang["sentTo"] = "Envoyé à";
$dropbox_lang["sentOn"] = "le";
$dropbox_lang["anonymous"] = "anonyme";
$dropbox_lang["ok"] = "Valider";
$dropbox_lang["lastUpdated"] = "Dernière mise à jour";
$dropbox_lang["lastResent"] = "Dernier ré-envoi";
$dropbox_lang['tableEmpty'] = "La liste est vide.";
$dropbox_lang["overwriteFile"] = "Ecraser la version précédente du même fichier ?";
$dropbox_lang['orderBy'] = "Classer par : ";
$dropbox_lang['lastDate'] = "date du dernier envoi";
$dropbox_lang['firstDate'] = "date du premier envoi";
$dropbox_lang['title'] = "titre";
$dropbox_lang['size'] = "taille";
$dropbox_lang['author'] = "auteur";
$dropbox_lang['sender'] = "expéditeur";
$dropbox_lang['recipient'] = "destinataire";

/**
 * Feedback variables
 */
$dropbox_lang["docAdd"] = "Le fichier a été ajouté";
$dropbox_lang["fileDeleted"] = "Le fichier sélectionné a été supprimé de votre espace d'échange.";
$dropbox_lang["backList"] = "Retourner à votre espace d'échange";

/**
 * RH: Mailing variables
 */
$dropbox_lang["mailingAsUsername"] = "Envoi par mail ";
$dropbox_lang["mailingInSelect"] = "--- Mailing ---";
$dropbox_lang["mailingSelectNoOther"] = "La fonction Mailing ne peut être combinée avec l'envoi à d'autres destinataires. Celle-ci envoie le fichier à tout le monde.";
$dropbox_lang["mailingNonMailingError"] = "Mailing ne peut être écrasé par des envois non-mailing et vice-versa";
$dropbox_lang["mailingExamine"] = "Examiner le fichier ZIP";
$dropbox_lang["mailingNotYetSent"] = "Les fichiers contenus dans le mailing n'ont pas été envoyés ...";
$dropbox_lang["mailingSend"] = "Envoyer le contenu";
$dropbox_lang["mailingConfirmSend"] = "Envoyer le contenu vers des personnes individuellement ?";
$dropbox_lang["mailingBackToDropbox"] = "(retour à la page d'accueil de Partage de fichiers)";
$dropbox_lang["mailingWrongZipfile"] = "Le fichier du Mailing doit être un ZIP avec MEMBERID ou LOGINNAME";
$dropbox_lang["mailingZipEmptyOrCorrupt"] = "Le fichier ZIP du Mailing est vide ou n'est pas un fichier ZIP valide";
$dropbox_lang["mailingZipPhp"] = "Le fichier de Mailing ne peut contenir de fichiers PHP. Il ne sera pas envoyé";
$dropbox_lang["mailingZipDups"] = "Le fichier ZIP ne peut pas contenir de fichiers dupliqués. Il ne sera pas envoyé";
$dropbox_lang["mailingFileFunny"] = "aucun nom, ou l'extension ne contient pas 1 à 4 caractères";
$dropbox_lang["mailingFileNoPrefix"] = "le nom ne commence pas par ";
$dropbox_lang["mailingFileNoPostfix"] = "le nom ne se termine pas par ";
$dropbox_lang["mailingFileNoRecip"] = "le nom ne contient pas l'ID d'un destinataire";
$dropbox_lang["mailingFileRecipNotFound"] = "aucun membre avec ";
$dropbox_lang["mailingFileRecipDup"] = "plusieurs membres ont ";
$dropbox_lang["mailingFileIsFor"] = "est pour ";
$dropbox_lang["mailingFileSentTo"] = "envoyé à ";
$dropbox_lang["mailingFileNotRegistered"] = " (pas membre de cet espace)";
$dropbox_lang["mailingNothingFor"] = "Rien pour";

/**
 * RH: Just Upload
 */
$dropbox_lang["justUploadInSelect"] = "--- Transféré ---";
$dropbox_lang["justUploadInList"] = "Envoi par";
$dropbox_lang["mailingJustUploadNoOther"] = "Ce transfert ne peut être combiné avec d'autres destinataires";
?>
