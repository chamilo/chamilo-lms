
{% if add_shibboleth_login_button.show_message %}
    <link href="{{ _p.web_plugin }}add_shibboleth_login_button/css.css" rel="stylesheet" type="text/css"> 
    <div class="well">
        {% if add_shibboleth_login_button.url_label %}
            <img src="{{ add_shibboleth_login_button.url_label }}" class='shibboleth_plugin_image'/>
        {% endif %}
        <h4>{{ add_shibboleth_login_button.button_label }}</h4>
        {% if add_shibboleth_login_button.url_label %}
            <div class='shibboleth_plugin_clear'>&nbsp;</div>
        {% endif %}
        <div class='shibboleth_plugin_comm'>{{ add_shibboleth_login_button.comm_label }}</div>
        <button class="btn btn-default" onclick="javascript:self.location.href='main/auth/shibboleth/login.php'">{{ "LoginEnter"|get_lang }}</button>
    </div>
{% endif %}
