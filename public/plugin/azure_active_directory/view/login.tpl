<div class="row">
    <div class="col-sm-4 col-sm-offset-4">
        {{ login_language_form }}

        {{ login_form }}

        {% if "allow_lostpassword"|api_get_setting == 'true' or "allow_registration"|api_get_setting == 'true' %}
            <ul class="nav nav-pills nav-stacked">
                {% if "allow_registration"|api_get_setting != 'false' %}
                    <li><a href="{{ _p.web_main }}auth/inscription.php">{{ 'SignUp'|get_lang }}</a></li>
                {% endif %}

                {% if "allow_lostpassword"|api_get_setting == 'true' %}
                    <li><a href="{{ _p.web_main }}auth/lostPassword.php">{{ 'LostPassword'|get_lang }}</a></li>
                {% endif %}
            </ul>
        {% endif %}
    </div>
</div>
