<div class="row">
    <div class="col-md-12">
        {{ items_form }}
    </div>
</div>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
        <tr class="sale-columns">
            <th>{{ 'Name'|get_lang }}</th>
            <th>{{ 'SubscriptionPeriodDuration'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th width="10%">{{ 'Options'|get_lang }}</th>
        </tr>
        </thead>
        <tbody>
        {% for frequency in frequencies_list %}
            <tr class="sale-row">
                <td class="text-center">{{ frequency.name }}</td>
                <td class="text-center">{{ frequency.duration }}</td>
                <td class="text-center">
                <div class="btn-group btn-group-xs" role="group" aria-label="...">
                    <a title="{{ 'DeleteFrequency'|get_plugin_lang('BuyCoursesPlugin') }}" href="{{ _p.web_plugin ~ 'buycourses/src/configure_frequency.php?' ~ {'action': 'delete_frequency', 'd': frequency.duration, 'n': frequency.name}|url_encode() }}"
                        class="btn btn-danger btn-default">
                        <em class="fa fa-remove"></em>
                    </a>
                </div>
                </td>
            </tr>
        {% endfor %}
        </tbody>
    </table>
</div>