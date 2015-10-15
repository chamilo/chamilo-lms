<div>
    <p>{{ 'DearUser'|get_lang }}</p>
    <p>{{ 'PurchaseDetailsIntro'|get_plugin_lang('BuyCoursesPlugin') }}</p>
    <dl>
        <dt>{{ 'OrderDate'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>{{ sale.date }}</dd>
        <dt>{{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>sale.reference</dd>
        <dt>{{ 'UserName'|get_lang }}</dt>
        <dd>{{ user.complete_name }}</dd>
        <dt>{{ 'Course'|get_lang }}</dt>
        <dd>{{ sale.product }}</dd>
        <dt>{{ 'ProductName'|get_plugin_lang('BuyCoursesPlugin') }}</dt>
        <dd>{{ sale.currency ~ ' ' ~ sale.price }}</dd>
    </dl>
    <p>{{ 'BankAccountIntro'|get_plugin_lang('BuyCoursesPlugin')|format(sale.product) }}</p>
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
