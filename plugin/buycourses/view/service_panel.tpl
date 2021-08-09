<div id="buy-courses-tabs">
    <ul class="nav nav-tabs buy-courses-tabs" role="tablist">
        <li id="buy-courses-tab" class="" role="presentation">
            <a href="course_panel.php" aria-controls="buy-courses" role="tab">{{ 'MyCourses'| get_lang }}</a>
        </li>
        {% if sessions_are_included %}
            <li id="buy-sessions-tab" class="" role="presentation">
                <a href="session_panel.php" aria-controls="buy-sessions" role="tab">{{ 'MySessions'| get_lang }}</a>
            </li>
        {% endif %}
        {% if services_are_included %}
            <li id="buy-services-tab" class="active" role="presentation">
                <a href="service_panel.php" aria-controls="buy-services"
                   role="tab">{{ 'MyServices'| get_plugin_lang('BuyCoursesPlugin') }}</a>
            </li>
        {% endif %}
        <li id="buy-courses-tab" class="" role="presentation">
            <a href="payout_panel.php" aria-controls="buy-courses"
               role="tab">{{ 'MyPayouts'| get_plugin_lang('BuyCoursesPlugin') }}</a>
        </li>
    </ul>
    <table class="table table-striped table-hover">
        <thead>
        <tr>
            <th>{{ 'Service'| get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-center">{{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-center">{{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-center">{{ 'OrderDate'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-center">{{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-center">{{ 'ServiceSaleInfo'|get_plugin_lang('BuyCoursesPlugin') }}</th>
        </tr>
        </thead>
        <tbody>
        {% for sale in sale_list %}
            <tr class="{{ sale.status == service_sale_statuses.status_cancelled ? 'buy-courses-cross-out' : '' }}">
                <td>{{ sale.name }}</td>
                <td class="text-center">{{ sale.service_type }}</td>
                <td class="text-center">{{ sale.currency ~ ' ' ~ sale.price }}</td>
                <td class="text-center">{{ sale.date | api_get_local_time }}</td>
                <td class="text-center">{{ sale.reference }}</td>
                <td class="text-center">
                    <a id="service_sale_info" tag="{{ sale.id }}" name="s_{{ sale.id }}"
                       class="btn btn-info btn-sm">{{ 'Info'|get_lang }}</a>
                </td>
                </td>
            </tr>
        {% endfor %}
        </tbody>
    </table>
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
                    if (action == 'service_sale_info') {
                        $('a[name=s_' + id + ']').html('<em class="fa fa-spinner fa-pulse"></em> {{ 'Loading'|get_lang }}');
                    }
                },
                success: function (response) {
                    $('a[name=s_' + id + ']').html('{{ 'Info'|get_lang }}');
                    var title = "";
                    if (action == 'service_sale_info') {
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
