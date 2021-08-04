<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Authentification vocale avec Whispeak';
$strings['plugin_comment'] = 'Autoriser l\'authentification de la voix dans Chamilo.';

$strings['enable'] = 'Activer';
$strings['enable_help'] = '<p>Ajoutez <code>$_configuration[\'whispeak_auth_enabled\'] = true;</code> dans le fichier <code>configuration.php</code></p>';
$strings['api_url'] = 'URL de l\'API';
$strings['api_url_help'] = 'http://api.whispeak.io:8080/v1.1/';
$strings['token'] = 'Clef API';
$strings['max_attempts'] = 'Tentatives maximum';
$strings['max_attempts_help'] = '(Optionnel) Si l\'authentification Whispeak échoue x fois, alors abandonner et demander le mot de passe de l\'utilisateur';
$strings['2fa'] = 'Authentification à 2 facteurs (2FA)';
$strings['2fa_help'] = 'Autoriser l\'extension du formulaire de login par une page d\'authentification forte. Après le login classique, l\'utilisateur/trice devra aussi s\'authentifier au travers de Whispeak.';
$strings['ActionRegistryPerUser'] = 'Registre d\'actions par utilisateur';

$strings['EnrollmentSampleText'] = 'Le fameux chef-d\'oeuvre Mona Lisa a été peint par Léonardo da Vinci.';
$strings['AuthentifySampleText1'] = 'Tomber comme des mouches.';
$strings['AuthentifySampleText2'] = 'Restez vigilants.';
$strings['AuthentifySampleText3'] = 'Le renard hurle à minuit.';
$strings['AuthentifySampleText4'] = 'Errer dans la campagne.';
$strings['AuthentifySampleText5'] = 'Sous l\'océan.';
$strings['AuthentifySampleText6'] = 'Prendre la mouche.';
$strings['RepeatThisPhrase'] = 'Autorisez l\'enregistrement audio puis lisez cette phrase à voix haute :';
$strings['SpeechAuthAlreadyEnrolled'] = 'L\'authentification de voix a déjà réussi précédemment.';
$strings['SpeechAuthNotEnrolled'] = 'L\'authentification de voix n\'a pas encore été enregistrée.';
$strings['SpeechAuthentication'] = 'Authentification par la voix';
$strings['EnrollmentFailed'] = 'Échec à l\'inscription.';
$strings['EnrollmentSuccess'] = 'Inscription réussie.';
$strings['AuthentifyFailed'] = 'Échec de l\'authentification.';
$strings['AuthentifySuccess'] = 'Authentification réussie!';
$strings['TryAgain'] = 'Essayez encore';
$strings['MaxAttemptsReached'] = 'Vous avez atteint le nombre maximum de tentatives autorisées.';
$strings['LoginWithUsernameAndPassword'] = 'Authentifiez-vous en utilisant votre mot de passe.';
$strings['YouNeedToIdentifyYourselfToAnswerThisQuestion'] = 'Vous devez vous authentifier pour répondre à cette question.';
$strings['IdentifyMe'] = 'M\'identifier';
$strings['PleaseWaitWhileLoading'] = 'Connexion au serveur d\'authentification. Veuillez patienter...';
$strings['Quality'] = 'Quality';
$strings['Failed'] = "Failed";
$strings['ActivityId'] = "Activity ID";
$strings['Success'] = "Success";
$strings['MarkForSpeechAuthentication'] = 'Cocher pour l\'authentification par la voix';
$strings['EnrollmentTitle'] = "Enrôlement pour générer l'empreinte vocale avec Whispeak";
$strings['Revocation'] = "Révocation";
$strings['DeleteEnrollments'] = "Supprimer les inscriptions";
$strings['NoEnrollment'] = "Aucune inscription";
$strings['EnrollmentDeleted'] = "Inscription supprimée.";
