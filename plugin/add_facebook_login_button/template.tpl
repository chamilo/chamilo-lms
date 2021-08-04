{% if add_facebook_login_button.show_message %}
    <hr>
    {% if add_facebook_login_button.facebook_button_url %}
        <a href="{{ add_facebook_login_button.facebook_href_link }}" title="{{ 'FacebookMainActivateTitle'|get_lang }}">
            <img src="{{ add_facebook_login_button.facebook_button_url }}"
                 alt="{{ 'FacebookMainActivateTitle'|get_lang }}" style="display: block; margin: 0 auto;">
        </a>
    {% else %}
        <a href="{{ add_facebook_login_button.facebook_href_link }}" class="btn btn-primary btn-block">
            <span class="fa fa-facebook fa-fw" aria-hidden="true"></span> {{ 'FacebookMainActivateTitle'|get_lang }}
        </a>
    {% endif %}
    <hr style="margin-bottom: 10px;">
{% endif %}
