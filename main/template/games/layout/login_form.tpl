{% if login_form %}
<div id="login_block" class="panel-form">

    {{ login_language_form }}

    {% if plugin_login_top is not null %}
    <div id="plugin_login_top">
        {{ plugin_login_top }}
    </div>
    {% endif %}

    {{ login_failed }}

    {{ login_form }}


    {% if "allow_lostpassword" | get_setting == 'true' and "allow_registration" | get_setting == 'true' %}
    <div class="lost-password">
        {% if "allow_lostpassword" | get_setting == 'true' %}
        <a class="btn btn-lost" href="main/auth/lostPassword.php"> {{ 'LostPassword' | get_lang }} </a>
        {% endif %}
    </div>
    <hr class="separator">
    <div class="registration">
        {% if "allow_registration" | get_setting != 'false' %}
        <a class="btn btn-press ajax" href="main/auth/inscription.php?hide_headers=1&width=650"> {{ 'SignUp' | get_lang }} </a>
        {% endif %}
    </div>
    {% endif %}

    {% if plugin_login_bottom is not null %}
    <div id="plugin_login_bottom">
        {{ plugin_login_bottom }}
    </div>
    {% endif %}

</div>
{% endif %}
