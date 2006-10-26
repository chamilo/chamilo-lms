<?php
/*
      +----------------------------------------------------------------------+
      | CLAROLINE version 1.3.0 $Revision: 3 $                             |
      +----------------------------------------------------------------------+
      | Copyright (c) 2001, 2002 Universite catholique de Louvain (UCL)      |
      +----------------------------------------------------------------------+
      |   $Id: course_info.inc.php 3 2004-01-23 14:01:45Z olivierb78 $     |
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
// infocours.php

$langModifInfo="Propriétés du site";
$langModifDone="Les informations ont été modifiées";
$langHome="Retour à la page d'accueil";
$langCode="Code du site";
$langDelCourse="Supprimer ce site";
$langProfessor="Modérateur";
$langProfessors="Modérateurs";
$langTitle="Intitulé";
$langFaculty="Catégorie";
$langDescription="Description";
$langConfidentiality="Confidentialité";
$langPublic="Accès public (depuis la page d'accueil du site sans identifiant)";
$langPrivOpen="Accès privé, inscription ouverte";
$langPrivate="Accès privé, inscription fermée (site réservé aux personnes figurant dans la liste <a href=../user/user.php>utilisateurs</a>)";
$langForbidden="Vous n'êtes pas enregistré dans ce cours en tant que responsable";
$langLanguage="Langue";
$langConfTip="Par défaut, votre site n'est accessible
qu'à vous qui en êtes le seul utilisateur. Si vous souhaitez un minimum de confidentialité, le plus simple est d'ouvrir
l'inscription pendant une semaine, de demander aux cadres de s'inscrire eux-mêmes
puis de fermer l'inscription et de vérifier dans la liste des utilisateurs les intrus éventuels.";
$langTipLang="Cette langue vaudra pour tous les visiteurs de votre site de cours.";

// Change Home Page
$langAgenda="Agenda";
$langLink="Liens";
$langDocument="Documents";
$langVid="Vidéo";
$langWork="Contributions des uns et des autres";
$langProgramMenu="Cahier des charges";
$langAnnouncement="Annonces";
$langUser="Utilisateurs";
$langForum="Forums";
$langExercise="Quizz";
$langStats="Statistiques";
$langUplPage="Déposer page et lier à l\'accueil";
$langLinkSite="Ajouter un lien sur la page d\'accueil";
$langModifInfo="Propriétés du site";
$langModifGroups="Groupes";
// delete_course.php

$langDelCourse="Supprimer ce site";
$langCourse="Le site ";
$langHasDel="a été supprimé";
$langBackHome="Retour à la page d'accueil de ";
$langByDel="En supprimant ce site, vous supprimerez tous les documents
qu'il contient et désinscrirez tous les cadres qui y sont inscrits. <p>Voulez-vous réellement supprimer le site";
$langY="OUI";
$langN="NON";

$langDepartmentUrl = "URL du département";
$langDepartmentUrlName = "Département";
$langBackupCourse="Archiver ce site";

$langBack="Retour aux propriétés du site";
$langBackH="Page principale du site";

$langSubscription="Inscription";
$langCourseAccess="Accès au site";
?>