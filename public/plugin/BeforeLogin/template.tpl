{% if beforelogin.content is defined and beforelogin.content %}
    {{ beforelogin.content|raw }}
{% elseif BeforeLogin.content is defined and BeforeLogin.content %}
    {{ BeforeLogin.content|raw }}
{% elseif beforelogin.form_option1 is defined and beforelogin.form_option1 %}
    <div class="before-login-plugin">
        {{ beforelogin.form_option1|raw }}
        {% if beforelogin.form_option2 is defined and beforelogin.form_option2 %}
            {{ beforelogin.form_option2|raw }}
        {% endif %}
    </div>
{% elseif BeforeLogin.form_option1 is defined and BeforeLogin.form_option1 %}
    <div class="before-login-plugin">
        {{ BeforeLogin.form_option1|raw }}
        {% if BeforeLogin.form_option2 is defined and BeforeLogin.form_option2 %}
            {{ BeforeLogin.form_option2|raw }}
        {% endif %}
    </div>
{% endif %}
