<?php
/**
 * @package chamilo.plugin.hello_world
 */

// See also the share_user_info plugin 

echo '<div class="well">';
if (!empty($plugin_info['settings']['hello_world_show_type'])) {
    echo "<h2>".$plugin_info['settings']['hello_world_show_type']."</h2>";
} else {
    echo "<h2>Hello world</h2>";  
}

//Using get_lang inside a plugin
echo get_lang('HelloPlugin');

echo '</div>';