<?php

/* For licensing terms, see /license.txt. */

$strings['plugin_title'] = 'PENS';
$strings['plugin_comment'] = 'Fournit le support du standard d’échange de paquets PENS. Point d’entrée préféré : /plugin/pens/collect.';

$strings['system_user_id'] = 'Identifiant utilisateur système PENS';
$strings['system_user_id_help'] = 'Le system-user-id que l’outil auteur doit envoyer. Les requêtes collect sont refusées tant que les deux identifiants ne sont pas configurés.';
$strings['system_password'] = 'Mot de passe système PENS';
$strings['system_password_help'] = 'Le system-password que l’outil auteur doit envoyer.';

$strings['PensAdminTitle'] = 'PENS';
$strings['PensAdminIntro'] = 'Cette page affiche les paquets reçus par le point d’entrée PENS dans Chamilo 2.';
$strings['PensBackToPlugins'] = 'Retour aux plugins';
$strings['PensNoPackages'] = 'Aucun paquet PENS n’a encore été reçu.';

$strings['PensId'] = 'ID';
$strings['PensPackageId'] = 'Package ID';
$strings['PensClient'] = 'Client';
$strings['PensType'] = 'Type';
$strings['PensFormat'] = 'Format';
$strings['PensStoredFile'] = 'Fichier stocké';
$strings['PensCreated'] = 'Créé';
$strings['PensUpdated'] = 'Mis à jour';
$strings['PensVendorData'] = 'Vendor data';

$strings['PensCurrentBehavior'] = 'Comportement actuel';
$strings['PensBehaviorReceive'] = 'Reçoit les requêtes externes PENS collect via /plugin/pens/collect.';
$strings['PensBehaviorDownload'] = 'Télécharge le paquet ZIP distant.';
$strings['PensBehaviorStore'] = 'Stocke le paquet dans le stockage des plugins.';
$strings['PensBehaviorPersist'] = 'Enregistre les métadonnées du paquet dans plugin_pens.';
$strings['PensBehaviorCallbacks'] = 'Envoie les callbacks PENS receipt et alert.';
$strings['PensBehaviorNoImport'] = 'N’importe pas encore automatiquement le paquet dans un cours.';
