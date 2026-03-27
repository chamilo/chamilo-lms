{% autoescape false %}
{% set tabClass = 'inline-flex items-center rounded-xl px-4 py-2.5 text-sm font-semibold transition' %}
{% set tableShell = 'overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm [&_table]:min-w-full [&_table]:divide-y [&_table]:divide-gray-25 [&_thead]:bg-gray-15 [&_th]:px-4 [&_th]:py-3 [&_th]:text-left [&_th]:text-xs [&_th]:font-semibold [&_th]:uppercase [&_th]:tracking-wide [&_th]:text-gray-50 [&_td]:px-4 [&_td]:py-4 [&_td]:align-middle [&_td]:text-sm [&_td]:text-gray-90 [&_tbody_tr]:border-t [&_tbody_tr]:border-gray-20 [&_tbody_tr:hover]:bg-gray-15/60' %}
<div class="space-y-6">
    <nav class="overflow-hidden rounded-2xl border border-gray-25 bg-white p-2 shadow-sm">
        <div class="flex flex-wrap gap-2">
            <a href="course_panel.php" class="{{ tabClass }} bg-white text-gray-90 hover:bg-gray-15">{{ 'MyCourses'|get_lang }}</a>
            {% if sessions_are_included %}
                <a href="session_panel.php" class="{{ tabClass }} bg-primary text-white">{{ 'MySessions'|get_lang }}</a>
            {% endif %}
            {% if services_are_included %}
                <a href="service_panel.php" class="{{ tabClass }} bg-white text-gray-90 hover:bg-gray-15">{{ 'MyServices'|get_plugin_lang('BuyCoursesPlugin') }}</a>
            {% endif %}
            <a href="payout_panel.php" class="{{ tabClass }} bg-white text-gray-90 hover:bg-gray-15">{{ 'MyPayouts'|get_plugin_lang('BuyCoursesPlugin') }}</a>
        </div>
    </nav>
    <div class="{{ tableShell }}">
        <table>
            <thead>
            <tr>
                <th>{{ 'Session'|get_lang }}</th>
                <th>{{ 'PaymentMethod'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th>{{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th>{{ 'OrderDate'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th>{{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            </tr>
            </thead>
            <tbody>
            {% for sale in sale_list %}
                <tr>
                    <td>{{ sale.product_name }}</td>
                    <td>{{ sale.payment_type }}</td>
                    <td>{{ sale.currency ~ ' ' ~ sale.price }}</td>
                    <td>{{ sale.date }}</td>
                    <td>{{ sale.reference }}</td>
                </tr>
            {% endfor %}
            </tbody>
        </table>
    </div>
</div>
{% endautoescape %}
