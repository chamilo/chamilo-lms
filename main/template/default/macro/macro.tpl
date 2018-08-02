{# special macros to generate repeated html code #}

{% macro collapse(name, title, content, list = false) %}
    <div class="panel-group" id="{{ name }}" role="tablist" aria-multiselectable="true">
        <div class="panel panel-default" id="{{ name }}_block">
            <div class="panel-heading" role="tab">
                <h4 class="panel-title">
                    <a role="button" data-toggle="collapse" data-parent="#{{ name }}" href="#{{ name }}Collapse" aria-expanded="true" aria-controls="{{ name }}Collapse">
                        {{ title }}
                    </a>
                </h4>
            </div>
            <div style="" aria-expanded="true" id="{{ name }}Collapse" class="panel-collapse collapse in" role="tabpanel">
                <div class="panel-body">
                    {% if list %}
                        <ul class="nav nav-pills nav-stacked">
                            {{ content }}
                        </ul>
                    {% else %}
                        {{ content }}
                    {% endif %}
                </div>
            </div>
        </div>
    </div>
{% endmacro %}

{% macro collapseFor(name, title, array) %}
<div class="panel-group" id="{{ name }}" role="tablist" aria-multiselectable="true">
    <div class="panel panel-default" id="{{ name }}_block">
        <div class="panel-heading" role="tab">
            <h4 class="panel-title">
                <a role="button" data-toggle="collapse" data-parent="#{{ name }}" href="#{{ name }}Collapse" aria-expanded="true" aria-controls="{{ name }}Collapse">
                    {{ title }}
                </a>
            </h4>
        </div>
        <div style="" aria-expanded="true" id="{{ name }}Collapse" class="panel-collapse collapse in" role="tabpanel">
            <div class="panel-body">
                <ul class="nav nav-pills nav-stacked">
                    {% for item in array %}
                    <li>
                        <a href="{{ item.link }}">{{ item.title }}</a>
                    </li>
                    {% endfor %}
                </ul>
            </div>
        </div>
    </div>
</div>
{% endmacro %}

{% macro collapseMenu(name, title, array) %}
<div class="panel-group" id="{{ name }}" role="tablist" aria-multiselectable="true">
    <div class="panel panel-default" id="{{ name }}_block">
        <div class="panel-heading" role="tab">
            <h4 class="panel-title">
                <a role="button" data-toggle="collapse" data-parent="#{{ name }}" href="#{{ name }}Collapse" aria-expanded="true" aria-controls="{{ name }}Collapse">
                    {{ title }}
                </a>
            </h4>
        </div>
        <div aria-expanded="true" id="{{ name }}Collapse" class="panel-collapse collapse in" role="tabpanel">
            <div class="panel-body">
                <ul class="list-group">
                    {% for item in array %}
                        <li class="list-group-item {{ item.class }}">
                            <span class="item-icon">{{ item.icon }}</span>
                            <a href="{{ item.link }}">{{ item.title }}</a>
                        </li>
                    {% endfor %}
                </ul>
            </div>
        </div>
    </div>
</div>
{% endmacro %}

{% macro pluginSidebar(name, content) %}
    <div id="{{ name }}" class="plugin plugin_{{ name }}">
        {{ content }}
    </div>
{% endmacro %}

{% macro pluginPanel(name, content) %}
    <div id="{{ name }}" class="plugin plugin_{{ name }}">
        <div class="row">
            <div class="col-md-12">
                {{ content }}
            </div>
        </div>
    </div>
{% endmacro %}


{% macro panel(name, content, footer = '') %}
<div class="panel panel-default">
    <div class="panel-heading"> {{ name }}</div>
    <div class="panel-body">
        {{ content }}
    </div>
    {% if footer %}
        <div class="panel-footer">{{ footer }}</div>
    {% endif %}
</div>
{% endmacro %}