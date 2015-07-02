{% extends template ~ "/layout/layout_1_col.tpl" %}

{% block content %}
<div class="row">
    <div class="col-md-9">
        {{ social_right_content}}
    </div>
    <div class="col-md-3">
        <img src="{{ avatar }}" class="img-responsive img-circle">
    </div>
</div>
{% endblock %}