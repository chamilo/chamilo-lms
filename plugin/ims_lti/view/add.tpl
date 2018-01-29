<div class="row">
    {% if tools|length %}
        <div class="col-sm-3">
            <h2 class="page-header">{{ 'AvailableTools'|get_plugin_lang('ImsLtiPlugin') }}</h2>
            <ul class="nav nav-pills nav-stacked">
                {% for tool in tools %}
                    <li class="{{ type == tool.id ? 'active' : '' }}">
                        <a href="{{ _p.web_self }}?type={{ tool.id }}&{{ _p.web_cid_query }}">{{ tool.name }}</a>
                    </li>
                {% endfor %}
            </ul>
        </div>
    {% endif %}
    <div class="col-sm-9 {{ tools|length ? '' : 'col-sm-offset-3' }}">
        <h2 class="page-header">{{ 'ToolSettings'|get_plugin_lang('ImsLtiPlugin') }}</h2>
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
