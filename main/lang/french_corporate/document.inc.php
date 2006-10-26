<?php
/*
      +----------------------------------------------------------------------+
      | CLAROLINE version 1.3.0 $Revision: 3510 $                            |
      +----------------------------------------------------------------------+
      | Copyright (c) 2001, 2002 Universite catholique de Louvain (UCL)      |
      +----------------------------------------------------------------------+
      |   $Id: document.inc.php 3510 2005-02-24 16:00:07Z olivierb78 $         |
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

$langMetadata="Métadonnées";  // RH: metadata
$langAddMetadata="Visualiser/modifier métadonnées";  // RH: metadata
$langGoMetadata="Go";  // RH: metadata

$fileModified="Le fichier a été modifié";

$langDoc="Documents";
$langDownloadFile= "T&eacute;l&eacute;charger sur le serveur le fichier";
$langDownload="T&eacute;l&eacute;charger";
$langCreateDir="Cr&eacute;er un r&eacute;pertoire";
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
$langUp="remonter";
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

// Special for group documents
$langGroupManagement="Gestion des groupes";
$langGroupSpace="Espace de groupe";

$langGroupSpaceLink="Espace du groupe";
$langGroupForumLink="Forum du groupe";

$langZipNoPhp="Le fichier ZIP ne peut pas contenir de fichiers en .php";

$langUncompress="Décompresser fichier zipé (.zip)";

$langDownloadAndZipEnd=" Le fichier .zip a été envoyé et décompressé";

$langAreYouSureToDelete = "Êtes vous sûr de vouloir supprimer";

$langPublish = "Publier";

$langMissingImagesDetected = "Images manquantes détectées";

$lang_cut_paste_link= "Cut and paste this URL to use this document in the online page editor"; 
?>