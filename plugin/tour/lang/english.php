<?php

/* For licensing terms, see /license.txt */

/**
 * Strings to english L10n
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.tour
 */
$strings['plugin_title'] = 'Tour';
$strings['plugin_comment'] = 'This plugin shows people how to use your Chamilo LMS';

/* Strings for settings */
$strings['show_tour'] = 'Show the tour';

$showTourHelpLine01 = 'The necessary configuration to show the help blocks, in JSON format, is located in the %splugin/tour/config/tour.json%s file.';
$showTourHelpLine02 = 'See README file for more information.';

$strings['show_tour_help'] = sprintf("$showTourHelpLine01 %s $showTourHelpLine02", "<strong>", "</strong>", "<br>");

$strings['theme'] = 'Theme';
$strings['theme_help'] = 'Chose <i>nassim</i>, <i>nazanin</i>, <i>royal</i>. Empty to use the default theme.';

/* Strings for plugin UI */
$strings['Skip'] = 'Skip';
$strings['Next'] = 'Next';
$strings['Prev'] = 'Prev';
$strings['Done'] = 'Done';
$strings['StartButtonText'] = 'Start the tour';

/* String for the steps */
$strings['TheLogoStep'] = 'Welcome to <b>Chamilo LMS 1.9.x</b>';
$strings['TheNavbarStep'] = 'Menu bar with main tools.';
$strings['TheRightPanelStep'] = 'Sidebar panel.';
$strings['TheUserImageBlock'] = 'Your profile photo.';
$strings['TheProfileBlock'] = 'Your profile tools: <i>Inbox</i>, <i>Compose</i>, <i>Pending invitations</i>, <i>Edit</i>.';
$strings['TheHomePageStep'] = 'This is the initial homepage.';
