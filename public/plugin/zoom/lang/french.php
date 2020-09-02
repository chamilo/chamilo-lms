<?php
/* License: see /license.txt */

// Needed in order to show the plugin title
$strings['plugin_title'] = "Conférence vidéo Zoom";
$strings['plugin_comment'] = "Intégration de conférences vidéo Zoom dans les cours et les sessions";

$strings['tool_enable'] = 'Outil de conférence vidéos Zoom activé';
$strings['apiKey'] = "Clé d'API (<em>API Key</em>)";
$strings['apiSecret'] = "Code secret d'API (<em>API Secret</em>)";
$strings['enableParticipantRegistration'] = "Activer l'inscription des participants";
$strings['enableCloudRecording'] = "Type d'enregistrement automatique";
$strings['enableGlobalConference'] = "Activer les conférences globales";
$strings['enableGlobalConferencePerUser'] = "Activer les conférences globales par utilisateur";
$strings['globalConferenceAllowRoles'] = "Visibilité du lien de vidéo conférence global pour les profils suivant";
$strings['globalConferencePerUserAllowRoles'] = "Visibilité du lien de vidéo conférence global par utilisateur pour les profils suivant";

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
<br/>5. Information: remplissez quelques champs à propos de votre \"App\"
(noms de l'application, de l'entreprise, nom et adresse de courriel de contact)
<br/>6. cliquez sur <em>Continue</em>
<br/>7. App Credentials :
<strong>copiez la clé (API Key) et le code secret (API Secret) dans les champs ci-dessous.</strong>
<br/>8. cliquez sur <em>Continue</em>
<br/>9. Feature :
activez <em>Event Subscriptions</em> pour en ajouter une avec comme endpoint URL
<code>https://your.chamilo.url/plugin/zoom/endpoint.php</code>
et ajoutez ces types d'événements :
<br/>- Start Meeting
<br/>- End Meeting
<br/>- Participant/Host joined meeting
<br/>- Participant/Host left meeting
<br/>- All Recordings have completed
<br/>- Recording transcript files have completed
<br/>puis cliquez sur <em>Done</em> puis sur <em>Save</em>
et <strong>copiez votre Verification Token dans le champ ci-dessous</strong>.
<br/>10. cliquez sur <em>Continue</em>
<br/>
<strong>Attention</strong> :
<br/>Zoom n'est <em>PAS</em> un logiciel libre
et des règles spécifiques de protection des données personnelles s'y appliquent.
Merci de vérifier auprès de Zoom qu'elles sont satisfaisantes pour vous et les apprenants qui l'utiliseront.";

$strings['enableParticipantRegistration_help'] = "Nécessite un profil Zoom payant.
Ne fonctionnera pas pour un profil <em>de base</em>.";

$strings['enableCloudRecording_help'] = "Nécessite un profil Zoom payant.
Ne fonctionnera pas pour un profil <em>de base</em>.";

