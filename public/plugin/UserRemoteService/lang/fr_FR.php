<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = "Services distants pour l'utilisateur";
$strings['plugin_comment'] = "Ajoute à la barre de menu des liens spécifiques qui identifient l'utilisateur et qui s'ouvrent dans une iframe.";

$strings['salt'] = 'Sel';
$strings['salt_help'] = "Chaine de caractère secrète, utilisée pour générer le paramètre d'URL <em>hash</em>. Plus il est long et mieux c'est.\n<br/>Les services distants peuvent vérifier la validité de l'URL générée avec l'expression PHP suivante :\n<br/><code class=\"php\">password_verify(\$salt.\$userId, \$hash)</code>\n<br/>Où\n<br/><code>\$salt</code> est la valeur saisie ici,\n<br/><code>\$userId</code> est le numéro de l'utilisateur auquel fait référence le paramètre d'URL <em>username</em> et\n<br/><code>\$hash</code> représente la valeur du paramètre d'URL <em>hash</em>.";
$strings['hide_link_from_navigation_menu'] = 'Masquer les liens dans le menu';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Ajouter le service au menu';
$strings['DeleteServices'] = 'Retirer les services du menu';
$strings['ServicesToDelete'] = 'Services à retirer du menu';
$strings['ServiceTitle'] = 'Titre du service';
$strings['ServiceURL'] = 'Adresse web du service (URL)';
$strings['RedirectAccessURL'] = "Adresse à utiliser pour rediriger l'utilisateur au service (URL)";
$strings['Actions'] = 'Actions';
$strings['AddRemoteService'] = 'Ajouter un service distant';
$strings['CurrentServices'] = 'Services actuels';
$strings['DeleteService'] = 'Supprimer le service';
$strings['InvalidSecurityToken'] = 'Jeton de sécurité invalide.';
$strings['InvalidServiceTitle'] = 'Veuillez saisir un titre de service.';
$strings['InvalidServiceUrl'] = 'Veuillez saisir une URL HTTP ou HTTPS valide.';
$strings['MissingSaltWarning'] = "Configurez un salt avant d'exposer des liens de services distants. Le salt est requis pour générer des URLs utilisateur signées.";
$strings['NoServicesConfigured'] = "Aucun service distant n'a encore été configuré.";
$strings['OpenInIframe'] = 'Ouvrir dans une iframe';
$strings['OpenRedirect'] = "Ouvrir l'URL de redirection";
$strings['RemoteServicesDescription'] = 'Gérer les services externes qui reçoivent des URLs utilisateur signées depuis Chamilo. Seuls les utilisateurs authentifiés peuvent ouvrir ces liens.';
$strings['ServiceCreated'] = 'Le service distant a été créé.';
$strings['ServiceDeleted'] = 'Le service distant a été supprimé.';
$strings['ServiceManagement'] = 'Gestion des services distants';
$strings['ServiceUnavailable'] = "Ce service distant n'est pas disponible. Vérifiez que le plugin est activé, que le salt est configuré et que l'URL est valide.";
