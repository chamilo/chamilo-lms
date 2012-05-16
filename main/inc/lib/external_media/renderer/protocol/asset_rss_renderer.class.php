<?php

/**
 * Rss renderer. Display RSS thanks to Google feed control.
 * 
 * @see http://www.google.com/uds/solutions/dynamicfeed/reference.html
 * @see http://code.google.com/apis/ajax/playground/#dynamic_feed_control_-_vertical
 * 
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class AssetRssRenderer extends AssetRenderer
{

    /**
     *
     * @param HttpResource $asset 
     */
    public function render($asset)
    {
        if (!$asset->is_rss())
        {
            return;
        }

        $url = $asset->url();
        $title = $asset->title();
        $id = 'a' . md5($url);

        $embed = <<<EOT
        <style type="text/css">
            .gfg-root {
                border: none;
                font-family: inherit;
            }
        </style>
        <script type="text/javascript" src="http://www.google.com/jsapi"></script>
        <script src="http://www.google.com/uds/solutions/dynamicfeed/gfdynamicfeedcontrol.js" type="text/javascript"></script> 
        <script type="text/javascript">
        
            function init()
            {
                if (typeof this.has_run == 'undefined' )
                {
                    this.has_run = true;
                }
                else
                {
                    return;
                }
                var head = document.getElementsByTagName('head')[0];
        
                var element = document.createElement('link');
                element.type = 'text/css';
                element.rel = 'stylesheet';
                element.href = 'http://www.google.com/uds/solutions/dynamicfeed/gfdynamicfeedcontrol.css';
                head.appendChild(element);
            }

            function load_$id() {
                var feeds = [
                    {
                    title: ' ',
                    url: '$url'
                    }
                ];

                var options = {
                    stacked : false,
                    horizontal : false,
                    title : '',
                    numResults : 10
                };

                new GFdynamicFeedControl(feeds, '$id', options);
                document.getElementById('content').style.width = "500px";
            }
        
        
            init();
            google.load('feeds', '1');
            google.setOnLoadCallback(load_$id);
       </script>        
       <div id="$id" style="min-height:271px;">Loading...</div>
EOT;


        $result = array();
        $result[self::EMBED_SNIPPET] = $embed;
        $result[self::TITLE] = $title;
        return $result;
    }

}