<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Speech authentication with Whispeak';
$strings['plugin_comment'] = 'Allow speech authentication in Chamilo.';

$strings['enable'] = 'Enable';
$strings['enable_help'] = 'Add <code>$_configuration[\'whispeak_auth_enabled\'] = true;</code> in the <code>configuration.php</code> file';
$strings['api_url'] = 'API URL';
$strings['api_url_help'] = 'http://api.whispeak.io:8080/v1/';
$strings['token'] = 'API key';
$strings['max_attempts'] = 'Max attempts';
$strings['max_attempts_help'] = '(Optional) If the Whispeak authentication is failed x times, then ask and verify the password of the user';
$strings['2fa'] = 'Two-Factor Authentication';
$strings['2fa_help'] = 'Allows extend the login page with a Two-Factor Authentication process. After the classic login, the user must authenticate through Whispeak.';

$strings['EnrollmentSampleText'] = 'The famous Mona Lisa painting was painted by Leonardo Da Vinci.';
$strings['AuthentifySampleText1'] = 'Dropping Like Flies.';
$strings['AuthentifySampleText2'] = 'Keep Your Eyes Peeled.';
$strings['AuthentifySampleText3'] = 'The fox screams at midnight.';
$strings['AuthentifySampleText4'] = 'Go Out On a Limb.';
$strings['AuthentifySampleText5'] = 'Under the Water.';
$strings['AuthentifySampleText6'] = 'Barking Up The Wrong Tree.';

$strings['RepeatThisPhrase'] = 'Allow audio recording and then read this sentence out loud:';
$strings['EnrollmentSignature0'] = 'Unsustainable signature requires a new enrollment.';
$strings['EnrollmentSignature1'] = 'Passable signature, advice to make a new enrollment.';
$strings['EnrollmentSignature2'] = 'Correct signature.';
$strings['EnrollmentSignature3'] = 'Good signature.';
$strings['SpeechAuthAlreadyEnrolled'] = 'Speech authentication already enrolled previously.';
$strings['SpeechAuthNotEnrolled'] = 'Speech authentication not enrolled previously.';
$strings['SpeechAuthentication'] = 'Speech authentication';
$strings['EnrollmentFailed'] = 'Enrollment failed.';
$strings['EnrollmentSuccess'] = 'Enrollment success.';
$strings['AuthentifyFailed'] = 'Login failed.';
$strings['AuthentifySuccess'] = 'Authentication success!';
$strings['TryAgain'] = 'Try again';
$strings['MaxAttemptsReached'] = 'You reached the maximum number of attempts allowed.';
$strings['LoginWithUsernameAndPassword'] = 'You should login using the username and password.';
$strings['YouNeedToIdentifyYourselfToAnswerThisQuestion'] = 'You need to identify yourself to answer this question.';
$strings['IdentifyMe'] = 'Identify me';
$strings['PleaseWaitWhileLoading'] = "Please wait while loading...";
$strings['Quality'] = 'Quality';
$strings['Failed'] = "Failed";
$strings['ActivityId'] = "Activity ID";
$strings['Success'] = "Success";

$strings['AudioQualityShort'] = 'Too short audio';
$strings['AudioQualityQuiet'] = 'Too quiet audio';
$strings['AudioQualityLoud'] = 'Too loud audio';
$strings['AudioQualityNoisy'] = 'Too noisy audio';
$strings['AudioQualityFrequency'] = 'Missing some audio frequencies';
$strings['AudioQualityPoorness'] = 'Too poor general audio quality';

$strings['MarkForSpeechAuthentication'] = 'Mark it for speech authentication';
