<?php

/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Experience API (xAPI)';
$strings['plugin_comment'] = 'Permet l\'intégration d\'un Learning Record Store (interne ou externe) et de clients xAPI';

$strings[XApiPlugin::SETTING_UUID_NAMESPACE] = 'Namespace UUID';
$strings[XApiPlugin::SETTING_UUID_NAMESPACE.'_help'] = 'Namespace pour identifiants uniques universels qui servent comme IDs de déclaration xAPI.'
    .'<br>Cette valeur est générée automatiquement par Chamilo, <strong>ne la modifiez pas.</strong>';
$strings['lrs_url'] = 'Point d\'entrée LRS';
$strings['lrs_url_help'] = 'URL de base du LRS';
$strings['lrs_auth_username'] = 'Utilisateur LRS';
$strings['lrs_auth_username_help'] = 'Nom d\'utilisateur pour l\'authentification HTTP de base';
$strings['lrs_auth_password'] = 'Mot de passe LRS';
$strings['lrs_auth_password_help'] = 'Mot de passe pour l\'authentification HTTP de base';
$strings['cron_lrs_url'] = 'Cron: LRS endpoint';
$strings['cron_lrs_url_help'] = 'Alternative base URL of the LRS for the cron process';
$strings['cron_lrs_auth_username'] = 'Cron: LRS user';
$strings['cron_lrs_auth_username_help'] = 'Alternative username for basic HTTP authentication for the cron process';
$strings['cron_lrs_auth_password'] = 'Cron: LRS password';
$strings['cron_lrs_auth_password_help'] = 'Alternative password for basic HTTP authentication for the cron process';
$strings['lrs_lp_item_viewed_active'] = 'Élément de parcours visionné';
$strings['lrs_lp_end_active'] = 'Parcours terminé';
$strings['lrs_quiz_active'] = 'Exercice terminé';
$strings['lrs_quiz_question_active'] = 'Question d\'exercice répondue';
$strings['lrs_portfolio_active'] = 'Événements de portfolio';

$strings['NoActivities'] = 'Aucune activité ajoutée pour l\'instant';
$strings['ActivityTitle'] = 'Activité';
$strings['AddActivity'] = 'Ajouter activité';
$strings['TinCanPackage'] = 'Paquet TinCan (zip)';
$strings['OnlyZipAllowed'] = 'Seuls les fichiers ZIP sont autorisés (.zip).';
$strings['ActivityImported'] = 'Activité importée.';
$strings['EditActivity'] = 'Éditer activité';
$strings['ActivityUpdated'] = 'Activité mise à jour';
$strings['ActivityLaunchUrl'] = 'URL de lancement';
$strings['ActivityId'] = 'ID d\'activité';
$strings['ActivityType'] = 'Type d\'activité';
$strings['ActivityDeleted'] = 'Activité supprimée';
$strings['ActivityLaunch'] = 'Lancer';
$strings['ActivityFirstLaunch'] = 'Premier lancement à';
$strings['ActivityLastLaunch'] = 'Dernier lancement à';
$strings['LaunchNewAttempt'] = 'Lancer nouvelle tentative';
$strings['LrsConfiguration'] = 'Configuration LRS';
$strings['Verb'] = 'Verbe';
$strings['Actor'] = 'Acteur';
$strings['ToolTinCan'] = 'Activités';
$strings['ActivityAddedToLPCannotBeAccessed'] = 'Cet activité fait partie d\'un parcours d\'apprentissage, il n\'est donc pas accessible par les étudiants depuis cette page';
