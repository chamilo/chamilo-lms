<link rel="stylesheet" type="text/css" href="../resources/css/style.css"/>
<script type="text/javascript" src="../resources/js/modals.js"></script>
<div class="row">
    <div class="col-md-3 col-sm-12 col-xs-12">
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
                    <th class="text-center">{{ 'Renewable'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                    <th class="text-center">{{ 'ServiceSaleInfo'|get_plugin_lang('BuyCoursesPlugin')  }}</th>
                </tr>
                </thead>
                <tbody>
                {% for sale in sale_list %}
                    <tr>
                        <td class="text-center">{{ sale.service_name }}</td>
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
                        <td class="text-center">{{ sale.date }}</td>
                        <td class="text-right">{{ sale.currency ~ ' ' ~ sale.price }}</td>
                        {% if sale.recurring_payment == 0 %}
                            <td class="text-center">{{ 'No' | get_lang }}</td>
                        {% else %}
                            <td class="text-center">
                                <a id="renewable_info" tag="{{ sale.id }}" name="r_{{ sale.id }}" class="btn btn-warning btn-sm">{{ 'Info' | get_lang }}</a>
                            </td>
                        {% endif %}
                        <td class="text-center">
                            <a id="service_sale_info" tag="{{ sale.id }}" name="s_{{ sale.id }}" class="btn btn-info btn-sm">{{ 'Info' | get_lang }}</a>
                        </td>
                    </tr>
                {% endfor %}
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).on('ready', function () {
        $("td a").click(function() {
            var id = $(this).attr('tag');
            var action = $(this).attr('id');
            $.ajax({
                data: 'id='+id,
                url: '{{ _p.web_plugin }}buycourses/src/buycourses.ajax.php?a='+action,
                type: 'POST',
                beforeSend: function() {
                    if (action == 'renewable_info') {
                        $('a[name=r_'+id+']').html('<em class="fa fa-spinner fa-pulse"></em> {{ 'Loading' | get_lang }}');
                    } else if (action == 'service_sale_info') {
                        $('a[name=s_'+id+']').html('<em class="fa fa-spinner fa-pulse"></em> {{ 'Loading' | get_lang }}');
                    }
                },
                success: function(response) {
                    $('a[name=r_'+id+']').html('{{ 'Info' | get_lang }}');
                    $('a[name=s_'+id+']').html('{{ 'Info' | get_lang }}');
                    var title = "";
                    if (action == "renewable_info") {
                        title = "{{ 'RecurringPaymentProfilePaypalInformation' | get_plugin_lang('BuyCoursesPlugin') }}";
                    } else if (action == 'service_sale_info') {
                        title = "{{ 'ServiceSaleInfo' | get_plugin_lang('BuyCoursesPlugin') }}";
                    }
                    bootbox.dialog({
                        message: response,
                        title: title,
                        buttons: {
                            main: {
                                label: "{{ 'Close' | get_lang }}",
                                className: "btn-default"
                            }
                        }
                    });
                }
            })
        });
    });
</script>
