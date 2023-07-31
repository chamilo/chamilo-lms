{% extends 'layout/layout_1_col.tpl'|get_template %}
{% block content %}
    {{ form_filter }}
    {% import 'default/macro/macro.tpl' as display %}
    {{ display.careers_panel(data, _u.is_admin) }}
{% endblock %}
