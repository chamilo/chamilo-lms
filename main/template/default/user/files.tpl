{% extends app.template_style ~ "/layout/layout_2_col.tpl" %}

{% block left_column %}
    <div class="well social-background-content">
        <img src="{{ user.avatar }}"/>
    </div>

{% endblock %}

{% block right_column %}
    <div class="well_border">
        <h3>{{ 'MyFiles' | trans }}</h3>
    </div>
    {{ editor }}

{% endblock %}
