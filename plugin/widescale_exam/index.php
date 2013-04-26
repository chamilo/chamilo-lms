<?php

$url = api_get_path(WEB_PLUGIN_PATH).'widescale_exam/user_list.php';

if (api_is_drh() || api_is_platform_admin()) {
    echo '<div id="course_block" class="well sidebar-nav">
            <h4>Reporte</h4>
                <ul class="nav nav-list">
                    <a href="'.$url.'">Lista de usuarios</a>
                </ul>';

    echo '</div>';
}