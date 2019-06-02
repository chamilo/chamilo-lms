<link rel="stylesheet" type="text/css" href="../resources/css/style.css"/>
<ul class="nav nav-tabs buy-courses-sessions-tabs" role="tablist">
    <li id="buy-courses-sessions-tab" class="active" role="presentation">
        <a href="sales_report.php" aria-controls="buy-courses_sessions"
           role="tab">{{ 'CourseSessionBlock'|get_lang }}</a>
    </li>
    {% if services_are_included %}
        <li id="buy-services-tab" class="{{ showing_services ? 'active' : '' }}" role="presentation">
            <a href="service_sales_report.php" aria-controls="buy-services"
               role="tab">{{ 'Services'|get_plugin_lang('BuyCoursesPlugin') }}</a>
        </li>
    {% endif %}
</ul>
</br>
</br>
{{ form }}

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
        <tr>
            <th class="text-center">{{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-center">{{ 'OrderStatus'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-center">{{ 'OrderDate'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-center">{{ 'PaymentMethod'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-center">{{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-center">{{ 'ProductType'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th>{{ 'Name'|get_lang }}</th>
            <th>{{ 'UserName'|get_lang }}</th>
            {% if invoicing_enable %}
                <th class="text-center">{{ 'Invoice'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            {% endif %}
            <th class="text-center">{{ 'Options'|get_lang }}</th>
        </tr>
        </thead>
        <tbody>
        {% for sale in sale_list %}
            <tr {{ sale.id == selected_sale ? 'class="warning"' : '' }}>
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
                <td class="text-center">{{ sale.date }}</td>
                <td class="text-center">{{ sale.payment_type }}</td>
                <td class="text-right">{{ sale.currency ~ ' ' ~ sale.price }}</td>
                <td class="text-center">{{ sale.product_type }}</td>
                <td>{{ sale.product_name }}</td>
                <td>{{ sale.complete_user_name }}</td>
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
                        <a href="{{ _p.web_self ~ '?' ~ {'order': sale.id, 'action': 'confirm'}|url_encode() }}"
                           class="btn btn-success btn-sm">
                            <em class="fa fa-user-plus fa-fw"></em> {{ 'SubscribeUser'|get_plugin_lang('BuyCoursesPlugin') }}
                        </a>
                        <a href="{{ _p.web_self ~ '?' ~ {'order': sale.id, 'action': 'cancel'}|url_encode() }}"
                           class="btn btn-danger btn-sm">
                            <em class="fa fa-times fa-fw"></em> {{ 'DeleteOrder'|get_plugin_lang('BuyCoursesPlugin') }}
                        </a>
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
            } else {
                $('#report-by-status').hide();
                $('#report-by-user').show();
            }
        });
    });
</script>
