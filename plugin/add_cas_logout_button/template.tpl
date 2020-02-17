{% if add_cas_logout_button.show_message %}
    <link href="{{ _p.web_plugin }}add_cas_logout_button/css.css" rel="stylesheet" type="text/css"> 
    <div class="well">
        {% if add_cas_logout_button.logout_image_url %}
            <img src="{{add_cas_logout_button.logout_image_url}}" class='cas_plugin_image'/>
        {% endif %}
        <h4>{{add_cas_logout_button.logout_label}}</h4>
        {% if add_cas_logout_button.logout_image_url %}
            <div class='cas_plugin_clear'>&nbsp;</div>
        {% endif %}
        <div class='cas_plugin_comm'>{{add_cas_logout_button.logout_comment}}</div>
        {{ add_cas_logout_button.form }}
        
    </div>
{% endif %}
