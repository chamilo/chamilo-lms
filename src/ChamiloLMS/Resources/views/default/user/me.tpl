{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}

{% block content %}

    <img src="{{ user.avatar }}"/>

    <h3>{{ user.complete_name }} @{{ user.username }} </h3>

    <div class="tabbable tabs-left">
        <ul class="nav nav-tabs">
            <a href="#lA" data-toggle="tab">Section 1</a>
            <a href="#lB" data-toggle="tab">Section 2</a>
            <a href="#lC" data-toggle="tab">Section 3</a>
        </ul>
        <div class="tab-content">
            <div class="tab-pane" id="lA">
                <p>I'm in Section A.</p>
            </div>
            <div class="tab-pane" id="lB">
                <p>I'm in Section A.</p>
            </div>
            <div class="tab-pane" id="lC">
                <p>I'm in Section A.</p>
            </div>
        </div>
    </div>



{% endblock %}
