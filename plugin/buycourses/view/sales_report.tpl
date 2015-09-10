{{ form }}

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th class="text-center">{{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th class="text-center">{{ 'OrderStatus'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th class="text-center">{{ 'OrderDate'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th class="text-center">{{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th class="text-center">{{ 'ProductType'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th>{{ 'Name'|get_lang }}</th>
                <th>{{ 'UserName'|get_lang }}</th>
                {% if selected_status == sale_status_pending %}
                    <th class="text-center">{{ 'Options'|get_lang }}</th>
                {% endif %}
            </tr>
        </thead>
        <tbody>
            {% for sale in sale_list %}
                <tr {{ sale.id == selected_sale ? 'class="warning"' : '' }}>
                    <td class="text-center">{{ sale.reference }}</td>
                    <td class="text-center">
                        {% if sale.status == sale_status_canceled %}
                            {{ 'SaleCanceled'|get_plugin_lang('BuyCoursesPlugin') }}
                        {% elseif sale.status == sale_status_pending %}
                            {{ 'SalePending'|get_plugin_lang('BuyCoursesPlugin') }}
                        {% elseif sale.status == sale_status_completed %}
                            {{ 'SaleCompleted'|get_plugin_lang('BuyCoursesPlugin') }}
                        {% endif %}
                    </td>
                    <td class="text-center">{{ sale.date }}</td>
                    <td class="text-right">{{ sale.currency ~ ' ' ~ sale.price }}</td>
                    <td class="text-center">{{ sale.product_type }}</td>
                    <td>{{ sale.product_name }}</td>
                    <td>{{ sale.complete_user_name }}</td>
                    {% if selected_status == sale_status_pending %}
                        <td class="text-center">
                            {% if sale.status == sale_status_pending %}
                                <a href="{{ _p.web_self ~ '?' ~ {'order': sale.id, 'action': 'confirm'}|url_encode() }}" class="btn btn-success btn-sm">
                                    <i class="fa fa-user-plus fa-fw"></i> {{ 'SubscribeUser'|get_plugin_lang('BuyCoursesPlugin') }}
                                </a>
                                <a href="{{ _p.web_self ~ '?' ~ {'order': sale.id, 'action': 'cancel'}|url_encode() }}" class="btn btn-danger btn-sm">
                                    <i class="fa fa-times fa-fw"></i> {{ 'DeleteOrder'|get_plugin_lang('BuyCoursesPlugin') }}
                                </a>
                            {% endif %}
                        </td>
                    {% endif %}
                </tr>
            {% endfor %}
        </tbody>
    </table>
</div>
