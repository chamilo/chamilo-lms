<?php
/*
      +----------------------------------------------------------------------+
      | CLAROLINE version 1.3.0 $Revision: 950 $                            |
      +----------------------------------------------------------------------+
      | Copyright (c) 2001, 2002 Universite catholique de Louvain (UCL)      |
      +----------------------------------------------------------------------+
      |   $Id: scormdocument.inc.php 950 2004-04-01 20:24:14Z olivierb78 $         |
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
      +----------------------------------------------------------------------+
 */

/***************************************************************
*                   Language translation
****************************************************************
GOAL
****
Translate the interface in chosen language

*****************************************************************/

// DOCUMENT

$langDoc="Parcours - Scorm contenus";
$langDownloadFile= "Transférer le fichier sur le serveur";
$langDownload="Transférer";
$langCreateDir="Cr&eacute;er un r&eacute;pertoire";
$langCreateDoc="Créer un document";
$langName="Nom";
$langNameDir="Nom du nouveau r&eacute;pertoire";
$langSize="Taille";
$langDate="Date";
$langRename="Renommer";
$langComment="Commentaire";
$langVisible="Visible/invisible";
$langCopy="Copier";
$langTo="vers";
$langNoSpace="Le t&eacute;l&eacute;chargement a &eacute;chou&eacute;. Il n'y a plus assez de place dans votre r&eacute;pertoire";
$langDownloadEnd="Le t&eacute;l&eacute;chargement est termin&eacute;";
$langFileExists="Impossible d'effectuer cette op&eacute;ration.
<br>Un fichier portant ce nom existe déj&agrave;.";
$langIn="en";
$langNewDir="nom du nouveau répertoire";
$langImpossible="Impossible d'effectuer cette opération";
$langAddComment="ajouter/modifier un commentaire à";
$langUp="Remonter";
$langDown="Descendre";
$langDocCopied="document copi&eacute;";
$langDocDeleted="élément supprim&eacute;";
$langElRen="&eacute;l&eacute;ment renom&eacute;";
$langDirCr="r&eacute;pertoire cr&eacute;&eacute;";
$langDirMv="&eacute;l&eacute;ment deplac&eacute;";
$langComMod="commentaire modifi&eacute;";
$langElRen="El&eacute;ment renomm&eacute;";
$langViMod="Visibilit&eacute; modifi&eacute;e";
$langFileError="Le fichier à télécharger n'est pas valide.";
$langMaxFileSize="La taille maximum est de";
$langFileName="Nom du fichier";
$langNoFileName="Veuillez introduire le nom du fichier";
$langNoText="Veuillez introduire votre texte / contenu HTML";

$langCreateDocument="Créer un document";

// Special for group documents
$langGroupManagement="Gestion des groupes";
$langGroupSpace="Espace de groupe";

$langGroupSpaceLink="Espace du groupe";
$langGroupForumLink="Forum du groupe";

$langZipNoPhp="Le fichier ZIP ne peut pas contenir de fichiers en .php";

$langUncompress="Décompresser un fichier ZIP";

$langScormcontent="Ceci est un contenu Scorm<br><input type=button name=scormbutton value='Exécuter' onclick='openscorm()'>";
$langScormcontentstudent="Ceci est un format de cours Scorm. Si vous souhaitez le lancer, cliquez ici : <input type=button name=scormbutton value='Exécuter' onclick='openscorm()'>";

$langDownloadAndZipEnd=" Le fichier .zip a été envoyé et décompressé";

$langAreYouSureToDelete = "Êtes vous sûr de vouloir supprimer";

$langPublish = "Publier";

$langMissingImagesDetected = "Images manquantes détectées";

/* ------------------------------
	Language strings for Miniweb
   ------------------------------ */
$langDocuments = "Documents";
$langMiniweb = "Table des matières";
$langMakeMiniweb = "Créer une organisation";
$langOrganiseDocuments = "Créer une table des matières";
$langEditTOC = "Modifier la table des matières";
$langReadMiniweb = "Voir la table des matières";
$langChangeMiniweb = "Modifier cette table des matières";
$langChapter = "Chapitre";

$langDocumentList = "Liste des documents"; //title of the list box that shows the documents
$langOrganisationList = "Table des matières"; //title of the list box that shows the organisation

$langHelpMiniweb = "Ce module vous permet d'organiser vos documents. Vous pouvez disposer vos documents en chapitres et choisir l'ordre dans lequel ils apparaitront.
		Lorsque vous êtes prêt, cliquez sur le bouton \"Générer la table des matières\".
		Les documents que vous voyez sont stockés dans <a href=\"document.php\">l'outil de documents</a>.";

$langCreationSucces = "La table des matières a été créée avec succès.";
$langCanViewOrganisation = "Vous pouvez voir votre organisation";
$langHere = "Ici";
$langViewDocument = "Voir";
$langEditDocument="Editer";
$langHtmlTitle = "Table des matières";

$langAddToTOC = "Ajouter au contenu";
$langAddChapter = "Ajouter un chapitre";
$langReady = "Générer la table des matières";
$langStoreDocuments = "Stocker les documents";
?>
