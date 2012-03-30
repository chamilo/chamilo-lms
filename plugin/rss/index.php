<?php

$bullet = api_get_path(WEB_PLUGIN_PATH) . '/rss/resources/arrow-bullet.png';

$settings = $plugin_info['settings'];
$rss      = isset($plugin_info['rss']) ? $plugin_info['rss'] : '';
$title    = isset($plugin_info['rss_title']) ? $plugin_info['rss_title'] : 'Rss';
$title = $title ? "<h4>$title</h4>" : '';

if (empty($rss))
{
    echo get_lang('no_rss');
}

$css = array();
$css[] = file_get_contents(dirname(__FILE__) . '/resources/rss.css');
$css[] = file_get_contents(dirname(__FILE__) . '/resources/color.css');
$css = implode($css);

echo<<<EOT
<div class="well sidebar-nav rss">

    <style type="text/css">

        $css
        .gfg-listentry-highlight{
            background-image: url('$bullet');
        }

    </style>

    <script type="text/javascript" src="http://www.google.com/jsapi"></script>
    <script src="http://www.google.com/uds/solutions/dynamicfeed/gfdynamicfeedcontrol.js" type="text/javascript"></script>
    <script type="text/javascript">
        google.load('feeds', '1');

        function OnLoad() {
            var feeds = [
                {
                    url: '$rss'
                }
            ];

            var options = {
                stacked : true,
                numResults : 5,
                horizontal : false,
                title : 'Nouvelles!'
            };

            new GFdynamicFeedControl(feeds, 'news', options);        
        }
        google.setOnLoadCallback(OnLoad);
    </script>
    <h4>$title</h4>
    <div id="news" class="" style="min-height:300px;"></div>
</div>
EOT;
