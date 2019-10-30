<div>
    <p>{{ 'DearUser'|get_lang }}</p>
    <p>{{ 'PurchaseDetailsIntro'|get_plugin_lang('BuyCoursesPlugin') }}</p>
    <dl>
        <dt>{{ 'OrderDate'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>{{ service_sale.buy_date|api_convert_and_format_date(constant('DATE_TIME_FORMAT_LONG_24H')) }}</dd>
        <dt>{{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>{{ service_sale.reference }}</dd>
        <dt>{{ 'UserName'|get_lang }}</dt>
        <dd>{{ service_sale.buyer }}</dd>
        <dt>{{ 'Service'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>{{ service_sale.name }}</dd>
        <dt>{{ 'SalePrice'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>{{ service_sale.currency ~ ' ' ~ service_sale.price }}</dd>
    </dl>
    <p>{{ 'BankAccountIntro'|get_plugin_lang('BuyCoursesPlugin')|format(service_sale.name) }}</p>
    <table>
        <thead>
        <tr>
            <th>{{ 'Name'|get_lang }}</th>
            <th>{{ 'BankAccount'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            <th>{{ 'SWIFT'|get_plugin_lang('BuyCoursesPlugin') }}</th>
        </tr>
        </thead>
        <tbody>
        {% for account in transfer_accounts %}
            <tr>
                <td>{{ account.name }}</td>
                <td>{{ account.account }}</td>
                <td>{{ account.swift }}</td>
            </tr>
        {% endfor %}
        </tbody>
    </table>
    <p>{{ 'PurchaseDetailsEnd'|get_plugin_lang('BuyCoursesPlugin') }}</p>
</div>
