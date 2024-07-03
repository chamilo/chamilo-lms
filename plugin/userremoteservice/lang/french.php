<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = "Services distants pour l'utilisateur";
$strings['plugin_comment'] =
    "Ajoute à la barre de menu des liens spécifiques qui identifient l'utilisateur et qui s'ouvrent dans une iframe.";

/* Strings for settings */
$strings['salt'] = "Sel";
$strings['salt_help'] =
'Chaine de caractère secrète, utilisée pour générer le paramètre d\'URL <em>hash</em>. Plus il est long et mieux c\'est.
<br/>Les services distants peuvent vérifier la validité de l\'URL générée avec l\'expression PHP suivante :
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Où
<br/><code>$salt</code> est la valeur saisie ici,
<br/><code>$userId</code> est le numéro de l\'utilisateur auquel fait référence le paramètre d\'URL <em>username</em> et
<br/><code>$hash</code> représente la valeur du paramètre d\'URL <em>hash</em>.';
$strings['hide_link_from_navigation_menu'] = 'Masquer les liens dans le menu';

// Please keep alphabetically sorted
$strings['CreateService'] = "Ajouter le service au menu";
$strings['DeleteServices'] = "Retirer les services du menu";
$strings['ServicesToDelete'] = "Services à retirer du menu";
$strings['ServiceTitle'] = "Titre du service";
$strings['ServiceURL'] = "Adresse web du service (URL)";
$strings['RedirectAccessURL'] = "Adresse à utiliser pour rediriger l'utilisateur au service (URL)";