// please keep these lines alphabetically sorted
$strings['AllCourseUsersWereRegistered'] = "Tous les étudiants du cours sont inscrits";
$strings['Agenda'] = "Ordre du jour";
$strings['CannotRegisterWithoutEmailAddress'] = "Impossible d'inscrire un utilisateur sans adresse de courriel";
$strings['CopyingJoinURL'] = "Copie de l'URL pour rejoindre en cours";
$strings['CopyJoinAsURL'] = "Copier l'URL pour 'rejoindre en tant que'";
$strings['CopyToCourse'] = "Copier dans le cours";
$strings['CouldNotCopyJoinURL'] = "Échec de la copie de l'URL pour rejoindre";
$strings['Course'] = "Cours";
$strings['CreatedAt'] = "Créé à";
$strings['CreateLinkInCourse'] = "Créer dans le cours un ou des lien(s) vers le(s) fichier(s)";
$strings['CreateUserVideoConference'] = "Créer des conferences par utilisateur";
$strings['DateMeetingTitle'] = "%s : %s";
$strings['DeleteMeeting'] = "Effacer la conférence";
$strings['DeleteFile'] = "Supprimer ce(s) fichier(s)";
$strings['Details'] = "Détail";
$strings['DoIt'] = "Fais-le";
$strings['Duration'] = "Durée";
$strings['DurationFormat'] = "%hh%I";
$strings['DurationInMinutes'] = "Durée (en minutes)";
$strings['EndDate'] = "Date de fin";
$strings['EnterMeeting'] = "Entrer dans la conférence";
$strings['ViewMeeting'] = "Voir la conférence";
$strings['Files'] = "Fichiers";
$strings['Finished'] = "terminée";
$strings['FileWasCopiedToCourse'] = "Le fichier a été copié dans le cours";
$strings['FileWasDeleted'] = "Le fichier a été effacé";
$strings['GlobalMeeting'] = "Conférence globale";
$strings['GroupUsersWereRegistered'] = "Les membres des groupes ont été inscrits";
$strings['InstantMeeting'] = "Conférence instantanée";
$strings['Join'] = "Rejoindre";
$strings['JoinGlobalVideoConference'] = "Rejoindre la conférence globale";
$strings['JoinURLCopied'] = "URL pour rejoindre copiée";
$strings['JoinURLToSendToParticipants'] = "URL pour assister à la conférence (à envoyer aux participants)";
$strings['LiveMeetings'] = "Conférences en cours";
$strings['LinkToFileWasCreatedInCourse'] = "Un lien vers le fichier a été ajouter au cours";
$strings['MeetingDeleted'] = "Conférence effacée";
$strings['MeetingsFound'] = "Conférences trouvées";
$strings['MeetingUpdated'] = "Conférence mise à jour";
$strings['NewMeetingCreated'] = "Nouvelle conférence créée";
$strings['Password'] = "Mot de passe";
$strings['RecurringWithFixedTime'] = "Recurrent, à heure fixe";
$strings['RecurringWithNoFixedTime'] = "Recurrent, sans heure fixe";
$strings['RegisterAllCourseUsers'] = "Inscrire tous les utilisateurs du cours";
$strings['RegisteredUserListWasUpdated'] = "Liste des utilisateurs inscrits mise à jour";
$strings['RegisteredUsers'] = "Utilisateurs inscrits";
$strings['RegisterNoUser'] = "N'inscrire aucun utilisateur";
$strings['RegisterTheseGroupMembers'] = "Inscrire les membres de ces groupes";
$strings['ScheduleAMeeting'] = "Programmer une conférence";
$strings['ScheduledMeeting'] = "Conférence programmée";
$strings['ScheduledMeetings'] = "Conférences programmées";
$strings['ScheduleAMeeting'] = "Programmer une conférence";
$strings['SearchMeeting'] = "Rechercher une conférence";
$strings['Session'] = "Session";
$strings['StartDate'] = "Date de début";
$strings['Started'] = "démarrée";
$strings['StartInstantMeeting'] = "Démarrer une conférence instantanée";
$strings['StartMeeting'] = "Démarrer la conférence";
$strings['StartTime'] = "Heure de début";
$strings['Topic'] = "Objet";
$strings['TopicAndAgenda'] = "Objet et ordre du jour";
$strings['Type'] = "Type";
$strings['UpcomingMeeting'] = "Conférences à venir";
$strings['UpdateMeeting'] = "Mettre à jour la conférence";
$strings['UpdateRegisteredUserList'] = "Mettre à jour la liste des utilisateurs inscrits";
$strings['UserRegistration'] = "Inscription des utilisateurs";
$strings['Y-m-d H:i'] = "d/m/Y à H\hi";
$strings['verificationToken'] = 'Verification Token';
$strings['Waiting'] = "en attente";
$strings['XRecordingOfMeetingXFromXDurationXDotX'] = "Enregistrement (%s) de la conférence %s de %s (%s).%s";
$strings['YouAreNotRegisteredToThisMeeting'] = "Vous n'êtes pas inscrit à cette conférence";
$strings['ZoomVideoConferences'] = "Conférences vidéo Zoom";
$strings['Recordings'] = "Enregistrements";
$strings['CreateGlobalVideoConference'] = "Créer une conférence global";
$strings['JoinURLNotAvailable'] = "URL pas disponible";
$strings['Meetings'] = "Conférences";
$strings['Activity'] = "Activité";
$strings['ConferenceNotAvailable'] = "Conférence non disponible";
