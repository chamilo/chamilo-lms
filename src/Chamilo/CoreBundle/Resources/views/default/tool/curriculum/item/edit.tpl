{% extends "@template_style/layout/layout_1_col.tpl" %}
{% block content %}
    {% import "@template_style/crud_macros/course_crud.tpl" as actions %}
    {{ actions.edit(form, links, course, course_session) }}
{% endblock %}
