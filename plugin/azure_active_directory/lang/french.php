<?php
/* For licensing terms, see /license.txt */
/**
 * Strings to French L10n.
 *
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 *
 * @package chamilo.plugin.azure_active_directory
 */
$strings['plugin_title'] = 'Azure Active Directory';
$strings['plugin_comment'] = 'Permet l\'authentification des utilisateurs via Azure Active Directory de Microsoft';

$strings['enable'] = 'Activer';
$strings['app_id'] = 'ID de l\'application';
$strings['app_id_help'] = 'Introduisez l\'ID de l\'application assigné à votre app par le portail d\'Azure, p.ex. 580e250c-8f26-49d0-bee8-1c078add1609';
$strings['app_secret'] = 'Clef secrète de l\'application';
$strings['force_logout'] = 'Bouton de logout';
$strings['force_logout_help'] = 'Affiche un bouton pour se délogger d\'Azure.';
$strings['block_name'] = 'Nom du bloc';
$strings['management_login_enable'] = 'Login de gestion';
$strings['management_login_enable_help'] = 'Désactiver le login de Chamilo et permettre une page de login alternative pour les utilisateurs administrateurs.<br>'
    .'Vous devez, pour cela, copier le fichier <code>/plugin/azure_active_directory/layout/login_form.tpl</code> dans le répertoire <code>/main/template/overrides/layout/</code>.';
$strings['management_login_name'] = 'Nom du login de gestion';
$strings['management_login_name_help'] = 'Le nom par défaut est "Login de gestion".';
$strings['OrganisationEmail'] = 'E-mail professionnel';
$strings['AzureId'] = 'ID Azure (mailNickname)';
$strings['ManagementLogin'] = 'Login de gestion';
$strings['InvalidId'] = 'Échec du login - nom d\'utilisateur ou mot de passe incorrect. Errocode: AZMNF';
$strings['provisioning'] = 'Création automatisée';
$strings['provisioning_help'] = 'Créer les utilisateurs automatiquement (en tant qu\'apprenants) depuis Azure s\'ils n\'existent pas encore dans Chamilo.';
$strings['group_id_admin'] = 'ID du groupe administrateur';
$strings['group_id_admin_help'] = 'L\'id du groupe peut être trouvé dans les détails du groupe, et ressemble à ceci : ae134eef-cbd4-4a32-ba99-49898a1314b6. Si ce champ est laissé vide, aucun utilisateur ne sera créé en tant qu\'administrateur.';
$strings['group_id_session_admin'] = 'ID du groupe administrateur de sessions';
$strings['group_id_session_admin_help'] = 'The group ID for session admins. Si ce champ est laissé vide, aucun utilisateur ne sera créé en tant qu\'administrateur de sessions.';
$strings['group_id_teacher'] = 'ID du groupe enseignant';
$strings['group_id_teacher_help'] = 'The group ID for teachers. Si ce champ est laissé vide, aucun utilisateur ne sera créé en tant qu\'enseignant.';
