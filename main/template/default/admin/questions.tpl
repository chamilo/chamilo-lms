{% extends 'layout/layout_1_col.tpl'|get_template %}
{% import 'default/macro/macro.tpl' as display %}

{% block content %}
    {{ form }}

    {% for question in pagination %}
        {{ display.collapse(
            question.iid,
            '#' ~ question.courseCode ~'-'~  question.iid ~ ' - ' ~ question.question,
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
