{% if add_cas_login_button.show_message %}
    <link href="{{ _p.web_plugin }}add_cas_login_button/css.css" rel="stylesheet" type="text/css">
    <div class="well">
        {% if add_cas_login_button.url_label %}
            <img src="{{ add_cas_login_button.url_label }}" class='cas_plugin_image'/>
        {% endif %}
        <h4>{{ add_cas_login_button.button_label }}</h4>
        {% if add_cas_login_button.url_label %}
            <div class='cas_plugin_clear'>&nbsp;</div>
        {% endif %}
        <div class='cas_plugin_comm'>{{ add_cas_login_button.comm_label }}</div>
        {% if add_cas_login_button.cas_activated %}
            {% if add_cas_login_button.cas_configured %}
                <button class="btn" onclick="javascript:self.location.href='main/auth/cas/logincas.php'">{{"LoginEnter"|get_lang}}</button>    
            {% else %}
                CAS isn't configured. Go to Admin > Configuration > CAS.<br/>
            {% endif %}
        {% else %}
            CAS isn't activated. Go to Admin > Configuration > CAS.<br/>
        {% endif %}
    </div>
{% endif %}
