{% extends 'layout/layout_1_col.tpl'|get_template %}

{% block content %}
    {{ introduction_message }}

    <h3>{{ 'PendingStudentPublications' | get_lang }}</h3>

    {{ table }}

    <h3>{{ 'StudentPublicationsSent' | get_lang }}</h3>

    {{ table_with_results }}
{% endblock %}
