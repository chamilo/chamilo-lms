<?php
/**
 * @package chamilo.plugin.customfooter
 */

echo '<div class="well">';
if (!empty($plugin_info['settings']['customfooter_show_type'])) {
    echo "<h2>".$plugin_info['settings']['customfooter_show_type']."</h2>";
} else {
    echo "<h2>Custom Footer</h2>";
}

//Using get_lang inside a plugin
echo get_lang('CustomFooter');

echo '</div>';