{%
    extends hide_header == true
    ? 'layout/blank.tpl'|get_template
    : 'layout/layout_1_col.tpl'|get_template
%}

{% block content %}

{{ inscription_header }}
{{ inscription_content }}
{{ form }}
{{ text_after_registration }}

{% endblock %}
