{% if ext_auth_chamilo_logout_button_behaviour.show_message %}
    <script type="text/javascript">
        $("#logout_button").attr('href', '{{ ext_auth_chamilo_logout_button_behaviour.link_url }}' );
        $("#logout_button").attr('title', '{{ ext_auth_chamilo_logout_button_behaviour.link_infobulle }}' );
        {% if ext_auth_chamilo_logout_button_behaviour.alert_onoff %}
            $("#logout_button").attr('onclick', 'alert("{{ ext_auth_chamilo_logout_button_behaviour.alert_text }}")' );
        {% endif %}            
        {% if ext_auth_chamilo_logout_button_behaviour.link_image %}
            $("#logout_button img").attr('src', '{{ "exit_na.png"|icon(22) }}');
        {% endif %}    
    </script>
{% endif %}
