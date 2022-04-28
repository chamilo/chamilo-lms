<ul class="nav nav-tabs buy-courses-sessions-tabs" role="tablist">
    <li id="buy-courses-sessions-tab" role="presentation">
        <a href="sales_report.php" aria-controls="buy-courses_sessions"
           role="tab">{{ 'CourseSessionBlock'|get_lang }}</a>
    </li>
    {% if services_are_included %}
        <li id="buy-services-tab" class="{{ showing_services ? 'active' : '' }}" role="presentation">
            <a href="service_sales_report.php" aria-controls="buy-services"
               role="tab">{{ 'Services'|get_plugin_lang('BuyCoursesPlugin') }}</a>
        </li>
    {% endif %}
    <li id="buy-subscriptions-tab" class="active" role="presentation">
        <a href="subscription_sales_report.php" aria-controls="buy-subscriptions"
           role="tab">{{ 'Subscriptions'|get_plugin_lang('BuyCoursesPlugin') }}</a>
    </li>
</ul>
<br />
<br />
{{ form }}

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
        <tr class="sale-columns">
            <th>{{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th>{{ 'OrderStatus'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th>{{ 'OrderDate'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th>{{ 'PaymentMethod'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th>{{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th>{{ 'CouponDiscount'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th>{{ 'Coupon'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th>{{ 'ProductType'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th>{{ 'Name'|get_lang }}</th>
            <th>{{ 'UserName'|get_lang }}</th>
            <th>{{ 'Email'|get_lang }}</th>
            {% if invoicing_enable %}
                <th>{{ 'Invoice'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            {% endif %}
            <th width="10%">{{ 'Options'|get_lang }}</th>
        </tr>
        </thead>
        <tbody>
        {% for sale in sale_list %}
            <tr class="sale-row {{ sale.id == selected_sale ? 'warning' : '' }}">
                <td class="text-center">{{ sale.reference }}</td>
                <td class="text-center">
                    {% if sale.status == sale_status_canceled %}
                        {{ 'SaleStatusCanceled'|get_plugin_lang('BuyCoursesPlugin') }}
                    {% elseif sale.status == sale_status_pending %}
                        {{ 'SaleStatusPending'|get_plugin_lang('BuyCoursesPlugin') }}
                    {% elseif sale.status == sale_status_completed %}
                        {{ 'SaleStatusCompleted'|get_plugin_lang('BuyCoursesPlugin') }}
                    {% endif %}
                </td>
                <td class="text-center">{{ sale.date | api_get_local_time }}</td>
                <td class="text-center">{{ sale.payment_type }}</td>
                <td class="text-right">{{ sale.total_price }}</td>
                <td class="text-right">{{ sale.total_discount }}</td>
                <td class="text-right">{{ sale.coupon_code }}</td>
                <td class="text-center">{{ sale.product_type }}</td>
                <td>{{ sale.product_name }}</td>
                <td>{{ sale.complete_user_name }}</td>
                <td>{{ sale.email }}</td>
                {% if invoicing_enable %}
                    <td class="text-center">
                    {% if sale.invoice == 1 %}
                        <a href="{{ _p.web_plugin ~ 'buycourses/src/invoice.php?' ~ {'invoice': sale.id, 'is_service': 0}|url_encode() }}" title="{{ 'InvoiceView'|get_plugin_lang('BuyCoursesPlugin') }}" >
                            <img src="{{ _p.web_img }}/icons/32/default.png" alt="{{ 'InvoiceView'|get_plugin_lang('BuyCoursesPlugin') }}" />
                            <br/>{{ sale.num_invoice }}
                        </a>
                    {% endif %}
                    </td>
                {% endif %}
                <td class="text-center">
                    {% if sale.status == sale_status_pending %}
                        <div class="btn-group btn-group-xs" role="group" aria-label="...">
                            <a title="{{ 'SubscribeUser'|get_plugin_lang('BuyCoursesPlugin') }}" href="{{ _p.web_self ~ '?' ~ {'order': sale.id, 'action': 'confirm'}|url_encode() }}"
                               class="btn btn-default">
                                <img src="{{ 'user_subscribe_session.png' | icon(22) }}" width="22" height="22 alt="{{ 'SubscribeUser'|get_plugin_lang('BuyCoursesPlugin') }}">
                            </a>
                            <a title="{{ 'DeleteOrder'|get_plugin_lang('BuyCoursesPlugin') }}" href="{{ _p.web_self ~ '?' ~ {'order': sale.id, 'action': 'cancel'}|url_encode() }}"
                               class="btn btn-default">
                                <img src="{{ 'delete.png' | icon(22) }}" width="22" height="22 alt="{{ 'DeleteOrder'|get_plugin_lang('BuyCoursesPlugin') }}">
                            </a>
                        </div>
                    {% endif %}
                </td>
            </tr>
        {% endfor %}
        </tbody>
    </table>
</div>

<script>
    $(function () {
        $('[name="filter_type"]').on('change', function () {
            var self = $(this);

            if (self.val() === '0') {
                $('#report-by-user').hide();
                $('#report-by-status').show();
                $('#report-by-date').hide();
                $('#report-by-email').hide();
            } else if (self.val() === '1') {
                $('#report-by-status').hide();
                $('#report-by-user').show();
                $('#report-by-date').hide();
                $('#report-by-email').hide();
            } else if (self.val() === '2') {
                $('#report-by-status').hide();
                $('#report-by-user').hide();
                $('#report-by-date').show();
                $('#report-by-email').hide();
            } else if (self.val() === '3') {
                $('#report-by-status').hide();
                $('#report-by-user').hide();
                $('#report-by-date').hide();
                $('#report-by-email').show();
            }
        });
    });
</script>
