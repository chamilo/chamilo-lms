<?php
//only show the block information if the admin is logged in
if (api_is_platform_admin()) {
    echo '<div style="color:black;height:50px;width:200px;background-color:#FFE378">';
    //We can have access to the current block and the block information with the variable $plugin_info (see your plugin.php)
    echo $plugin_info['current_block'];
    echo '</div>';    
}