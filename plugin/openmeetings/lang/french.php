<?php
/* License: see /license.txt */
//Needed in order to show the plugin title
$strings['plugin_title'] = "OpenMeetings";
$strings['plugin_comment'] = "[Non maintenu] Ajoutez un espace de vidéoconférences aux cours de Chamilo avec OpenMeetings";

$strings['Videoconference'] = "Vidéoconférence";
$strings['MeetingOpened'] = "Session ouverte";
$strings['MeetingClosed'] = "Session fermée";
$strings['MeetingClosedComment'] = "Si vous avez demandé l'enregistrement des sessions de conférence, cet enregistrement apparaîtra dans la liste ci-dessous dans quelques instants.";
$strings['CloseMeeting'] = "Fermer la session";

$strings['MeetingDeleted'] = "Supprimer la session";
$strings['MeetingDeletedComment'] = "";

$strings['VideoConferenceXCourseX'] = "Vidéoconférence #%s, cours %s";
$strings['VideoConferenceAddedToTheCalendar'] = "Vidéoconférence ajoutée au calendrier";
$strings['VideoConferenceAddedToTheLinkTool'] = "Vidéoconférence ajoutée comme lien. Vous pouvez éditer et publier le lien sur la page principale du cours depuis l'outil liens.";

$strings['GoToTheVideoConference'] = "Entrer dans la salle de conférence";

$strings['Records'] = "Enregistrement";
$strings['Meeting'] = "Salle de conférence";

$strings['ViewRecord'] = "Voir l'enregistrement";
$strings['CopyToLinkTool'] = "Ajouter comme lien du cours";

$strings['EnterConference'] = "Entrer dans la salle de conférence";
$strings['RecordList'] = "Liste des enregistrements";
$strings['ServerIsNotRunning'] = "Le serveur de vidéoconférence ne fonctionne pas";
$strings['ServerIsNotConfigured'] = "Le serveur de vidéoconférence n'est pas configuré correctement";

$strings['XUsersOnLine'] = "%s utilisateurs dans la salle";

$strings['host'] = 'Hôte de OpenMeetings';
$strings['host_help'] = "C'est le nom du serveur où le serveur de vidéoconférence a été habilité. Cela peut être http://localhost:5080/openmeetings, une adresse IP (du genre http://192.168.13.54:5080/openmeetings) ou un nom de domaine (du genre http://ma.videoconf.com:5080/openmeetings).";

$strings['salt'] = 'Clef OpenMeetings';
$strings['salt_help'] = "C'est la clef de sécurité de votre serveur OpenMeetings (appelée 'salt' en anglais) qui permet à votre serveur de vérifier l'identité de votre installation de Chamilo et ainsi l'autoriser à se connecter. Veuillez vous référer à la documentation de OpenMeetings pour la localiser.";

$strings['tool_enable'] = 'Outil de vidéoconférence OpenMeetings activé';
$strings['tool_enable_help'] = "Choisissez si vous souhaitez activer l'outil de vidéoconférence OpenMeetings. Une fois activé, il apparaîtra comme un outil additionnel sur toutes les pages principales de cours, et les enseignants pourront démarrer une conférence à n'importe quel moment. Les étudiants ne pourront pas lancer de nouvelle session de conférence, seulement se joindre à une session existante. Si vous ne disposez pas d'un serveur de vidéoconférence OpenMeetings, veuillez <a target=\"_blank\" href=\"http://openmeetings.apache.org/\">en installer un</a> avant de poursuivre, ou demander un devis à l'un des fournisseurs officiels de Chamilo. OpenMeetings est un outil de logiciel libre (et gratuit), mais son installation pourrait présenter une certaine complexité et demander des compétences qui ne sont peut-être pas à la portée de tous. Vous pouvez l'installer vous-même à partir de la documentation (disponible publiquement) de OpenMeetings, ou recherchez un soutien professionnel. Ce soutien pourrait générer certains coûts (au moins le temps de la personne qui vous assiste dans l'opération). Dans le plus pur esprit du logiciel libre, nous vous fournissons les outils pour simplifier votre travail dans la mesure de nos possibilités, et nous vous recommandons des professionnels (les fournisseurs officiels de Chamilo) pour vous venir en aide au cas où ceux-ci seraient insuffisants.<br />";

$strings['openmeetings_welcome_message'] = 'Message de bienvenue de OpenMeetings';
$strings['openmeetings_record_and_store'] = 'Enregistrer les sessions de vidéoconférence';

$strings['plugin_tool_openmeetings'] = 'Vidéo';

$strings['ThereAreNotRecordingsForTheMeetings'] = 'Aucun enregistrement disponible';
$strings['NoRecording'] = "Pas d'enregistrement";
