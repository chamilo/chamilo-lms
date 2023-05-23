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
<p>L'API de Zoom utilise les <em>JSON Web Tokens (JWT)</em> pour autoriser l'accès à un compte. 
Pour les obtenir, créez une application JWT ou une application OAuth serveur à serveur :</p>
<blockquote>
  <p>À partir du 1er juin 2023, Zoom recommande de créer une application OAuth serveur à serveur 
pour remplacer la fonctionnalité d'une application JWT dans votre compte.</p>
</blockquote>
<ol>
<li>Connectez-vous à votre <a href=\"https://zoom.us/profile\">page de profil Zoom</a></li>
<li>Cliquez sur Avancé / Marketplace d'application</li>
<li>Cliquez sur <a href=\"https://marketplace.zoom.us/develop/create\">Développer / Créer une application</a></li>
<li>Choisissez JWT ou OAuth serveur à serveur, puis Créer</li>
<li>Informations : Remplissez les champs sur votre \"App\" (noms de l'application et de la société, nom et adresse e-mail de contact)</li>
<li>Cliquez sur Continuer</li>
<li>Identifiants de l'application
<ol>
<li>Pour une application JWT : Copiez votre clé API (API Key) et votre code secret (API Secret) dans la configuration du plugin</li>
<li>Pour une application OAuth serveur à serveur : Copiez votre <em>ID de compte</em>, votre <em>ID de client</em> et votre <em>secret de client</em> dans la configuration du plugin</li>
</ol></li>
<li>Cliquez sur Continuer</li>
<li><p>Fonctionnalité : activez les <em>Abonnements aux événements / Event Subscriptions</em> pour en ajouter un nouveau avec comme endpoint URL
<code>https://your.chamilo.url/plugin/zoom/endpoint.php</code> (validez le point de terminaison pour permettre l'activation de l'application) et ajoutez
ces types d'événements :</p>
<ul>
<li>Démarrer une réunion / Start Meeting</li>
<li>Terminer une réunion / End Meeting</li>
<li>Participant/hôte a rejoint la réunion / Participant/Host joined meeting</li>
<li>Participant/hôte a quitté la réunion / Participant/Host left meeting</li>
<li>Démarrer un webinar / Start Webinar</li>
<li>Terminer un webinar / End Webinar</li>
<li>Participant/hôte a rejoint le webinar / Participant/Host joined webinar</li>
<li>Participant/hôte a quitté le webinar / Participant/Host left webinar</li>
<li>Toutes les enregistrements sont terminées / All Recordings have completed</li>
<li>Les fichiers de transcription d'enregistrement sont terminés / Recording transcript files have completed</li>
</ul>
<p>Ensuite, cliquez sur Terminé, puis sur Enregistrer et copiez votre <em>Jeton de vérification / Verification Token</em> si vous avez une application JWT ou le <em>Jeton Secret / Secret
Token</em> si vous avez une application OAuth serveur à serveur dans la configuration du plugin</p></li>
<li>cliquez sur Continuer</li>
<li>Scopes (uniquement pour l'application OAuth serveur à serveur) : cliquez sur <em>Ajouter des scopes / Add Scopes</em> et sélectionnez <em>meeting:write:admin</em>, <em>webinar:write:admin</em>, <em>recording:write:admin</em>. Puis cliquez sur Terminé.</li>
</ol>
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
$strings['SignAttendance'] = "Signer la feuille d'émargement";
$strings['ReasonToSign'] = "Explication pour signer la feuille d'émargement";
$strings['ConferenceWithAttendance'] = "Conférence avec signature d'émargement";
$strings['Sign'] = "Signer";
$strings['Signature'] = "Signature";
$strings['Meeting'] = "Meeting";
$strings['Webinar'] = "Webinar";
$strings['AudienceType'] = "Type d'audience";
$strings['AccountEmail'] = "Compte email";
$strings['NewWebinarCreated'] = "Nouveau webinar créé";
$strings['UpdateWebinar'] = "Mettre à jour le webinar";
$strings['WebinarUpdated'] = "Webinar mis à jour";
$strings['DeleteWebinar'] = "Supprimer le webinar";
$strings['WebinarDeleted'] = "Webinar supprimé";
$strings['UrlForSelfRegistration'] = "URL pour l'auto-inscription des participants";
$strings['RegisterMeToConference'] = "M'inscrire à la visio";
$strings['UnregisterMeToConference'] = "Me désinscrire de la visio";
$strings['ForEveryone'] = "Tout le monde";
$strings['SomeUsers'] = "Utilisateurs inscrits (à inscrire après)";
