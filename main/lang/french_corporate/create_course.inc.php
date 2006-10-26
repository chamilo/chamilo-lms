<?php
/*
      +----------------------------------------------------------------------+
      | CLAROLINE version 1.3.0 $Revision: 4413 $                             |
      +----------------------------------------------------------------------+
      | Copyright (c) 2001, 2002 Universite catholique de Louvain (UCL)      |
      +----------------------------------------------------------------------+
      |   $Id: create_course.inc.php 4413 2005-04-25 08:40:08Z olivierb78 $   |
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
// create_course.php
$langCreateSite="Créer un site pour les cadres";
$langFieldsRequ="Tous les champs sont obligatoires";
$langTitle="Intitulé";
$langEx="p. ex. <i>Théorie pédante sur l'orthopédagogie</i>";
$langFac="Catégorie";
$langCode="Code du site";
$langTargetFac=" ";
$langMax = "max. 20 caractères, p. ex. <i>INNOV001</i>";
$langDoubt="En cas de doute sur l\'intitulé exact ou le code de votre site,consultez le";
$langProgram="Staff fédéral</a>. Si le site que vous voulez créer ne correspond pas à un code existant, vous pouvez en inventer un. Par exemple <i>INNOVATION</i> s\'il s\'agit d\'un programme de formation à l\'innovation";
$langProfessors="Brève description";
$langExplanation="Une fois que vous aurez cliqué sur OK, un site contenant Forum, Liste de liens, Quizz, Agenda, Liste de documents... sera créé. Grâce à votre identifiant, vous pourrez en modifier le contenu";
$langEmpty="Vous n\'avez pas rempli tous les champs.\n<br>\nUtilisez le bouton de retour en arrière de votre navigateur et recommencez.<br>Si vous ne connaissez pas le code de votre site, consultez le staff fédéral";
$langCodeTaken="Ce code est déjà pris.<br>Utilisez le bouton de retour en arrière de votre navigateur et recommencez";


// tables MySQL
$langFormula="Cordialement, le Modérateur";
$langForumLanguage="french";	// other possibilities are english, spanish (this uses phpbb language functions)
$langTestForum="Forum d\'essais";
$langDelAdmin="A supprimer via l\'administration des forums";
$langMessage="Lorsque vous supprimerez le forum \"Forum d\'essai\", cela supprimera également le présent sujet qui ne contient que ce seul message";
$langExMessage="Message exemple";
$langAnonymous="Anonyme";
$langExerciceEx="Exemple d\'exercice";
$langAntique="Histoire de la philosophie antique";
$langSocraticIrony="L\'ironie socratique consiste à...";
$langManyAnswers="(plusieurs bonnes réponses possibles)";
$langRidiculise="Ridiculiser son interlocuteur pour lui faire admettre son erreur.";
$langNoPsychology="Non. L\'ironie socratique ne se joue pas sur le terrain de la psychologie, mais sur celui de l\'argumentation.";
$langAdmitError="Reconnaître ses erreurs pour inviter son interlocuteur à faire de même.";
$langNoSeduction="Non. Il ne s\'agit pas d\'une stratégie de séduction ou d\'une méthode par l\'exemple.";
$langForce="Contraindre son interlocuteur, par une série de questions et de sous-questions, à reconnaître qu\'il ne connaît pas ce qu\'il prétend connaître.";
$langIndeed="En effet. L\'ironie socratique est une méthode interrogative. Le grec \"eirotao\" signifie d\'ailleurs \"interroger\".";
$langContradiction="Utiliser le principe de non-contradiction pour amener son interlocuteur dans l\'impasse.";
$langNotFalse="Cette réponse n\'est pas fausse. Il est exact que la mise en évidence de l\'ignorance de l\'interlocuteur se fait en mettant en évidence les contradictions auxquelles abouttisent ses thèses.";



// Home Page MySQL Table "accueil"
$langAgenda="Agenda";
$langLinks="Liens";
$langDoc="Documents";
$langVideo="Video";
$langWorks="Contributions des uns et des autres";
$langCourseProgram="Cahier des charges";
$langAnnouncements="Annonces";
$langUsers="Utilisateurs";
$langForums="Forums";
$langExercices="Quizz";
$langStatistics="Statistiques";
$langAddPageHome="Déposer page et lier à page d\'accueil";
$langLinkSite="Ajouter un lien sur la page d\'accueil";
$langModifyInfo="Propriétés du site";
$langCourseDesc = "Description du site";


// Other SQL tables
$langAgendaTitle="Mardi 11 décembre 14h00 : cours de philosophie (1) - Local : Sud 18";
$langAgendaText="Introduction générale à la philosophie et explication sur le fonctionnement du cours";
$langMicro="Micro-trottoir";
$langVideoText="Ceci est un exemple en RealVideo. Vous pouvez envoyer des vidéos de tous formats (.mov, .rm, .mpeg...), pourvu que vos étudiants soient en mesure de les lire";
$langGoogle="Moteur de recherche généraliste performant";
$langIntroductionText="Ceci est le texte d\'introduction de votre site. Modifier ce texte régulièrement est une bonne façon d\'indiquer clairement que ce site est un lieu d\'interaction vivant et non un simple répertoire de documents.";

$langIntroductionTwo="Cette page est un espace de publication. Elle permet à chaque cadre ou groupe de cadres d\'envoyer un document (Word, Excel, HTML... ) vers le site afin de le rendre accessible aux autres cadres.
Si vous passez par votre espace de groupe pour publier le document (option publier), l\'outil de gestion des contributions fera un simple lien vers le document là où il se trouve dans votre répertoire de groupe sans le déplacer.";

$langCourseDescription="Ecrivez ici la description qui apparaîtra dans la liste des sites (Le contenu de ce champ ne s\'affiche actuellement nulle part et ne se trouve ici qu\'en préparation à une version prochaine de Claroline).";
$langProfessor="Modérateur";
$langAnnouncementEx="Ceci est un exemple d\'annonce.";
$langJustCreated="Vous venez de créer le site";
$langEnter="Retourner à votre liste de sites";
$langMillikan="Expérience de Millikan";

// Groups
$langGroups="Groupes";
$langCreateCourseGroups="Groupes";

$langCatagoryMain = "Général";
$langCatagoryGroup = "Forums des Groupes";
$langChat ="Discuter";
?>