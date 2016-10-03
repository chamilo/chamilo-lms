{%
    extends hide_header == true
    ? template ~ "/layout/blank.tpl"
    : template ~ "/layout/layout_1_col.tpl"
%}

{% block content %}
<div class="page-inscription">
    {{ inscription_header }}
    {{ inscription_content }}
    {{ form }}
    {{ text_after_registration }}
</div>

{% endblock %}
