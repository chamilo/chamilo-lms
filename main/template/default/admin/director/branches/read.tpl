{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <a href="{{ url(links.list_link) }}">
        List
    </a>
     {{ item.id }} {{ item.branchName }}
    <hr />


    <form action="{{ url(links.update_link, {id : item.id}) }}" method = "post" {{ form_enctype(form) }}>
        {{ form_widget(form) }}
    </form>


{% endblock %}
