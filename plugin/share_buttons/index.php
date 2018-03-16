<?php
/**
 * Controller for example date plugin.
 *
 * @package chamilo.plugin.share_buttons
 */
echo '
<div class="well well-sm">
    <!-- AddThis Button BEGIN -->
    <div class="addthis_toolbox addthis_default_style '.$plugin_info['icon_class'].'">
        <a class="addthis_button_preferred_1"></a>
        <a class="addthis_button_preferred_2"></a>
        <a class="addthis_button_preferred_3"></a>
        <a class="addthis_button_preferred_4"></a>
        <a class="addthis_button_compact"></a>
        <a class="addthis_counter addthis_bubble_style"></a>
    </div>
<!-- AddThis Button END -->
</div>
<script type="text/javascript" src="//s7.addthis.com/js/250/addthis_widget.js"></script>
';
