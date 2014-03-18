{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    {% import app.template_style ~ "/crud_macros/course_crud.tpl" as actions %}
    <form action="{{ url('course_home.controller:addIconAction', {'course': course.code, 'id_session' : course_session.id, itemName : item.name }) }}" method = "post" {{ form_enctype(form) }}>
        {{ form_widget(form) }}
    </form>
{% endblock %}
