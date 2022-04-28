<div class="row">
    <div class="col-md-12">
        {{ items_form }}
    </div>
</div>
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">{{ 'FrequencyConfig'|get_plugin_lang('BuyCoursesPlugin') }}</h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-5">
                {{ frequency_form }}
            </div>
            <div class="col-md-7">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th>{{ 'Duration'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                            <th>{{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                            <th>{{ 'Actions'|get_lang }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        {% for subscription in subscriptions %}
                            <tr>
                                <td>{{ subscription.durationName }}</td>
                                <td>{{ subscription.price }} {{ currencyIso }}</td>
                                <td>
                                    <a href="{{ _p.web_self ~ '?' ~ {'action':'delete_frequency', 'd': subscription.duration, 'id': subscription.product_id, 'type': subscription.product_type}|url_encode() }}"
                                        class="btn btn-danger btn-sm">
                                        <em class="fa fa-remove"></em>
                                    </a>
                                </td>
                            </tr>
                        {% endfor %}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

