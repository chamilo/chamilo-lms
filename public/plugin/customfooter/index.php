<?php
/**
 * @package chamilo.plugin.customfooter
 */
if (isset($plugin_info['current_region'])) {
    switch ($plugin_info['current_region']) {
        case 'footer_left':
            echo $plugin_info['settings']['customfooter_footer_left'];
            break;
        case 'footer_right':
            echo $plugin_info['settings']['customfooter_footer_right'];
            break;
    }
}
