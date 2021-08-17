{% extends 'layout/layout_1_col.tpl'|get_template %}
{% import 'default/macro/macro.tpl' as display %}

{% block content %}
    {{ toolbar }}
    {{ form }}
{#    {% for question in pagination %}#}
    {% for i in start..end %}
        {% if pagination[i] is defined %}
            {% set question = pagination[i] %}
            {{ display.collapse(
                question.iid,
                '#' ~ question.courseCode ~'-'~  question.iid ~ ' - ' ~ question.question,
                question.questionData,
                false,
                false
                )
            }}
        {% endif %}
    {% endfor %}

    {% if question_count > pagination_length %}
        {{ pagination }}
    {% endif %}
{% endblock %}
