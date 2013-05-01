{% extends app.template_style ~ "/layout/layout_2_col.tpl" %}
{% block left_column %}
    <div class="well sidebar-nav">
        {{ tree }}
    </div>
{% endblock %}
{% block right_column %}
    content
{% endblock %}