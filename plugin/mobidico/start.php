<?php

require_once __DIR__ . '/../../main/inc/global.inc.php';

$course_plugin = 'mobidico'; //needed in order to load the plugin lang variables


$plugin = Mobidico::create();

if ($plugin->get('tool_enable') !== 'true') {
    api_not_allowed(true);
}


$url = $plugin->get('mobidico_url');
$key = $plugin->get('api_key');

$tool_name = get_lang('Videoconference');

$htmlHeadXtra[] = '<script>
$(document).ready(function() {
    var params = {
        "chamiloid" : "2",
        //"chamiloid" : "'.api_get_user_id().'",
        "API_KEY" : "'.$key.'"
    };
    
    $.ajax({
        url: "'.$url.'/app/desktop/php/authenticate.php",
        type: "POST",
        data: params,
        success: function(data) {
            var parsed = jQuery.parseJSON(data);
            if (parsed.STATUS == "OK") {
                if (parsed.SESSION != "") {
                    var url = "'.$url.'/app/index.html?session="+parsed.SESSION;
                    var win = window.open(url, "_blank");
                    win.focus();
                }
            } else {
                console.log(parsed.ERROR);
            }            
        }
    });
});
</script>';


$tpl = new Template('Mobidico');

$content = '';
$tpl->assign('content', $content);
$tpl->display_one_col_template();
