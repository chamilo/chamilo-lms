<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Speech authentication with Whispeak';
$strings['plugin_comment'] = 'Allow speech authentication in Chamilo, which can integrate to the login or inside tests.';

$strings['enable'] = 'Enable';
$strings['enable_help'] = 'Add <code>$_configuration[\'whispeak_auth_enabled\'] = true;</code> in the <code>configuration.php</code> file';
$strings['api_url'] = 'API URL';
$strings['api_url_help'] = 'http://api.whispeak.io:8080/v1/';
$strings['token'] = 'API key';
$strings['max_attempts'] = 'Max attempts';
$strings['max_attempts_help'] = '(Optional) If the Whispeak authentication is failed x times, then ask and verify the password of the user';
$strings['2fa'] = 'Two-Factor Authentication';
$strings['2fa_help'] = 'Allows extend the login page with a Two-Factor Authentication process. After the classic login, the user must authenticate through Whispeak.';
$strings['ActionRegistryPerUser'] = 'Action registry per user';

$strings['EnrollmentSampleText'] = 'The famous Mona Lisa painting was painted by Leonardo Da Vinci.';
$strings['AuthentifySampleText1'] = 'Dropping Like Flies.';
$strings['AuthentifySampleText2'] = 'Keep Your Eyes Peeled.';
$strings['AuthentifySampleText3'] = 'The fox screams at midnight.';
$strings['AuthentifySampleText4'] = 'Go Out On a Limb.';
$strings['AuthentifySampleText5'] = 'Under the Water.';
$strings['AuthentifySampleText6'] = 'Barking Up The Wrong Tree.';
$strings['RepeatThisPhrase'] = 'Allow audio recording and then read this sentence out loud:';
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
$strings['MarkForSpeechAuthentication'] = 'Mark it for speech authentication';
$strings['EnrollmentTitle'] = "Enrollment to generate voice print with Whispeak";
$strings['Revocation'] = "Revocation";
$strings['DeleteEnrollments'] = "Delete enrollments";
$strings['NoEnrollment'] = "No enrollment.";
$strings['EnrollmentDeleted'] = "Enrollment deleted";
