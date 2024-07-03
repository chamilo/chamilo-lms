<ul class="nav nav-tabs buy-courses-sessions-tabs" role="tablist">
    <li id="buy-courses-sessions-tab" class="" role="presentation">
        <a href="sales_report.php" aria-controls="buy-courses_sessions"
           role="tab">{{ 'CourseSessionBlock'|get_lang }}</a>
    </li>
    <li id="buy-services-tab" class="active" role="presentation">
        <a href="service_sales_report.php" aria-controls="buy-services"
           role="tab">{{ 'Services'|get_plugin_lang('BuyCoursesPlugin') }}</a>
    </li>
    <li id="buy-subscriptions-tab" role="presentation">
        <a href="subscription_sales_report.php" aria-controls="buy-subscriptions"
           role="tab">{{ 'Subscriptions'|get_plugin_lang('BuyCoursesPlugin') }}</a>
    </li>
</ul>
</br>
</br>
<div class="row">
    <div class="col-md-3 col-sm-12 col-xs-12">
        <h4><b>{{ 'Filter'|get_lang }}</b></h4>
        {{ form }}
    </div>
    <div class="col-md-9 col-sm-12 col-xs-12">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                <tr>
                    <th class="text-center">{{ 'ServiceName'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                    <th class="text-center">{{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                    <th class="text-center">{{ 'OrderStatus'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                    <th class="text-center">{{ 'OrderDate'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                    <th class="text-right">{{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                    <th class="text-right">{{ 'CouponDiscount'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                    <th class="text-right">{{ 'Coupon'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                    {% if invoicing_enable %}
                        <th class="text-right">{{ 'Invoice'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                    {% endif %}
                    <th class="text-center">{{ 'ServiceSaleInfo'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                </tr>
                </thead>
                <tbody>
                {% for sale in sale_list %}
                    <tr>
                        <td class="text-center">{{ sale.name }}</td>
                        <td class="text-center">{{ sale.reference }}</td>
                        <td class="text-center">
                            {% if sale.status == sale_status_cancelled %}
                                {{ 'SaleStatusCancelled'|get_plugin_lang('BuyCoursesPlugin') }}
                            {% elseif sale.status == sale_status_pending %}
                                {{ 'SaleStatusPending'|get_plugin_lang('BuyCoursesPlugin') }}
                            {% elseif sale.status == sale_status_completed %}
                                {{ 'SaleStatusCompleted'|get_plugin_lang('BuyCoursesPlugin') }}
                            {% endif %}
                        </td>
                        <td class="text-center">{{ sale.buy_date | api_get_local_time}}</td>
                        <td class="text-right">{{ sale.service.total_price }}</td>
                        <td class="text-right">{{ sale.total_discount }}</td>
                        <td class="text-right">{{ sale.coupon_code }}</td>
                        {% if invoicing_enable %}
                            <td class="text-center">
                            {% if sale.invoice == 1 %}
                                <a href="{{ _p.web_plugin ~ 'buycourses/src/invoice.php?' ~ {'invoice': sale.id, 'is_service': 1}|url_encode() }}" title="{{ 'InvoiceView'|get_plugin_lang('BuyCoursesPlugin') }}" >
                                    <img src="{{ _p.web_img }}/icons/32/default.png" alt="{{ 'InvoiceView'|get_plugin_lang('BuyCoursesPlugin') }}" />
                                    <br>{{ sale.num_invoice }}
                                </a>
                            {% endif %}
                            </td>
                        {% endif %}
                        <td class="text-center">
                            <a id="service_sale_info" tag="{{ sale.id }}" name="s_{{ sale.id }}"
                               class="btn btn-info btn-sm">
                                {{ 'Info'|get_lang }}
                            </a>
                        </td>
                    </tr>
                {% endfor %}
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(function () {
        $("td a").click(function () {
            var id = $(this).attr('tag');
            var action = $(this).attr('id');
            $.ajax({
                data: 'id=' + id,
                url: '{{ _p.web_plugin }}buycourses/src/buycourses.ajax.php?a=' + action,
                type: 'POST',
                beforeSend: function () {
                    if (action == 'renewable_info') {
                        $('a[name=r_' + id + ']').html('<em class="fa fa-spinner fa-pulse"></em> {{ 'Loading'|get_lang }}');
                    } else if (action == 'service_sale_info') {
                        $('a[name=s_' + id + ']').html('<em class="fa fa-spinner fa-pulse"></em> {{ 'Loading'|get_lang }}');
                    }
                },
                success: function (response) {
                    $('a[name=r_' + id + ']').html('{{ 'Info'|get_lang }}');
                    $('a[name=s_' + id + ']').html('{{ 'Info'|get_lang }}');
                    var title = "";
                    if (action == "renewable_info") {
                        title = "{{ 'RecurringPaymentProfilePaypalInformation'|get_plugin_lang('BuyCoursesPlugin') }}";
                    } else if (action == 'service_sale_info') {
                        title = "{{ 'ServiceSaleInfo'|get_plugin_lang('BuyCoursesPlugin') }}";
                    }
                    bootbox.dialog({
                        message: response,
                        title: title,
                        buttons: {
                            main: {
                                label: "{{ 'Close'|get_lang }}",
                                className: "btn-default"
                            }
                        }
                    });
                }
            })
        });
    });
</script>
