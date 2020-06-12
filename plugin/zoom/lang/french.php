<?php
/* License: see /license.txt */

// Needed in order to show the plugin title
$strings['plugin_title'] = "Conférence vidéo Zoom";
$strings['plugin_comment'] = "Intégration de conférences vidéo Zoom dans les cours et les sessions";

$strings['tool_enable'] = 'Outil de conférence vidéos Zoom activé';
$strings['apiKey'] = "Clé d'API (<em>API Key</em>)";
$strings['apiSecret'] = "Code secret d'API (<em>API Secret</em>)";

$strings['tool_enable_help'] = "Choisissez si vous voulez activer l'outil de conférence vidéo Zoom.
Une fois activé, il apparaitra dans les pages d'accueil de tous les cours :
les enseignants pourront <strong>démarrer</strong> une conférence et les étudiants la <strong>rejoindre</strong>.
<br/>
Ce plugin requiert un compte Zoom pour gérer les conférences.
L'API de Zoom utilise les <em>JSON Web Tokens (JWT)</em> pour autoriser l'accès à un compte.
<strong>Une <em>clé</em> et un <em>code secret</em> d'API sont requis</strong> pour s'authentifier avec JWT.
Pour les obtenir, créez une <em>JWT app</em> :
<br/>1. logguez vous sur <a href=\"https://zoom.us/profile\">Votre profil Zoom</a>
<br/>2. cliquez sur <em>Avancé / Marketplace d'application</em>
<br/>3. cliquez sur <em><a href=\"https://marketplace.zoom.us/develop/create\">Develop / build App</a></em>
<br/>4. choisissez <em>JWT / Create</em>
<br/>5. saisissez quelques informations sur votre \"App\"
(noms de l'application, de l'entreprise, nom et adresse de courriel de contact)
<br/>6. cliquez sur <em>Continue</em>
<br/>La page <em>App Credentials</em> affiche la clé (API Key) and le code secret (API Secret) à saisir ici.
<br/>
<strong>Attention</strong> :
<br/>Zoom n'est <em>PAS</em> un logiciel libre
et des règles spécifiques de protection des données personnelles s'y appliquent.
Merci de vérifier auprès de Zoom qu'elles sont satisfaisantes pour vous et les apprenants qui l'utiliseront.";

// please keep these lines alphabetically sorted
$strings['%Hh%I'] = "%Hh%I";
$strings['Agenda'] = "Ordre du jour";
$strings['Course'] = "Cours";
$strings['CreatedAt'] = "Créé à";
$strings['DeleteMeeting'] = "Effacer la conférence";
$strings['Details'] = "Détail";
$strings['Duration'] = "Durée";
$strings['DurationInMinutes'] = "Durée (en minutes)";
$strings['EndDate'] = "Date de fin";
$strings['Instant'] = "Instantané";
$strings['Join'] = "Rejoindre";
$strings['JoinURLToSendToParticipants'] = "URL pour assister à la conférence (à envoyer aux participants)";
$strings['Live'] = "En cours";
$strings['LiveMeetings'] = "Conférences en cours";
$strings['Meeting'] = "Conférence";
$strings['MeetingDeleted'] = "Conférence effacée";
$strings['NewMeetingCreated'] = "Nouvelle conférence créée";
$strings['Participants'] = "Participants";
$strings['Password'] = "Mot de passe";
$strings['Recordings'] = "Enregistrements";
$strings['RecurringWithFixedTime'] = "Recurrent, à heure fixe";
$strings['RecurringWithNoFixedTime'] = "Recurrent, sans heure fixe";
$strings['ScheduleMeeting'] = "Programmer une conférence";
$strings['Scheduled'] = "Programmées";
$strings['ScheduledMeetings'] = "Conférences programmées";
$strings['Search'] = "Rechercher";
$strings['Session'] = "Session";
$strings['StartDate'] = "Date de début";
$strings['StartInstantMeeting'] = "Démarrer une conférence instantanée";
$strings['StartTime'] = "Heure de début";
$strings['StartURLNotToBeShared'] = "URL de démarrage de la conférence (à ne pas partager)";
$strings['Status'] = "Statut";
$strings['Topic'] = "Objet";
$strings['TopicAndAgenda'] = "Objet et ordre du jour";
$strings['Type'] = "Type";
$strings['Upcoming'] = "À venir";
$strings['UpdateMeeting'] = "Mettre à jour la conférence";
$strings['Y-m-d H:i'] = "d/m/Y à H\hi";
$strings['ZoomVideoconference'] = "Conférence vidéo Zoom";
