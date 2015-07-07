<h2 class="page-header">{{ 'SessionRequirements'|get_lang }}</h2>

{% set finishedSessions = 0 %}

{% for item in data %}
    {% if item.status %}
        {% set finishedSessions = finishedSessions + 1 %}

        <p class="bg-success" style="padding: 10px;">
            <span class="pull-right">
                <i class="fa fa-check"></i>
            </span>
            {{ item.session.name }}
        </p>
    {% else %}
        <p class="bg-danger" style="padding: 10px;">
            <span class="pull-right">
                <i class="fa fa-exclamation-triangle"></i>
            </span>
            {{ item.session.name }}
        </p>
    {% endif %}
{% endfor %}

<hr>
<p>
    {% if finishedSessions == data|length %}
        {{ subscribe_button }}
    {% else %}
        {{ 'YouNeedCompleteAllSessions'|get_lang }}
    {% endif %}
</p>
