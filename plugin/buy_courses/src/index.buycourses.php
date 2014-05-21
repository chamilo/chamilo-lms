<?php
/**
 * @package chamilo.plugin.themeselect
 */

$plugin = Buy_CoursesPlugin::create();
$guess_enable = $plugin->get('unregistered_users_enable');

if ($guess_enable == "true" || isset($_SESSION['_user'])) {
    $title = "Listado de cursos en venta";

    echo '<div class="well sidebar-nav static">';
    echo '<h4>' . $title . '</h4>';
    echo '<ul class="nav nav-list">';
    echo '<li>';
    echo '<a href="src/list.php">Comprar cursos</a>';
    echo '</li>';
    if (api_is_platform_admin()) {
        echo '<li>';
        echo '<a href="src/configuration.php">' . utf8_encode($plugin->get_lang('bc_confi_index')) . '</a>';
        echo '</li>';
        echo '<li>';
        echo '<a href="src/paymentsetup.php">' . utf8_encode($plugin->get_lang('bc_pagos_index')) . '</a>';
        echo '</li>';
        echo '<li>';
        echo '<a href="src/pending_orders.php">' . utf8_encode($plugin->get_lang('bc_pending')) . '</a>';
        echo '</li>';
    }
    echo '</ul>';
    echo '</div>';
}
 