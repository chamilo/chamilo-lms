{# Load the template basis from the default template #}
{% extends app.template_style ~ "/../default/layout/main_header.tpl" %}

{% block main_div_container %}
{% if app.full_width == 1 %}
    <div id="main" class="container-fluid" data-role="page">
{% else %}
    <div id="main" class="container" data-role="page">
{% endif %}
{% endblock main_div_container %}

