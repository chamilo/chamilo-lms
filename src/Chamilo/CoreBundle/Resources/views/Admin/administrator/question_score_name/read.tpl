{% extends "@template_style/layout/layout_1_col.tpl" %}
{% block content %}
    {% import "@template_style/crud_macros/simple_crud.tpl" as actions %}
    {{ actions.read(item, links) }}
{% endblock %}
