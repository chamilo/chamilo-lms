{% extends 'layout/layout_1_col.tpl'|get_template %}
{% import 'macro/macro.tpl'|get_template as display %}

{% block content %}
    {{ form }}

    {% for question in pagination %}
        {{ question.question }}
        {{ display.collapse(
            question.iid,
            '#' ~ question.iid ~ '-' ~ question.question,
            question.questionData,
            false,
            false
            )
        }}
    {% endfor %}

    {% if question_count > pagination_length %}
        {{ pagination }}
    {% endif %}
{% endblock %}
