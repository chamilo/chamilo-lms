<div id="buy-courses-tabs">

    <ul class="nav nav-tabs buy-courses-tabs" role="tablist">
        <li id="buy-courses-tab" class="" role="presentation">
            <a href="course_panel.php" aria-controls="buy-courses" role="tab">{{ 'MyCourses'| get_lang }}</a>
        </li>
        {% if sessions_are_included %}
            <li id="buy-sessions-tab" class="active" role="presentation">
                <a href="session_panel.php" aria-controls="buy-sessions" role="tab">{{ 'MySessions'| get_lang }}</a>
            </li>
        {% endif %}
        {% if services_are_included %}
            <li id="buy-services-tab" class="" role="presentation">
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
            <th>{{ 'Session'|get_lang }}</th>
            <th class="text-center">{{ 'PaymentMethod'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-center">{{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-center">{{ 'OrderDate'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th class="text-center">{{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}</th>
        </tr>
        </thead>
        <tbody>
        {% for sale in sale_list %}
            <tr>
                <td>{{ sale.product_name }}</td>
                <td class="text-center">{{ sale.payment_type }}</td>
                <td class="text-right">{{ sale.currency ~ ' ' ~ sale.price }}</td>
                <td class="text-center">{{ sale.date }}</td>
                <td class="text-center">{{ sale.reference }}</td>
            </tr>
        {% endfor %}
        </tbody>
    </table>


</div>
