<?php

$plugin = RssPlugin::create();

$rss = $plugin->get_rss();

$title = $plugin->get_block_title();
$title = $title ? "<h4>$title</h4>" : '';

$css = $plugin->get_css();
$css = $css ? "<style type=\"text/css\">$css</style>" : '';

$bullet = api_get_path(WEB_PLUGIN_PATH) . '/rss/resources/arrow-bullet.png';

if (empty($rss))
{
    echo get_lang('no_rss');
    return;
}



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
    $title
    <div id="news" class="" style="min-height:300px;"></div>
</div>
EOT;
