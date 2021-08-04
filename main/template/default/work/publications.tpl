{% extends 'layout/layout_1_col.tpl'|get_template %}
{% import 'default/macro/macro.tpl' as display %}

{% block content %}
    <a
        href="javascript:void(0);"
        class="ajax"
        data-title="{{ intro_title | escape}}"
        data-content="{{ intro_content | escape }}"
    >
        <h4>{{ intro_title }}</h4>
    </a>

    {{ display.collapse('PendingStudentPublications', 'PendingStudentPublications' | get_lang, table, false, true) }}

    {{ display.collapse('StudentPublicationsSent', 'StudentPublicationsSent' | get_lang, table_with_results, false, false) }}
{% endblock %}
