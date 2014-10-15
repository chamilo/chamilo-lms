<?php

/* For licensing terms, see /license.txt */

/**
 * Strings to spanish L10n
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.tour
 */
$strings['plugin_title'] = 'Tour';
$strings['plugin_comment'] = 'Este plugin muestra a la gente cómo usar tu LMS';

/* Strings for settings */
$strings['show_tour'] = 'Mostrar el tour';

$showTourHelpLine01 = 'La configuración necesaria para mostrar los bloques de ayuda, en formato JSON, están localizadas en el archivo %splugin/tour/config/tour.json%s.';
$showTourHelpLine02 = 'Ver el archivo README para más información.';

$strings['show_tour_help'] = sprintf("$showTourHelpLine01 %s $showTourHelpLine02", "<strong>", "</strong>", "<br>");

$strings['theme'] = 'Tema';
$strings['theme_help'] = 'Elegir entre <i>nassim</i>, <i>nazanin</i>, <i>royal</i>. Vacío para usar el tema por defecto.';

/* Strings for plugin UI */
$strings['Skip'] = 'Saltar';
$strings['Next'] = 'Siguiente';
$strings['Prev'] = 'Anterior';
$strings['Done'] = 'Hecho';
$strings['StartButtonText'] = 'Empezar el recorrido';

/* String for the steps */
$strings['TheLogoStep'] = 'Bienvenido a <b>Chamilo LMS 1.9.x</b>.';
$strings['TheNavbarStep'] = 'Barra de herramientas con principales herramientas.';
$strings['TheRightPanelStep'] = 'Pane lateral.';
$strings['TheUserImageBlock'] = 'Tu foto de perfil.';
$strings['TheProfileBlock'] = 'Herramientas de perfil: <i>Bandeja de entrada</i>, <i>Nuevo mensaje</i>, <i>Invitaciones pendientes</i>, <i>Editar perfil</i>.';
$strings['TheHomePageStep'] = 'Esta es la página de inicio.';
