<?php
/**
 * Controller for example date plugin
 * @package chamilo.plugin.date
 */


if (!empty($plugin_info['settings']['hello_world_show_type'])) {
    echo "<h2>".$plugin_info['settings']['hello_world_show_type']."</h2>";
} else {
    echo "<h2>Hello world</h2>";  
}