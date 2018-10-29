<div class="row">
    {% if global_tools|length or added_tools|length %}
        <div class="col-sm-3">
            {% if added_tools|length %}
                <h2>{{ 'ToolsAdded'|get_plugin_lang('ImsLtiPlugin') }}</h2>
                <ul class="nav nav-pills nav-stacked">
                    {% for tool in added_tools %}
                        <li class="{{ type == tool.id ? 'active' : '' }}">
                            <a href="{{ _p.web_plugin }}ims_lti/configure.php?action=edit&id={{ tool.id }}&{{ _p.web_cid_query }}">
                                {{ tool.name }}
                            </a>
                        </li>
                    {% endfor %}
                </ul>
            {% endif %}

            {% if global_tools|length %}
                <h2>{{ 'AvailableTools'|get_plugin_lang('ImsLtiPlugin') }}</h2>
                <ul class="nav nav-pills nav-stacked">
                    {% for tool in global_tools %}
                        <li class="{{ type == tool.id ? 'active' : '' }}">
                            {% if tool.isActiveDeepLinking %}
                                <a href="{{ _p.web_plugin }}ims_lti/start.php?id={{ tool.id }}&{{ _p.web_cid_query }}">{{ tool.name }}</a>
                            {% else %}
                                <a href="{{ _p.web_self }}?type={{ tool.id }}&{{ _p.web_cid_query }}">{{ tool.name }}</a>
                            {% endif %}
                        </li>
                    {% endfor %}
                </ul>
            {% endif %}
        </div>
    {% endif %}

    <div class="col-sm-9 {{ not global_tools|length or not added_tools|length ? 'col-md-offset-3' : '' }}">
        {{ form }}
    </div>
</div>
