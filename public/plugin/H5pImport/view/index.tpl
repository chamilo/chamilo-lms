{% if is_allowed_to_edit %}
    {% if form is defined %}
        {{ form }}
    {% endif %}
{% endif %}
{{ table }}
