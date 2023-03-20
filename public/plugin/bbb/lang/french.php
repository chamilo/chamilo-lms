<?php
/* License: see /license.txt */
//Needed in order to show the plugin title
$strings['plugin_title'] = "Vidéoconférence";
$strings['plugin_comment'] = "Ajoutez un espace de vidéoconférences aux cours de Chamilo avec BigBlueButton (BBB)";

$strings['Videoconference'] = "Vidéoconférence";
$strings['MeetingOpened'] = "Session ouverte";
$strings['MeetingClosed'] = "Session fermée";
$strings['MeetingClosedComment'] = "Si vous avez demandé l'enregistrement des sessions de conférence, cet enregistrement apparaîtra dans la liste ci-dessous dans quelques instants.";
$strings['CloseMeeting'] = "Fermer la session";

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

$strings['host'] = 'Hôte de BigBlueButton';
$strings['host_help'] = "C'est le nom du serveur où le serveur de vidéoconférence a été habilité.
Cela peut être localhost, une adresse IP (du genre http://192.168.13.54) ou un nom de domaine (du genre http://ma.video.com).";

$strings['salt'] = 'Clef BigBlueButton';
$strings['salt_help'] = "C'est la clef de sécurité de votre serveur BigBlueButton (appelée 'salt' en anglais) qui permet à votre serveur de vérifier l'identité de votre installation de Chamilo et ainsi l'autoriser à se connecter. Veuillez vous référer à la documentation de BigBlueButton pour la localiser, ou utilisez la commande 'bbb-conf --salt' si vous disposez d'un accès en ligne de commande au serveur de vidéoconférence.";

$strings['tool_enable'] = 'Outil de vidéoconférence BigBlueButton activé';
$strings['tool_enable_help'] = "Choisissez si vous souhaitez activer l'outil de vidéoconférence BigBlueButton.
    Une fois activé, il apparaîtra comme un outil additionnel sur toutes les pages principales de cours, et les enseignants pourront démarrer une conférence à n'importe quel moment. Les étudiants ne pourront pas lancer de nouvelle session de conférence, seulement se joindre à une session existante. Si vous ne disposez pas d'un serveur de vidéoconférence BigBlueButton, veuillez <a target=\"_blank\" href=\"http://bigbluebutton.org/\">en installer un</a> avant de poursuivre, ou demander un devis à l'un des fournisseurs officiels de Chamilo. BigBlueButton est un outil de logiciel libre (et gratuit), mais son installation pourrait présenter une certaine complexité et demander des compétences qui ne sont peut-être pas à la portée de tous. Vous pouvez l'installer vous-même à partir de la documentation (disponible publiquement) de BigBlueButton, ou recherchez un soutien professionnel. Ce soutien pourrait générer certains coûts (au moins le temps de la personne qui vous assiste dans l'opération). Dans le plus pur esprit du logiciel libre, nous vous fournissons les outils pour simplifier votre travail dans la mesure de nos possibilités, et nous vous recommandons des professionnels (les fournisseurs officiels de Chamilo) pour vous venir en aide au cas où ceux-ci seraient insuffisants.<br />";

$strings['big_blue_button_welcome_message'] = 'Message de bienvenue de BigBlueButton';
$strings['enable_global_conference'] = 'Activer les conférences globales';
$strings['enable_global_conference_per_user'] = 'Activer les conférences globales par utilisateur';
$strings['enable_conference_in_course_groups'] = 'Activer les conférences dans les groupes';
$strings['enable_global_conference_link'] = 'Activer le lien vers la conférence globale sur la page principale';

$strings['big_blue_button_record_and_store'] = 'Enregistrer les sessions de vidéoconférence';
$strings['bbb_enable_conference_in_groups'] = 'Permettre la création de vidéoconférence pour les groupes';
$strings['plugin_tool_bbb'] = 'Vidéo';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Aucun enregistrement disponible';
$strings['NoRecording'] = "Pas d'enregistrement";
$strings['ClickToContinue'] = 'Cliquez pour continuer';
$strings['NoGroup'] = 'Sans groupe';
$strings['UrlMeetingToShare'] = 'URL à partager';

$strings['AdminView'] = 'Vue administrateur';
$strings['max_users_limit'] = 'Utilisateurs maximum';
$strings['max_users_limit_help'] = 'Nombre maximum d\'utilisateurs simultanés dans une salle de vidéoconférence de cours ou cours-session. Laisser vide ou sur 0 pour ne pas assigner de limite.';
$strings['MaxXUsersWarning'] = 'Cette salle de conférence est limitée à %s utilisateurs simultanés.';
$strings['MaxXUsersReached'] = 'La limite de %s utilisateurs simultanés a été atteinte dans cette salle de conférence. Veuillez rafraîchir dans quelque minutes pour voir si un siège s\'est libéré, ou attendre l\'ouverture d\'une nouvelle salle de conférence pour participer.';
$strings['MaxXUsersReachedManager'] = 'La limite de %s utilisateurs simultanés a été atteinte dans cette salle de conférence. Pour augmenter la limite, prenez contact avec l\'administrateur du portail.';
$strings['MaxUsersInConferenceRoom'] = 'Nombre max d\'utilisateurs simultanés dans une salle de conférence';
$strings['global_conference_allow_roles'] = "Visibilité du lien de vidéo conférence global pour les profils suivant";
$strings['CreatedAt'] = "Créé à";

$strings['bbb_force_record_generation'] = 'Forcer la génération de l\'enregistrement à la fin de la session';
$strings['ThereIsNoVideoConferenceActive'] = "Il n'y a aucune vidéoconférence actuellement active";
$strings['meeting_duration'] = 'Durée de la conférence (en minutes)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Permettre aux apprenants de démarrer les vidéoconferénces de leurs groupes';
