<div class="well">
<!-- AddThis Button BEGIN -->
{% if follow_buttons.message is not empty %}
    <h3>{{ follow_buttons.message }}</h3>
{% endif %}
    
<div class="addthis_toolbox {{ follow_buttons.plugin_info.icon_class}} {{ follow_buttons.plugin_info.position}} ">
    {% if follow_buttons.facebook is not empty %}
        <a class="addthis_button_facebook_follow" addthis:userid="{{ follow_buttons.facebook }}"></a>
    {% endif %}
    {% if follow_buttons.twitter is not empty %}
        <a class="addthis_button_twitter_follow" addthis:userid="{{ follow_buttons.twitter }}"></a>
    {% endif %}
    {% if follow_buttons.linkedin is not empty %}
        <a class="addthis_button_linkedin_follow" addthis:userid="{{ follow_buttons.linkedin }}"></a>
    {% endif %}
    {% if follow_buttons.googleplus is not empty %}
        <a class="addthis_button_google_follow" addthis:userid="{{ follow_buttons.googleplus }}"></a>
    {% endif %}
    {% if follow_buttons.youtube is not empty %}
        <a class="addthis_button_youtube_follow" addthis:userid="{{ follow_buttons.youtube }}"></a>
    {% endif %}
    {% if follow_buttons.flickr is not empty %}
        <a class="addthis_button_flickr_follow" addthis:userid="{{ follow_buttons.flickr }}"></a>
    {% endif %}
    {% if follow_buttons.vimeo is not empty %}
        <a class="addthis_button_vimeo_follow" addthis:userid="{{ follow_buttons.vimeo }}"></a>
    {% endif %}
    {% if follow_buttons.rss is not empty %}
        <a class="addthis_button_rss_follow" addthis:url="{{ follow_buttons.rss }}"></a>
    {% endif %}
</div>
<script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pubid=ra-4f69d7995360186c"></script>
<!-- AddThis Button END -->
</div>