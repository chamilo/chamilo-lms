<div class="well">
<!-- AddThis Button BEGIN -->
{if !empty($follow_buttons.message)}
    <h3>{$follow_buttons.message}</h3>
{/if}
<div class="addthis_toolbox addthis_32x32_style addthis_default_style">
    {if !empty($follow_buttons.facebook)}
        <a class="addthis_button_facebook_follow" addthis:userid="{$follow_buttons.facebook}"></a>
    {/if}
    {if !empty($follow_buttons.twitter)}
        <a class="addthis_button_twitter_follow" addthis:userid="{$follow_buttons.twitter}"></a>
    {/if}
    {if !empty($follow_buttons.linkedin)}
        <a class="addthis_button_linkedin_follow" addthis:userid="{$follow_buttons.linkedin}"></a>
    {/if}
    {if !empty($follow_buttons.googleplus)}
        <a class="addthis_button_google_follow" addthis:userid="{$follow_buttons.googleplus}"></a>
    {/if}
    {if !empty($follow_buttons.youtube)}
        <a class="addthis_button_youtube_follow" addthis:userid="{$follow_buttons.youtube}"></a>
    {/if}
    {if !empty($follow_buttons.flickr)}
        <a class="addthis_button_flickr_follow" addthis:userid="{$follow_buttons.flickr}"></a>
    {/if}
    {if !empty($follow_buttons.vimeo)}
        <a class="addthis_button_vimeo_follow" addthis:userid="{$follow_buttons.vimeo}"></a>
    {/if}
    {if !empty($follow_buttons.rss)}
        <a class="addthis_button_rss_follow" addthis:url="{$follow_buttons.rss}"></a>
    {/if}    
</div>
<script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pubid=ra-4f69d7995360186c"></script>
<!-- AddThis Button END -->
</div>