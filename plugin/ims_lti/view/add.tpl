<div class="row">
    {% if tools|length %}
        <div class="col-sm-3">
            <h2>{{ 'AvailableTools'|get_plugin_lang('ImsLtiPlugin') }}</h2>
            <ul class="nav nav-pills nav-stacked">
                {% for tool in tools %}
                    <li class="{{ type == tool.id ? 'active' : '' }}">
                        {% if tool.isActiveDeepLinking %}
                            <a href="{{ _p.web_plugin }}ims_lti/start.php?id={{ tool.id }}&{{ _p.web_cid_query }}">{{ tool.name }}</a>
                        {% else %}
                            <a href="{{ _p.web_self }}?type={{ tool.id }}&{{ _p.web_cid_query }}">{{ tool.name }}</a>
                        {% endif %}
                    </li>
                {% endfor %}
            </ul>
        </div>
    {% endif %}

    <div class="{{ tools|length ? 'col-sm-9' : 'col-sm-12' }}">
        {% if tools|length == 0 %}
            <h2>{{ 'ToolSettings'|get_plugin_lang('ImsLtiPlugin') }}</h2>
        {% endif %}

        {{ form }}
    </div>
</div>

<script>
    $(document).on('ready', function () {
        $('select[name="type"]').on('change', function () {
            var advancedOptionsEl = $('#show_advanced_options');
            var type = parseInt($(this).val());

            if (type > 0) {
                advancedOptionsEl.hide();
            } else {
                advancedOptionsEl.show();
            }
        });
    });
</script>
