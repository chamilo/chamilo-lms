<?php

$strings['plugin_title'] = 'Menu extra depuis un service web';
$strings['plugin_comment'] = "Ajouter un menu flottant fourni par un service web sur base de l'e-mail utilisateur";
$strings['api_menu_url'] = 'URL du menu API';
$strings['api_menu_url_help'] = 'Endpoint de la plateforme API renvoyant les éléments de menu au format JSON. Les paramètres de requête email, userId, locale et mobile sont ajoutés automatiquement.';
$strings['authentication_url'] = "URL d'authentication";
$strings['authentication_url_help'] = 'Endpoint optionnel utilisé pour obtenir un jeton bearer.';
$strings['authentication_email'] = "Email pour l'authentification.";
$strings['authentication_password'] = "Mot de passe pour l'authentification";
$strings['api_bearer_token'] = 'Jeton bearer statique';
$strings['api_bearer_token_help'] = "Optionnel. Si défini, ce jeton est utilisé au lieu d'appeler l'URL d'authentification.";
$strings['normal_menu_url'] = 'URL pour le web service du menu standard';
$strings['normal_menu_url_help'] = "Solution de repli rétrocompatible lorsque l'URL du menu API est vide.";
$strings['mobile_menu_url'] = 'URL pour le web service du menu mobile';
$strings['mobile_menu_url_help'] = "Solution de repli rétrocompatible lorsque l'URL du menu API est vide.";
$strings['menu_request_mode'] = 'Mode de requête du menu';
$strings['api_platform_query'] = 'Paramètres de requête de la plateforme API';
$strings['legacy_email_path'] = "Chemin legacy pour l'email";
$strings['session_timeout'] = 'Durée de vie du token, en secondes';
$strings['session_timeout_help'] = "Évite de recharger le menu de manière répétée. Si aucun temps n'est défini, le plugin utilisera la valeur de 86400 secondes (24h).";
$strings['cache_ttl'] = 'TTL du cache du menu en secondes';
$strings['cache_ttl_help'] = 'Valeur par défaut : 300 secondes. Mettre 0 pour désactiver le cache du menu.';
$strings['request_timeout'] = "Délai d'expiration de la requête HTTP en secondes";
$strings['request_timeout_help'] = 'Valeur par défaut : 3 secondes.';
$strings['MenuTitle'] = 'Menu supplémentaire';
$strings['list_css_imports'] = "Liste des URLs CSS à importer, séparées par des points-virgule (';')";
$strings['list_fonts_imports'] = "Liste des fontes de caractères à importer, séparées par des points-virgule (';')";
