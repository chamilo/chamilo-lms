{% block compressed_root %}
{% spaceless %}
    {{ block('root') }}
{% endspaceless %}
{% endblock %}

{% block root %}
    {{ block('breadcrumb') }}
{% endblock %}

{% block breadcrumb %}
    {% if item %}
        <ul class="breadcrumb">
            {{ block('item') }}
        </ul>
    {% endif %}
{% endblock %}

{% block item %}
    {% for item in item.children %}
        {% if matcher.isCurrent(item) %}
            <li class="active">{{ block('label') }}</li>
        {% else %}
            {% if item.uri %}
                <li><a href="{{ item.uri }}">{{ block('label') }}</a> <span class="divider">/</span></li>
            {% else %}
                <li>{{ block('label') }}</li>
            {% endif %}
        {% endif %}
    {% endfor %}
{% endblock %}
{% block label %}
    {{ item.label }}
{% endblock %}
