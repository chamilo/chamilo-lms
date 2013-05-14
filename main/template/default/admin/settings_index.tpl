{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
<script>
$(function() {
    //$("#settings").tabs();
});
</script>

<div id="settings">
    <!--
    <ul>
        <li><a href="#tabs-1">Users</a></li>
        <li><a href="#tabs-2">Courses</a></li>
        <li><a href="#tabs-3">Platform</a></li>
        <li><a href="#tabs-4">Aenean lacinia</a></li>
        <li><a href="#tabs-5">Aenean lacinia</a></li>
        <li><a href="#tabs-6">Aenean lacinia</a></li>
        <li><a href="#tabs-7">Aenean lacinia</a></li>
        <li><a href="#tabs-8">Aenean lacinia</a></li>
    </ul>

         <div class="span3">
            <ul class="nav nav-list bs-docs-sidenav affix-top">
        {* for block_item in blocks *}
               <li><a href="#tabs-{{loop.index}}">{{block_item.label}}</a></li>
        {* endfor *}
            </ul>
        </div>
    -->

    {% for block_item in blocks %}
        {% if loop.index % 2 == 1%}
            {% if app.full_width == 1 %}
                <div class="row-fluid">
            {% else %}
                <div class="row">
            {% endif %}
        {% endif %}

        <div id="tabs-{{loop.index}}" class="span6">
            <div class="well_border">
                <h4>{{block_item.icon}} {{block_item.label}}</h4>
                <div style="list-style-type:none">
                    {{ block_item.search_form }}
                </div>
                {% if block_item.items is not null %}
                    <ul>
                    {% for url in block_item.items %}
                        <li><a href="{{url.url}}">{{ url.label }}</a></li>
                    {% endfor %}
                    </ul>
                {% endif %}

                {% if block_item.extra is not null %}
                    <div>
                        {{ block_item.extra }}
                    </div>
                {% endif %}
            </div>
        </div>
        {% if loop.index % 2 == 0 %}
            </div>
        {% endif %}
    {% endfor %}


</div>
{% endblock %}
