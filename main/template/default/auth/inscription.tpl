{%
    extends hide_header == true
    ? template ~ "/layout/blank.tpl"
    : template ~ "/layout/layout_1_col.html.twig"
%}

{% block content %}

{{ inscription_header }}
{{ inscription_content }}
{{ form }}
{{ text_after_registration }}

{% endblock %}
