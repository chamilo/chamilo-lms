{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    {% import app.template_style ~ "/crud_macros/course_crud.tpl" as actions %}
    {{ actions.edit(form, links, {'courseCode' : app.request.get('courseCode'))} }}
{% endblock %}
