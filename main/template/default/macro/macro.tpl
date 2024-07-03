{# Macros to generate repeated html code #}

{% macro collapse(name, title, content, list = false, expanded = 'true', title_right = '', title_icons = '') %}
    <div class="panel-group" id="{{ name }}" role="tablist" aria-multiselectable="true">
        <div class="panel panel-default" id="{{ name }}_block">
            <div class="panel-heading" role="tab">

                {% if title_icons %}
                    {{ title_icons }}
                {% endif %}

                <a role="button" data-toggle="collapse" data-parent="#{{ name }}" href="#{{ name }}Collapse"
                   aria-expanded="{{ expanded }}" aria-controls="{{ name }}Collapse">
                    {{ title }}
                </a>

                {% if title_right %}
                    <div class="pull-right">
                        {{ title_right }}
                    </div>
                {% endif %}
            </div>
            <div aria-expanded="{{ expanded }}" id="{{ name }}Collapse"
                 class="panel-collapse collapse {{  expanded == 'true' ? 'in' : '' }}" role="tabpanel">
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
                <a role="button" data-toggle="collapse" data-parent="#{{ name }}" href="#{{ name }}Collapse"
                   aria-expanded="true" aria-controls="{{ name }}Collapse">
                    {{ title }}
                </a>
            </div>
            <div aria-expanded="true" id="{{ name }}Collapse" class="panel-collapse collapse in" role="tabpanel">
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
                <a role="button" data-toggle="collapse" data-parent="#{{ name }}" href="#{{ name }}Collapse"
                   aria-expanded="true" aria-controls="{{ name }}Collapse">
                    {{ title }}
                </a>
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
        {% if name %}
            <div class="panel-heading"> {{ name }}</div>
        {% endif %}

        <div class="panel-body">
            {{ content }}
        </div>

        {% if footer %}
            <div class="panel-footer">{{ footer }}</div>
        {% endif %}
    </div>
{% endmacro %}

{% macro careers_panel(content, admin, macro_iteration = 0) %}
    {% import _self as display %}
    {% for item in content %}
        <div id="career-{{ item.id }}" class="career panel panel-default" {% if item.parent_id is not empty and macro_iteration != 0 %} style="margin-left: 45px"{% endif %}>
            <div  class="panel-heading">
                <h4>
                    {% if admin %}
                        <a href="{{ _p.web }}main/admin/careers.php?action=edit&id={{ item.id }}">
                            {{ item.name }}
                        </a>
                    {% else %}
                        {{ item.name }}
                    {% endif %}
                </h4>
            </div>
            <div class="panel-body">
                {{ item.description }}
                {% if item.career.promotions is not empty %}
                    <table class="table promotions">
                        <thead class="title">
                            <th>{{ 'Promotions' | get_lang }}</th>
                        </thead>
                        {% for promotions in item.career %}
                            {% for prom in promotions %}
                                <tr>
                                    <td class="promo" rowspan="{{ line }}">
                                        <h4 id="promotion-id-{{ prom.id }}">
                                            <a title="{{ prom.name }}" href="{{ _p.web }}main/admin/promotions.php?action=edit&id={{ prom.id }}">
                                                {{ prom.name }}
                                            </a>
                                        </h4>
                                    </td>
                                </tr>
                            {% endfor %}
                        {% endfor %}
                    </table>
                {% endif %}
            </div>
            {% if item.children is defined %}
                {{ display.careers_panel(item.children, admin, macro_iteration + 1) }}
            {% endif %}
        </div>
    {% endfor %}
{% endmacro %}

{% macro box_widget(name, content, icon) %}
    <div class="card box-widget">
        <div class="card-body">
            <div class="stat-widget-five">
                <i class="fa fa-{{ icon }}" aria-hidden="true"></i>
                {{ content }}
                <div class="box-name">
                    {{ name }}
                </div>
            </div>
        </div>
    </div>
{% endmacro %}

{% macro card_widget(name, content, icon, extra) %}
    <div class="card card-first-date">
        <div class="card-body">
            <div class="stat-widget-five">
                <div class="stat-icon">
                    <i class="fa fa-{{ icon }}" aria-hidden="true"></i>
                    {% if extra %}
                        <span class="active-icon">{{ extra }}</span>
                    {% endif %}
                </div>
                <div class="stat-content">
                    <div class="text-left">
                        <div class="stat-text">
                            {{ content }}
                        </div>
                        <div class="stat-heading">
                            {{ name }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
{% endmacro %}

{% macro reporting_user_details(user) %}
    <div class="parameters">
        <dl class="dl-horizontal">
            {% if user.status %}
                <dt>{{ 'Status'|get_lang }}</dt>
                <dd>{{ user.status }}</dd>
            {% endif %}

            <dt>{{ 'OfficialCode'|get_lang }}</dt>
            <dd>{{ user.code == '' ? 'NoOfficialCode'|get_lang : user.code }}</dd>
            <dt>{{ 'OnLine'|get_lang }}</dt>
            <dd>
                {{ user.user_is_online }}
                {{ user.online }}
            </dd>
            <dt>{{ 'Tel'|get_lang }}</dt>
            <dd>{{ user.phone == '' ? 'NoTel'|get_lang : user.phone }}</dd>

            {% if user.timezone %}
                <dt>{{ 'Timezone'|get_lang }}</dt>
                <dd>{{ user.timezone }}</dd>
            {% endif %}
        </dl>

        {% if user.created %}
            <div class="create">{{ user.created }}</div>
        {% endif %}

        {% if user.url_access or user.legal.url_send %}
            <div class="access">
                {{ user.url_access }}
                {{ user.legal.url_send }}
            </div>
        {% endif %}
    </div>
{% endmacro %}

{% macro reporting_user_box(user) %}
    {% import _self as display %}

    <div class="user">
        <div class="avatar">
            <img width="128px" src="{{ user.avatar }}" class="img-responsive">
        </div>
        <div class="name">
            <h3>
                {% if user.complete_name_link %}
                    {{ user.complete_name_link }}
                {% else %}
                    {{ user.complete_name }}
                {% endif %}
            </h3>
            <p class="email">{{ user.email }}</p>
        </div>

        {{ display.reporting_user_details(user) }}

    </div>
{% endmacro %}
