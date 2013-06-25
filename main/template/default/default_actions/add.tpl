{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    {{ links | var_dump }}
    <form action="{{ links.add_link }}" method="post" {{ form_enctype(form) }}>
        {{ form_widget(form) }}
    </form>
{% endblock %}
