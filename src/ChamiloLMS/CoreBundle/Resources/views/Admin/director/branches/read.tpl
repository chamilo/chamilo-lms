{% extends "@template_style/layout/layout_1_col.tpl" %}
{% block content %}
    <a href="{{ url(links.list_link) }}">
        {{ 'List' |trans }}
    </a>
    <div class="page-header">
        <h3>
         {{ 'Branch' |trans }} #{{ item.id }} - {{ item.branchName }}
        </h3>
    </div>

    <h4>
        {{ 'Create committee' |trans }}
    </h4>

    <form action="{{ url(links.update_link, {id : item.id}) }}" method = "post" {{ form_enctype(form) }}>
        {{ form_widget(form) }}
    </form>


{% endblock %}
