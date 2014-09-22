{% extends "@template_style/layout/layout_1_col.tpl" %}
{% block content %}
    {% import "@template_style/crud_macros/course_crud.tpl" as actions %}
    <form action="{{ url('course_home.controller:editIconAction', {'course': course.code, 'id_session' : course_session.id, itemId : item.id }) }}" method = "post" {{ form_enctype(form) }}>
        {{ form_widget(form) }}
    </form>
{% endblock %}
