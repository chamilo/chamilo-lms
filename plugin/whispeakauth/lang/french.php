<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Authentification vocale avec Whispeak';
$strings['plugin_comment'] = 'Allow speech authentication in Chamilo.';

$strings['enable'] = 'Enable';
$strings['enable_help'] = '<p>Add <code>$_configuration[\'whispeak_auth_enabled\'] = true;</code> in the <code>configuration.php</code> file</p>';
$strings['api_url'] = 'API URL';
$strings['api_url_help'] = 'http://api.whispeak.io:8080/v1/';
$strings['token'] = 'API key';
$strings['max_attempts'] = 'Max attempts';
$strings['max_attempts_help'] = '(Optional) If the Whispeak authentication is failed x times, then ask and verify the password of the user';
$strings['2fa'] = 'Two-Factor Authentication';
$strings['2fa_help'] = 'Allows extend the login page with a Two-Factor Authentication process. After the classic login, the user must authenticate through Whispeak.';

$strings['EnrollmentSampleText'] = 'Le fameux chef-d\'oeuvre Mona Lisa a été peint par Léonardo da Vinci.';
$strings['AuthentifySampleText1'] = 'Dropping Like Flies.';
$strings['AuthentifySampleText2'] = 'Keep Your Eyes Peeled.';
$strings['AuthentifySampleText3'] = 'The fox screams at midnight.';
$strings['AuthentifySampleText4'] = 'Go Out On a Limb.';
$strings['AuthentifySampleText5'] = 'Under the Water.';
$strings['AuthentifySampleText6'] = 'Barking Up The Wrong Tree.';

$strings['RepeatThisPhrase'] = 'Autorisez l\'enregistrement audio puis répétez cette phrase trois fois:';
$strings['EnrollmentSignature0'] = 'Signature non viable, nécessite un nouvel enrôlement';
$strings['EnrollmentSignature1'] = 'Signature passable, conseil de faire un nouvel enrôlement.';
$strings['EnrollmentSignature2'] = 'Signature correcte.';
$strings['EnrollmentSignature3'] = 'Signature bonne.';
$strings['SpeechAuthAlreadyEnrolled'] = 'Speech authentication already enrolled previously.';
$strings['SpeechAuthNotEnrolled'] = 'Speech authentication not enrolled previously.';
$strings['SpeechAuthentication'] = 'Authentification de voix';
$strings['EnrollmentFailed'] = 'Échec à l\'inscription.';
$strings['EnrollmentSuccess'] = 'Inscription réussie.';
$strings['AuthentifyFailed'] = 'Échec de l\'authentification.';
$strings['AuthentifySuccess'] = 'Authentification réussie!';
$strings['TryAgain'] = 'Essayez encore';
$strings['MaxAttemptsReached'] = 'You reached the maximum number of attempts allowed.';
$strings['LoginWithUsernameAndPassword'] = 'You should login using the username and password.';
$strings['YouNeedToIdentifyYourselfToAnswerThisQuestion'] = 'You need to identify yourself to answer this question.';
$strings['IdentifyMe'] = 'Identify me';
$strings['PleaseWaitWhileLoading'] = "Please wait while loading...";

$strings['AudioQualityShort'] = 'Too short audio';
$strings['AudioQualityQuiet'] = 'Too quiet audio';
$strings['AudioQualityLoud'] = 'Too loud audio';
$strings['AudioQualityNoisy'] = 'Too noisy audio';
$strings['AudioQualityFrequency'] = 'Missing some audio frequencies';
$strings['AudioQualityPoorness'] = 'Too poor general audio quality';

$strings['AgreeAllowResearch'] = 'I agree to allow the use of data for research (no commercial usage).';

$strings['MarkForSpeechAuthentication'] = 'Mark it for speech authentication';
