<?php // $Id: trad4all.inc.php 4755 2005-05-02 13:38:51Z olivierb78 $
/*
      +----------------------------------------------------------------------+
      | CLAROLINE version 1.4.0 $Revision: 4755 $                            |
      +----------------------------------------------------------------------+
      | Copyright (c) 2001, 2002 Universite catholique de Louvain (UCL)      |
      +----------------------------------------------------------------------+
      |   This program is free software; you can redistribute it and/or      |
      |   modify it under the terms of the GNU General Public License        |
      |   as published by the Free Software Foundation; either version 2     |
      |   of the License, or (at your option) any later version.             |
      +----------------------------------------------------------------------+
      | Authors: Thomas Depraetere <depraetere@ipm.ucl.ac.be>                |
      |          Hugues Peeters    <peeters@ipm.ucl.ac.be>                   |
      |          Christophe Gesché <gesche@ipm.ucl.ac.be>                    |
      |          Olivier Brouckaert <oli.brouckaert@skynet.be>               |
      +----------------------------------------------------------------------+
*/

$englishLangName = "french";
$localLangName = "français";

$iso639_2_code = "fr";
$iso639_1_code = "fre";

$langNameOfLang['arabic']="arabe";
$langNameOfLang['brazilian']="brésilien";
$langNameOfLang['bulgarian']="bulgare";
$langNameOfLang['catalan']="catalan ";
$langNameOfLang['croatian']="croate";
$langNameOfLang['danish']="danois";
$langNameOfLang['dutch']="néerlandais";
$langNameOfLang['english']="anglais";
$langNameOfLang['english_org']="anglais_org";
$langNameOfLang['finnish']="finlandais";
$langNameOfLang['french']="français";
$langNameOfLang['french_corporate']="français_corporation";
$langNameOfLang['french_KM']="français_KM";
$langNameOfLang['french_org']="français_org";
$langNameOfLang['galician']="galicien";
$langNameOfLang['hungarian']="hongrois";
$langNameOfLang['indonesian']="indonésien";
$langNameOfLang['malay']="malais";
$langNameOfLang['slovenian']="slovène";
$langNameOfLang['german']="allemand";
$langNameOfLang['greek']="grec";
$langNameOfLang['italian']="italien";
$langNameOfLang['japanese']="japonnais";
$langNameOfLang['polish']="polonais";
$langNameOfLang['portuguese']="portugais";
$langNameOfLang['russian']="russe";
$langNameOfLang['simpl_chinese']="chinois simplifié";
$langNameOfLang['spanish']="espagnol";
$langNameOfLang['spanish_latin']="espagnol Amér. Sud";
$langNameOfLang['swedish']="suédois";
$langNameOfLang['thai']="thaïlandais";
$langNameOfLang['turkce']="turc";
$langNameOfLang['vietnamese']="vietnamien";

$charset = 'iso-8859-1';
$text_dir = 'ltr';
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ' ';
$number_decimal_separator = ',';
$byteUnits = array('Octets', 'Ko', 'Mo', 'Go');

$langDay_of_weekNames['init'] = array('D', 'L', 'M', 'M', 'J', 'V', 'S');
$langDay_of_weekNames['short'] = array('Di', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam');
$langDay_of_weekNames['long'] = array('Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi');

$langMonthNames['init']  = array('J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D');
$langMonthNames['short'] = array('Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc');
$langMonthNames['long'] = array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%a %d %b %y";
$dateFormatLong  = '%A %d %B %Y';
$dateTimeFormatLong  = '%A %d %B %Y à %H:%M';
$timeNoSecFormat = '%H:%M';
// GENERIC
$langYes="Oui";
$langNo="Non";
$langBack="Retour";
$langNext="Suivant";
$langAllowed="Autorisé";
$langDenied="Refusé";
$langBackHome="Retour à la page principale";
$langPropositions="Propositions d'amélioration de";
$langMaj="Mise à jour";
$langModify="Modifier";
$langSave="Sauvegarder";
$langDelete="Effacer";
$langVisible="Rendre visible";
$langInvisible="Rendre invisible";
$langMove="Déplacer";
$langTitle="Titre";
$langHelp="Aide";
$langOk="Valider";
$langAdd="Ajouter";
$langAddIntro="Ajouter un texte d'introduction";
$langBackList="Retour à la liste";
$langText="Texte";
$langEmpty="Vide";
$langConfirmYourChoice="Veuillez confirmer votre choix";
$langAnd="et";
$langChoice="Votre choix";
$langFinish="Terminer";
$langCancel="Annuler";
$langNotAllowed="Vous n'êtes pas autorisé à accéder à cette section";
$langManager="Responsable";
$langPlatform="Utilise ";
$langOptional="Facultatif";
$langNextPage="Page suivante";
$langPreviousPage="Page précédente";
$langUse="Utiliser";
$langTotal="Total";
$langTake="prendre";
$langOne="Une";
$langSeveral="Plusieurs";
$langNotice="Remarque";
$langDate="Date";
$langAmong="parmi";
$langCourseHomepage="Sommaire de l'espace";
$langNotLogged="Vous n'êtes pas identifié";
$langShow="Afficher";

// banner

$langMyCourses="Mes espaces";
$langModifyProfile="Mon profil";
$langMyStats = "Mon parcours";
$langLogout="Quitter";
$langMyAgenda = "Mon agenda";

//needed for student view
$langCourseManagerview = "Vue responsable";
$langStudentView = "Vue membre";

//needed for resource linker
$lang_add_resource="Ajouter une ressource";
$lang_added_resources="Ressources ajoutées";
$lang_modify_resource="Modifier / Ajouter une ressource";
$lang_resource="Ressource";
$lang_resources="Ressources";
$lang_attachment="Joindre un fichier";

$langOnLine = "En ligne";
$langUsers = "utilisateurs";
$langUser = "utilisateur";

$langcourse_description = "Description du cours";
$langcalendar_event = "Agenda";
$langdocument = "Documents";
$langlearnpath = "Parcours";
$langlink = "Liens";
$langannouncement = "Annonces";
$langbb_forum = "Forums";
$langdropbox = "Dropbox";
$langquiz = "Tests";
$languser = "Utilisateurs";
$langgroup = "Groupes";
$langchat = "Discussion";
$langconference = "Conférence";
$langstudent_publication = "Publications";
$langtracking = "Statistiques";
$langhomepage_link = "Ajouter un lien sur la page d'accueil";
$langcourse_setting = "Paramètres du cours";
$langbackup = "Sauvegarder le cours";
$langcopy_course_content = "Copier le contenu du cours";
$langrecycle_course = "Recycler le cours";
?>